<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\Setting;
use BlogCms\Services\AnalyticsService;
use BlogCms\Support\LocaleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'gsc' => [
                'gsc_property_url' => Setting::get('gsc_property_url', ''),
                'gsc_credentials_json' => Setting::get('gsc_credentials_json') ? '********' : '',
                'gsc_enabled' => Setting::get('gsc_enabled', false),
            ],
            'ga4' => [
                'ga4_measurement_id' => Setting::get('ga4_measurement_id', ''),
                'ga4_property_id' => Setting::get('ga4_property_id', ''),
                'ga4_credentials_json' => Setting::get('ga4_credentials_json') ? '********' : '',
                'ga4_enabled' => Setting::get('ga4_enabled', false),
                'ga4_tracking_code' => Setting::get('ga4_tracking_code', ''),
            ],
            'general' => [
                'site_name' => Setting::get('site_name', ''),
                'site_description' => Setting::get('site_description', ''),
                'homepage_meta_title' => Setting::get('homepage_meta_title', ''),
                'homepage_meta_description' => Setting::get('homepage_meta_description', ''),
                'blog_posts_per_page' => Setting::get('blog_posts_per_page', '12'),
            ],
            'api' => [
                'api_key' => Setting::get('api_key', ''),
            ],
            'languages' => [
                'default_locale' => LocaleManager::default(),
                'locales' => LocaleManager::all(),
            ],
        ];

        return view('blog::admin.settings.index', compact('settings'));
    }

    public function updateLanguages(Request $request)
    {
        $validated = $request->validate([
            'locales' => 'required|array|min:1',
            'locales.*' => 'string|regex:/^[a-z]{2}(_[A-Z]{2})?$/',
            'default_locale' => 'required|string',
        ]);

        $locales = array_values(array_unique($validated['locales']));

        if (! in_array($validated['default_locale'], $locales, true)) {
            return redirect()->route('admin.settings.index', ['tab' => 'languages'])
                ->with('error', 'The default language must be one of the enabled languages.');
        }

        Setting::set('locales', $locales, 'languages', 'json');
        Setting::set('default_locale', $validated['default_locale'], 'languages');

        LocaleManager::flush();

        return redirect()->route('admin.settings.index', ['tab' => 'languages'])
            ->with('success', 'Language settings updated.');
    }

    public function updateGeneral(Request $request)
    {
        $request->validate([
            'site_name' => 'nullable|string|max:255',
            'site_description' => 'nullable|string|max:500',
            'homepage_meta_title' => 'nullable|string|max:70',
            'homepage_meta_description' => 'nullable|string|max:200',
            'blog_posts_per_page' => 'nullable|integer|min:1|max:100',
        ]);

        Setting::set('site_name', $request->site_name, 'general');
        Setting::set('site_description', $request->site_description, 'general');
        Setting::set('homepage_meta_title', $request->homepage_meta_title, 'general');
        Setting::set('homepage_meta_description', $request->homepage_meta_description, 'general');
        Setting::set('blog_posts_per_page', $request->blog_posts_per_page, 'general');

        return redirect()->route('admin.settings.index', ['tab' => 'general'])
            ->with('success', 'General settings updated.');
    }

    public function updateGsc(Request $request)
    {
        $request->validate([
            'gsc_property_url' => 'nullable|string|max:255',
            'gsc_credentials_json' => 'nullable|string',
            'gsc_enabled' => 'nullable|boolean',
        ]);

        Setting::set('gsc_property_url', $request->gsc_property_url, 'google_search_console');
        Setting::set('gsc_enabled', $request->boolean('gsc_enabled'), 'google_search_console', 'boolean');

        if ($request->gsc_credentials_json && $request->gsc_credentials_json !== '********') {
            Setting::set('gsc_credentials_json', $request->gsc_credentials_json, 'google_search_console', 'encrypted');
        }

        return redirect()->route('admin.settings.index', ['tab' => 'gsc'])
            ->with('success', 'Google Search Console settings updated.');
    }

    public function updateGa4(Request $request)
    {
        $request->validate([
            'ga4_measurement_id' => 'nullable|string|max:50',
            'ga4_property_id' => 'nullable|string|max:50',
            'ga4_credentials_json' => 'nullable|string',
            'ga4_enabled' => 'nullable|boolean',
            'ga4_tracking_code' => 'nullable|string',
        ]);

        Setting::set('ga4_measurement_id', $request->ga4_measurement_id, 'google_analytics');
        Setting::set('ga4_property_id', $request->ga4_property_id, 'google_analytics');
        Setting::set('ga4_enabled', $request->boolean('ga4_enabled'), 'google_analytics', 'boolean');
        Setting::set('ga4_tracking_code', $request->ga4_tracking_code, 'google_analytics');

        if ($request->ga4_credentials_json && $request->ga4_credentials_json !== '********') {
            Setting::set('ga4_credentials_json', $request->ga4_credentials_json, 'google_analytics', 'encrypted');
        }

        return redirect()->route('admin.settings.index', ['tab' => 'ga4'])
            ->with('success', 'Google Analytics 4 settings updated.');
    }

    public function generateApiKey()
    {
        $key = 'ak_' . Str::random(48);
        Setting::set('api_key', $key, 'api', 'encrypted');

        return redirect()->route('admin.settings.index', ['tab' => 'api'])
            ->with('success', 'API key generated successfully.')
            ->with('new_api_key', $key);
    }

    public function revokeApiKey()
    {
        Setting::where('key', 'api_key')->delete();

        return redirect()->route('admin.settings.index', ['tab' => 'api'])
            ->with('success', 'API key revoked.');
    }

    public function testGsc()
    {
        $analytics = app(AnalyticsService::class);
        if (!$analytics->isGscConfigured()) {
            return response()->json(['success' => false, 'message' => 'GSC not configured. Check credentials and property URL.']);
        }
        $result = $analytics->getSearchQueries('/', 7);
        if ($result['error']) {
            return response()->json(['success' => false, 'message' => 'Connection failed: ' . $result['error']]);
        }
        return response()->json(['success' => true, 'message' => 'Connected successfully! Found ' . count($result['queries']) . ' queries.']);
    }

    public function testGa4()
    {
        $analytics = app(AnalyticsService::class);
        if (!$analytics->isGa4Configured()) {
            return response()->json(['success' => false, 'message' => 'GA4 not configured. Check credentials and property ID.']);
        }
        $result = $analytics->getPageViews('/', 7);
        if ($result['error']) {
            return response()->json(['success' => false, 'message' => 'Connection failed: ' . $result['error']]);
        }
        return response()->json(['success' => true, 'message' => 'Connected! ' . $result['total'] . ' page views in last 7 days.']);
    }
}
