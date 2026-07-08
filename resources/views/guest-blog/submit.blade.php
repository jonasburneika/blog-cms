@extends(config('blog-cms.frontend.layout') ?: 'blog::layouts.public')

@section('title', 'Submit your article')

@section(config('blog-cms.frontend.section', 'content'))
    <section class="guest-submit">
        <h1 class="guest-submit__heading">Submit your article</h1>
        <p class="guest-submit__intro">Hi {{ $guest->name }} — your pitch was approved. Add your full article below; our editors will review it before it goes live.</p>

        @if($errors->any())
            <ul class="guest-form__errors">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('blog.guest.submit.store', $token) }}" enctype="multipart/form-data" class="guest-form">
            @csrf
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;">

            <label>Title
                <input type="text" name="title" value="{{ old('title', $guest->proposed_topic) }}" required maxlength="255">
            </label>
            <label>Excerpt <span class="guest-form__optional">(optional, a one-line summary)</span>
                <textarea name="excerpt" rows="2" maxlength="1000">{{ old('excerpt') }}</textarea>
            </label>
            <label>Article
                <textarea name="content" rows="18" required>{{ old('content') }}</textarea>
            </label>
            <p class="guest-form__hint">Basic formatting (headings, bold, lists, links, images) is kept; anything unsafe is removed automatically.</p>
            <label>Cover image <span class="guest-form__optional">(optional)</span>
                <input type="file" name="cover" accept="image/*">
            </label>

            <button type="submit" class="guest-form__submit">Submit article</button>
        </form>
    </section>
@endsection
