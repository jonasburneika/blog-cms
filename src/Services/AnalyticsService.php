<?php

namespace BlogCms\Services;

use BlogCms\Models\Setting;
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\Filter\StringFilter\MatchType;
use Google\Client as GoogleClient;
use Google\Service\SearchConsole;

class AnalyticsService
{
    protected ?array $ga4Credentials = null;
    protected ?string $ga4PropertyId = null;
    protected ?array $gscCredentials = null;
    protected ?string $gscPropertyUrl = null;

    public function __construct()
    {
        $this->ga4PropertyId = Setting::get('ga4_property_id');
        $this->gscPropertyUrl = Setting::get('gsc_property_url');

        $ga4Json = Setting::get('ga4_credentials_json');
        if ($ga4Json) {
            $this->ga4Credentials = json_decode($ga4Json, true);
        }

        $gscJson = Setting::get('gsc_credentials_json');
        if ($gscJson) {
            $this->gscCredentials = json_decode($gscJson, true);
        }
    }

    public function isGa4Configured(): bool
    {
        return class_exists(BetaAnalyticsDataClient::class)
            && $this->ga4Credentials && $this->ga4PropertyId && Setting::get('ga4_enabled', false);
    }

    public function isGscConfigured(): bool
    {
        return $this->gscCredentials && $this->gscPropertyUrl && Setting::get('gsc_enabled', false);
    }

    /**
     * Get GA4 page views for a specific URL path over the last N days.
     */
    public function getPageViews(string $pagePath, int $days = 30): array
    {
        if (!$this->isGa4Configured()) {
            return ['total' => 0, 'daily' => [], 'error' => 'GA4 not configured'];
        }

        try {
            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $this->writeCredentialsToTemp($this->ga4Credentials));

            $client = new BetaAnalyticsDataClient();

            $response = $client->runReport([
                'property' => 'properties/' . $this->ga4PropertyId,
                'dateRanges' => [new DateRange(['start_date' => $days . 'daysAgo', 'end_date' => 'today'])],
                'dimensions' => [new Dimension(['name' => 'date'])],
                'metrics' => [
                    new Metric(['name' => 'screenPageViews']),
                    new Metric(['name' => 'activeUsers']),
                ],
                'dimensionFilter' => new FilterExpression([
                    'filter' => new Filter([
                        'field_name' => 'pagePath',
                        'string_filter' => new StringFilter([
                            'match_type' => MatchType::EXACT,
                            'value' => $pagePath,
                        ]),
                    ]),
                ]),
            ]);

            $daily = [];
            $total = 0;
            $totalUsers = 0;

            foreach ($response->getRows() as $row) {
                $date = $row->getDimensionValues()[0]->getValue();
                $views = (int) $row->getMetricValues()[0]->getValue();
                $users = (int) $row->getMetricValues()[1]->getValue();
                $daily[] = ['date' => $date, 'views' => $views, 'users' => $users];
                $total += $views;
                $totalUsers += $users;
            }

            return ['total' => $total, 'users' => $totalUsers, 'daily' => $daily, 'error' => null];
        } catch (\Exception $e) {
            return ['total' => 0, 'users' => 0, 'daily' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get GSC search queries for a specific URL path over the last N days.
     */
    public function getSearchQueries(string $pagePath, int $days = 30): array
    {
        if (!$this->isGscConfigured()) {
            return ['queries' => [], 'totals' => ['clicks' => 0, 'impressions' => 0], 'error' => 'GSC not configured'];
        }

        try {
            $client = new GoogleClient();
            $client->setAuthConfig($this->gscCredentials);
            $client->setScopes(['https://www.googleapis.com/auth/webmasters.readonly']);

            $service = new SearchConsole($client);

            $request = new SearchConsole\SearchAnalyticsQueryRequest();
            $request->setStartDate(date('Y-m-d', strtotime("-{$days} days")));
            $request->setEndDate(date('Y-m-d'));
            $request->setDimensions(['query']);
            $request->setDimensionFilterGroups([
                new SearchConsole\ApiDimensionFilterGroup([
                    'filters' => [
                        new SearchConsole\ApiDimensionFilter([
                            'dimension' => 'page',
                            'operator' => 'contains',
                            'expression' => $pagePath,
                        ]),
                    ],
                ]),
            ]);
            $request->setRowLimit(20);

            $response = $service->searchanalytics->query($this->gscPropertyUrl, $request);

            $queries = [];
            $totalClicks = 0;
            $totalImpressions = 0;

            foreach ($response->getRows() ?? [] as $row) {
                $queries[] = [
                    'query' => $row->getKeys()[0],
                    'clicks' => (int) $row->getClicks(),
                    'impressions' => (int) $row->getImpressions(),
                    'ctr' => round($row->getCtr() * 100, 1),
                    'position' => round($row->getPosition(), 1),
                ];
                $totalClicks += (int) $row->getClicks();
                $totalImpressions += (int) $row->getImpressions();
            }

            return [
                'queries' => $queries,
                'totals' => ['clicks' => $totalClicks, 'impressions' => $totalImpressions],
                'error' => null,
            ];
        } catch (\Exception $e) {
            return ['queries' => [], 'totals' => ['clicks' => 0, 'impressions' => 0], 'error' => $e->getMessage()];
        }
    }

    protected function writeCredentialsToTemp(array $credentials): string
    {
        $path = storage_path('app/google-credentials-' . md5(json_encode($credentials)) . '.json');
        if (!file_exists($path)) {
            file_put_contents($path, json_encode($credentials));
        }
        return $path;
    }
}
