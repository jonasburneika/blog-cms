<p>Hi {{ $guest->name }},</p>

<p>Good news — your guest post pitch{{ $guest->proposed_topic ? ' on "'.$guest->proposed_topic.'"' : '' }} has been approved.</p>

<p>You can now submit your full article using the private link below. It's unique to you, so please don't share it:</p>

@if($submitUrl)
    <p><a href="{{ $submitUrl }}">Submit your article</a></p>
    <p style="color:#666;font-size:13px;">Or paste this into your browser:<br>{{ $submitUrl }}</p>
@endif

<p>Once you submit, our editors will review it before publishing.</p>

<p>Thanks,<br>{{ \BlogCms\Models\Setting::get('site_name', config('app.name')) }}</p>
