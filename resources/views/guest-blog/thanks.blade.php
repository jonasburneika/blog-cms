@extends(config('blog-cms.frontend.layout') ?: 'blog::layouts.public')

@section('title', 'Thank you')

@section(config('blog-cms.frontend.section', 'content'))
    <section class="guest-thanks">
        @switch($stage)
            @case('pitch')
                <h1>Thanks for your pitch!</h1>
                <p>We've received it. If it's a good fit, we'll email you a private link to submit your full article.</p>
                @break
            @case('article')
                <h1>Article submitted 🎉</h1>
                <p>Thanks! Your article is in our editors' queue for review before publishing. We'll be in touch.</p>
                @break
            @case('already')
                <h1>Already submitted</h1>
                <p>This article has already been submitted and is with our editors. If you think this is a mistake, just reply to our email.</p>
                @break
            @default
                <h1>Thank you</h1>
        @endswitch

        <p><a href="{{ url('/') }}">&larr; Back to home</a></p>
    </section>
@endsection
