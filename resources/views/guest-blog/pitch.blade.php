@extends(config('blog-cms.frontend.layout') ?: 'blog::layouts.public')

@section('title', 'Write for us')

@section(config('blog-cms.frontend.section', 'content'))
    <section class="guest-pitch">
        <h1 class="guest-pitch__heading">Write for us</h1>
        <p class="guest-pitch__intro">Pitch your article idea below. If it's a good fit, we'll email you a private link to submit the full article — no account needed.</p>

        @if($errors->any())
            <ul class="guest-form__errors">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('blog.guest.pitch.store') }}" class="guest-form">
            @csrf
            {{-- Honeypot: leave empty; real users never see it. --}}
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;">

            <label>Your name
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="255">
            </label>
            <label>Email
                <input type="email" name="email" value="{{ old('email') }}" required maxlength="255">
            </label>
            <label>Phone <span class="guest-form__optional">(optional)</span>
                <input type="text" name="phone" value="{{ old('phone') }}" maxlength="50">
            </label>
            <label>Proposed topic
                <input type="text" name="proposed_topic" value="{{ old('proposed_topic') }}" required maxlength="255">
            </label>
            <label>Preferred category <span class="guest-form__optional">(optional)</span>
                <input type="text" name="category_preference" value="{{ old('category_preference') }}" maxlength="255">
            </label>
            <label>Pitch / message
                <textarea name="message" rows="6" required maxlength="5000">{{ old('message') }}</textarea>
            </label>
            <label>Link to a writing sample <span class="guest-form__optional">(optional)</span>
                <input type="url" name="sample_url" value="{{ old('sample_url') }}" maxlength="2048" placeholder="https://…">
            </label>

            <button type="submit" class="guest-form__submit">Send pitch</button>
        </form>
    </section>
@endsection
