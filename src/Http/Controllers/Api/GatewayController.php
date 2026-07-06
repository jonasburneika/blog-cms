<?php

namespace BlogCms\Http\Controllers\Api;

use BlogCms\Http\Controllers\Api\Concerns\HandlesApiLocale;
use BlogCms\Http\Controllers\Controller;
use BlogCms\Models\BlogCategory;
use BlogCms\Models\BlogPost;
use BlogCms\Models\BlogPostFaq;
use BlogCms\Models\BlogPostHowToStep;
use BlogCms\Support\PageUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GatewayController extends Controller
{
    use HandlesApiLocale;

    public function handle(Request $request)
    {
        // Inject locale from URL param if not in body
        if ($request->query('locale') && ! $request->filled('locale')) {
            $request->merge(['locale' => $request->query('locale')]);
        }

        $action = $request->input('action');
        $resource = $request->input('resource');

        if (! $action || ! $resource) {
            return response()->json([
                'error' => 'Parameters "action" and "resource" are required.',
                'available_resources' => ['categories', 'posts', 'faqs', 'howto-steps', 'media'],
                'available_actions' => ['list', 'show', 'create', 'update', 'delete', 'upload'],
            ], 422);
        }

        return match ($resource) {
            'categories' => $this->handleCategories($action, $request),
            'posts' => $this->handlePosts($action, $request),
            'faqs' => $this->handleFaqs($action, $request),
            'howto-steps' => $this->handleHowToSteps($action, $request),
            'media' => $this->handleMedia($action, $request),
            default => response()->json(['error' => "Unknown resource: {$resource}"], 422),
        };
    }

    // ─── Categories ──────────────────────────────────────────────

    private function handleCategories(string $action, Request $request)
    {
        return match ($action) {
            'list' => $this->categoryList($request),
            'create' => $this->categoryCreate($request),
            'update' => $this->categoryUpdate($request),
            default => response()->json(['error' => "Action '{$action}' not available for categories. Use: list, create, update"], 422),
        };
    }

    private function categoryList(Request $request)
    {
        $locale = $this->apiLocale($request);

        $data = BlogCategory::orderBy('sort_order')->get()
            ->map(fn ($c) => $this->presentInLocale($c, $locale));

        return response()->json(['data' => $data]);
    }

    private function categoryCreate(Request $request)
    {
        $locale = $this->apiLocale($request);
        app()->setLocale($locale); // scalar fields land under the requested locale

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'menu_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'sort_order' => 'nullable|integer',
        ]);

        $category = BlogCategory::create($validated);

        return response()->json(['data' => $this->presentInLocale($category, $locale)], 201);
    }

    private function categoryUpdate(Request $request)
    {
        $category = BlogCategory::findOrFail($request->input('category_id'));

        $locale = $this->apiLocale($request);
        app()->setLocale($locale); // merge only the requested locale

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'menu_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'sort_order' => 'nullable|integer',
        ]);

        $category->update($validated);

        return response()->json(['data' => $this->presentInLocale($category, $locale)]);
    }

    // ─── Posts ────────────────────────────────────────────────────

    private function handlePosts(string $action, Request $request)
    {
        return match ($action) {
            'list' => $this->postList($request),
            'show' => $this->postShow($request),
            'create' => $this->postCreate($request),
            'update' => $this->postUpdate($request),
            'delete' => $this->postDelete($request),
            'upload-cover' => $this->postUploadCover($request),
            default => response()->json(['error' => "Action '{$action}' not available for posts. Use: list, show, create, update, delete, upload-cover"], 422),
        };
    }

    private function postList(Request $request)
    {
        $locale = $this->apiLocale($request);

        $posts = BlogPost::with(['category:id,title,slug', 'faqs', 'howToSteps'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->category_id, fn ($q, $id) => $q->where('blog_category_id', $id))
            ->when($request->category, function ($q, $category) use ($locale) {
                $q->whereHas('category', fn ($cq) => $cq->where("slug->{$locale}", $category)->orWhere("title->{$locale}", $category));
            })
            ->when($request->locale, fn ($q, $l) => $q->whereNotNull("title->{$l}"))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        $posts->getCollection()->transform(function ($post) use ($locale) {
            return array_merge($this->presentInLocale($post, $locale), [
                'cover_desktop_url' => $post->getFirstMediaUrl('cover_desktop'),
                'cover_mobile_url' => $post->getFirstMediaUrl('cover_mobile'),
            ]);
        });

        return response()->json($posts);
    }

    private function postShow(Request $request)
    {
        $locale = $this->apiLocale($request);
        $post = $this->findPost($request);
        $post->load(['category:id,title,slug', 'faqs', 'howToSteps', 'media']);

        $presented = $this->presentInLocale($post, $locale);

        return response()->json([
            'data' => array_merge($presented, [
                'cover_desktop_url' => $post->getFirstMediaUrl('cover_desktop'),
                'cover_mobile_url' => $post->getFirstMediaUrl('cover_mobile'),
            ]),
            'url' => $presented['url'],
        ]);
    }

    private function postCreate(Request $request)
    {
        $locale = $this->apiLocale($request);
        $request->merge(['locale' => $locale]);
        $this->resolveCategory($request, $locale);
        app()->setLocale($locale); // scalar translatable fields land under this locale

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'blog_category_id' => 'required|exists:blog_categories,id',
            'status' => 'required|in:0,1,2',
            'locale' => 'nullable|string|max:10',
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'howto_title' => 'nullable|string|max:255',
            'howto_description' => 'nullable|string',
            'faq_title' => 'nullable|string|max:255',
            'faqs' => 'nullable|array',
            'faqs.*.question' => 'required_with:faqs|string|max:500',
            'faqs.*.answer' => 'required_with:faqs|string',
            'faqs.*.sort_order' => 'nullable|integer',
            'howto_steps' => 'nullable|array',
            'howto_steps.*.title' => 'required_with:howto_steps|string|max:500',
            'howto_steps.*.content' => 'nullable|string',
            'howto_steps.*.image' => 'nullable|string|max:500',
            'howto_steps.*.tip' => 'nullable|string',
            'howto_steps.*.sort_order' => 'nullable|integer',
            'citations' => 'nullable|array',
            'citations.*.name' => 'required_with:citations|string|max:255',
            'citations.*.url' => 'required_with:citations|url|max:2048',
            'citations.*.type' => 'nullable|string|max:100',
            'citations.*.sort_order' => 'nullable|integer',
            'topics' => 'nullable|array',
            'topics.*.name' => 'required_with:topics|string|max:255',
            'topics.*.same_as' => 'nullable|url|max:2048',
            'topics.*.type' => 'nullable|string|max:100',
            'topics.*.sort_order' => 'nullable|integer',
            'based_on' => 'nullable|array',
            'based_on.*' => 'required_with:based_on|url|max:2048',
        ]);

        $post = DB::transaction(function () use ($validated) {
            $postData = collect($validated)->except(['faqs', 'howto_steps', 'citations', 'topics', 'based_on'])->toArray();
            $postData['user_id'] = config('blog-cms.api.default_author_id');

            $post = BlogPost::create($postData);

            if (! empty($validated['faqs'])) {
                foreach ($validated['faqs'] as $faq) {
                    $post->faqs()->create($faq);
                }
            }

            if (! empty($validated['howto_steps'])) {
                foreach ($validated['howto_steps'] as $step) {
                    $post->howToSteps()->create($step);
                }
            }

            if (! empty($validated['citations'])) {
                foreach ($validated['citations'] as $citation) {
                    $post->citations()->create($citation);
                }
            }

            if (! empty($validated['topics'])) {
                foreach ($validated['topics'] as $topic) {
                    $post->topics()->create($topic);
                }
            }

            if (! empty($validated['based_on'])) {
                foreach ($validated['based_on'] as $url) {
                    $post->basedOn()->create(['url' => $url]);
                }
            }

            return $post;
        });

        $post->load(['category:id,title,slug', 'faqs', 'howToSteps', 'citations', 'topics', 'basedOn']);

        $presented = $this->presentInLocale($post, $locale);

        return response()->json([
            'data' => $presented,
            'url' => $presented['url'],
        ], 201);
    }

    private function postUpdate(Request $request)
    {
        $post = $this->findPost($request);

        $locale = $this->apiLocale($request);
        $this->resolveCategory($request, $locale);
        app()->setLocale($locale); // merge only the requested locale

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'blog_category_id' => 'sometimes|required|exists:blog_categories,id',
            'status' => 'sometimes|required|in:0,1,2',
            'locale' => 'nullable|string|max:10',
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'is_featured' => 'nullable|boolean',
            'published_at' => 'nullable|date',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'howto_title' => 'nullable|string|max:255',
            'howto_description' => 'nullable|string',
            'faq_title' => 'nullable|string|max:255',
            'faqs' => 'nullable|array',
            'faqs.*.question' => 'required_with:faqs|string|max:500',
            'faqs.*.answer' => 'required_with:faqs|string',
            'faqs.*.sort_order' => 'nullable|integer',
            'howto_steps' => 'nullable|array',
            'howto_steps.*.title' => 'required_with:howto_steps|string|max:500',
            'howto_steps.*.content' => 'nullable|string',
            'howto_steps.*.image' => 'nullable|string|max:500',
            'howto_steps.*.tip' => 'nullable|string',
            'howto_steps.*.sort_order' => 'nullable|integer',
        ]);

        DB::transaction(function () use ($validated, $post) {
            $postData = collect($validated)->except(['faqs', 'howto_steps'])->toArray();
            $post->update($postData);

            if (array_key_exists('faqs', $validated)) {
                $post->faqs()->delete();
                foreach ($validated['faqs'] ?? [] as $faq) {
                    $post->faqs()->create($faq);
                }
            }

            if (array_key_exists('howto_steps', $validated)) {
                $post->howToSteps()->delete();
                foreach ($validated['howto_steps'] ?? [] as $step) {
                    $post->howToSteps()->create($step);
                }
            }
        });

        $post->load(['category:id,title,slug', 'faqs', 'howToSteps']);

        $presented = $this->presentInLocale($post, $locale);

        return response()->json([
            'data' => $presented,
            'url' => $presented['url'],
        ]);
    }

    private function postDelete(Request $request)
    {
        $this->findPost($request)->delete();

        return response()->json(['message' => 'Post deleted.']);
    }

    private function postUploadCover(Request $request)
    {
        $post = $this->findPost($request);

        $request->validate([
            'cover_desktop' => 'nullable|image|max:5120',
            'cover_mobile' => 'nullable|image|max:5120',
        ]);

        if (! $request->hasFile('cover_desktop') && ! $request->hasFile('cover_mobile')) {
            return response()->json(['error' => 'At least one cover image (cover_desktop or cover_mobile) is required.'], 422);
        }

        if ($request->hasFile('cover_desktop')) {
            $post->clearMediaCollection('cover_desktop');
            $post->addMediaFromRequest('cover_desktop')->toMediaCollection('cover_desktop');
        }

        if ($request->hasFile('cover_mobile')) {
            $post->clearMediaCollection('cover_mobile');
            $post->addMediaFromRequest('cover_mobile')->toMediaCollection('cover_mobile');
        }

        return response()->json([
            'data' => [
                'cover_desktop_url' => $post->getFirstMediaUrl('cover_desktop'),
                'cover_mobile_url' => $post->getFirstMediaUrl('cover_mobile'),
            ],
        ]);
    }

    // ─── FAQs ────────────────────────────────────────────────────

    private function handleFaqs(string $action, Request $request)
    {
        return match ($action) {
            'list' => $this->faqList($request),
            'show' => $this->faqShow($request),
            'create' => $this->faqCreate($request),
            'update' => $this->faqUpdate($request),
            'delete' => $this->faqDelete($request),
            default => response()->json(['error' => "Action '{$action}' not available for faqs. Use: list, show, create, update, delete"], 422),
        };
    }

    private function faqList(Request $request)
    {
        return response()->json(['data' => $this->findPost($request)->faqs]);
    }

    private function faqShow(Request $request)
    {
        return response()->json(['data' => BlogPostFaq::findOrFail($request->input('faq_id'))]);
    }

    private function faqCreate(Request $request)
    {
        $validated = $request->validate([
            'post_id' => 'required|exists:blog_posts,id',
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'sort_order' => 'nullable|integer',
        ]);

        $post = BlogPost::findOrFail($validated['post_id']);
        $faq = $post->faqs()->create($validated);

        return response()->json(['data' => $faq], 201);
    }

    private function faqUpdate(Request $request)
    {
        $faq = BlogPostFaq::findOrFail($request->input('faq_id'));

        $validated = $request->validate([
            'question' => 'sometimes|required|string|max:500',
            'answer' => 'sometimes|required|string',
            'sort_order' => 'nullable|integer',
        ]);

        $faq->update($validated);

        return response()->json(['data' => $faq]);
    }

    private function faqDelete(Request $request)
    {
        BlogPostFaq::findOrFail($request->input('faq_id'))->delete();

        return response()->json(['message' => 'FAQ deleted.']);
    }

    // ─── HowTo Steps ────────────────────────────────────────────

    private function handleHowToSteps(string $action, Request $request)
    {
        return match ($action) {
            'list' => $this->howToList($request),
            'show' => $this->howToShow($request),
            'create' => $this->howToCreate($request),
            'update' => $this->howToUpdate($request),
            'delete' => $this->howToDelete($request),
            default => response()->json(['error' => "Action '{$action}' not available for howto-steps. Use: list, show, create, update, delete"], 422),
        };
    }

    private function howToList(Request $request)
    {
        return response()->json(['data' => $this->findPost($request)->howToSteps]);
    }

    private function howToShow(Request $request)
    {
        return response()->json(['data' => BlogPostHowToStep::findOrFail($request->input('step_id'))]);
    }

    private function howToCreate(Request $request)
    {
        $validated = $request->validate([
            'post_id' => 'required|exists:blog_posts,id',
            'title' => 'required|string|max:500',
            'content' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'tip' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $post = BlogPost::findOrFail($validated['post_id']);
        $step = $post->howToSteps()->create($validated);

        return response()->json(['data' => $step], 201);
    }

    private function howToUpdate(Request $request)
    {
        $step = BlogPostHowToStep::findOrFail($request->input('step_id'));

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:500',
            'content' => 'nullable|string',
            'image' => 'nullable|string|max:500',
            'tip' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $step->update($validated);

        return response()->json(['data' => $step]);
    }

    private function howToDelete(Request $request)
    {
        BlogPostHowToStep::findOrFail($request->input('step_id'))->delete();

        return response()->json(['message' => 'HowTo step deleted.']);
    }

    // ─── Media ───────────────────────────────────────────────────

    private function handleMedia(string $action, Request $request)
    {
        return match ($action) {
            'upload' => $this->mediaUpload($request),
            default => response()->json(['error' => "Action '{$action}' not available for media. Use: upload"], 422),
        };
    }

    private function mediaUpload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240',
        ]);

        $file = $request->file('image');
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
        $path = $file->storeAs('blog-images', $filename, 'public');

        return response()->json([
            'data' => [
                'path' => '/storage/' . $path,
                'url' => asset('storage/' . $path),
            ],
        ], 201);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    private function findPost(Request $request): BlogPost
    {
        return BlogPost::findOrFail($request->input('post_id') ?? $request->input('id'));
    }

    private function resolveCategory(Request $request, string $locale): void
    {
        if ($request->filled('category') && ! $request->filled('blog_category_id')) {
            if ($category = $this->findCategoryByRef($request->category, $locale)) {
                $request->merge(['blog_category_id' => $category->id]);
            }
        }
    }
}
