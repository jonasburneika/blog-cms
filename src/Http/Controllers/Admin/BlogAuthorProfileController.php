<?php

namespace BlogCms\Http\Controllers\Admin;

use BlogCms\Http\Controllers\Admin\Concerns\EditsTranslations;
use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogAuthorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogAuthorProfileController extends Controller
{
    use EditsTranslations;

    public function index()
    {
        $profiles = BlogAuthorProfile::with('user')
            ->withCount('posts')
            ->orderBy('slug')
            ->paginate(20);

        return view('blog::admin.authors.index', compact('profiles'));
    }

    public function create(Request $request)
    {
        return view('blog::admin.authors.create', [
            'users' => $this->availableUsers(),
            'locale' => $this->editingLocale($request),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);
        $locale = $this->editingLocale($request);

        $profile = new BlogAuthorProfile([
            'user_id' => $validated['user_id'],
            'display_name' => $validated['display_name'] ?? null,
            'role' => $validated['role'] ?? null,
            'social_links' => $this->cleanLinks($request),
            'knows_about' => $this->parseKnowsAbout($request),
            'credentials' => $this->cleanCredentials($request),
            'is_indexable' => $request->boolean('is_indexable'),
        ]);
        $profile->slug = $this->uniqueSlug($validated['display_name'] ?? $this->userName($validated['user_id']));
        $this->fillTranslations($profile, $request, $locale);
        $profile->save();

        if ($request->hasFile('avatar')) {
            $profile->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }

        return redirect()->route('admin.authors.index')->with('success', 'Author profile created.');
    }

    public function edit(Request $request, BlogAuthorProfile $author)
    {
        return view('blog::admin.authors.edit', [
            'author' => $author,
            'users' => $this->availableUsers(),
            'locale' => $this->editingLocale($request),
        ]);
    }

    public function update(Request $request, BlogAuthorProfile $author)
    {
        $validated = $this->validateData($request, $author);
        $locale = $this->editingLocale($request);

        // user_id is fixed once a profile exists — never silently re-assign it.
        $author->fill([
            'display_name' => $validated['display_name'] ?? null,
            'role' => $validated['role'] ?? null,
            'social_links' => $this->cleanLinks($request),
            'knows_about' => $this->parseKnowsAbout($request),
            'credentials' => $this->cleanCredentials($request),
            'is_indexable' => $request->boolean('is_indexable'),
        ]);
        $this->fillTranslations($author, $request, $locale);
        $author->save();

        if ($request->hasFile('avatar')) {
            $author->clearMediaCollection('avatar');
            $author->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }

        return redirect()->route('admin.authors.index')->with('success', 'Author profile updated.');
    }

    public function destroy(BlogAuthorProfile $author)
    {
        $author->delete();

        return redirect()->route('admin.authors.index')->with('success', 'Author profile deleted.');
    }

    protected function validateData(Request $request, ?BlogAuthorProfile $author = null): array
    {
        return $request->validate([
            'user_id' => ['required', 'integer', Rule::unique('blog_author_profiles', 'user_id')->ignore($author?->id)],
            'display_name' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'social_links' => 'nullable|array',
            'social_links.*.label' => 'nullable|string|max:100',
            'social_links.*.url' => 'nullable|url|max:2048',
            'knows_about' => 'nullable|string',
            'credentials' => 'nullable|array',
            'credentials.*.name' => 'nullable|string|max:255',
            'credentials.*.category' => 'nullable|string|max:100',
            'is_indexable' => 'nullable|boolean',
            'avatar' => 'nullable|image|max:5120',
            'locale' => 'nullable|string',
        ]);
    }

    protected function cleanLinks(Request $request): array
    {
        return array_values(array_filter(
            (array) $request->input('social_links', []),
            fn ($l) => ! empty($l['url']),
        ));
    }

    /** "knows about" textarea → array of trimmed, non-empty topics (one per line). */
    protected function parseKnowsAbout(Request $request): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $request->input('knows_about', ''));

        return array_values(array_filter(array_map('trim', $lines), fn ($t) => $t !== ''));
    }

    /** Credential repeater rows → [{name, category}], keeping only named ones. */
    protected function cleanCredentials(Request $request): array
    {
        return array_values(array_filter(
            array_map(fn ($c) => [
                'name' => trim((string) ($c['name'] ?? '')),
                'category' => trim((string) ($c['category'] ?? '')),
            ], (array) $request->input('credentials', [])),
            fn ($c) => $c['name'] !== '',
        ));
    }

    protected function availableUsers()
    {
        $userModel = config('blog-cms.models.user');

        return $userModel::query()->orderBy('name')->get(['id', 'name', 'email']);
    }

    protected function userName($id): string
    {
        $userModel = config('blog-cms.models.user');

        return (string) ($userModel::find($id)?->name ?? 'author');
    }

    protected function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base) ?: 'author';
        $candidate = $slug;
        $i = 1;
        while (BlogAuthorProfile::where('slug', $candidate)->exists()) {
            $i++;
            $candidate = $slug.'-'.$i;
        }

        return $candidate;
    }
}
