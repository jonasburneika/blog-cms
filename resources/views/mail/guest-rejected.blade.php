<p>Hi {{ $guest->name }},</p>

<p>Thank you for your interest in contributing a guest post{{ $guest->proposed_topic ? ' on "'.$guest->proposed_topic.'"' : '' }}.</p>

<p>After review, we're not able to move forward with this pitch at the moment.</p>

@if($guest->admin_notes)
    <p>{{ $guest->admin_notes }}</p>
@endif

<p>We appreciate you thinking of us and welcome future pitches.</p>

<p>Thanks,<br>{{ \BlogCms\Models\Setting::get('site_name', config('app.name')) }}</p>
