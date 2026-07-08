{{-- Wires every <x-blog::share-buttons> on the page (event-delegated, so it also
     covers widgets added later). Appends utm_source/medium/campaign to the shared
     URL. Included once via @once in the component. --}}
<script>
(function () {
    function tag(base, source, medium, campaign) {
        try {
            var u = new URL(base, window.location.origin);
            ['utm_source', 'utm_medium', 'utm_campaign'].forEach(function (p) { u.searchParams.delete(p); });
            u.searchParams.set('utm_source', source);
            u.searchParams.set('utm_medium', medium);
            u.searchParams.set('utm_campaign', campaign);
            return u.toString();
        } catch (e) { return base; }
    }

    function popup(url) {
        window.open(url, '_blank', 'noopener,noreferrer,width=600,height=560');
    }

    function flash(btn) {
        if (!btn.dataset.label) { btn.dataset.label = btn.textContent; }
        btn.textContent = 'Copied!';
        setTimeout(function () { btn.textContent = btn.dataset.label; }, 1500);
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-share-method]');
        if (!btn) { return; }
        var root = btn.closest('[data-share]');
        if (!root) { return; }
        e.preventDefault();

        var method = btn.getAttribute('data-share-method');
        var base = root.getAttribute('data-share-url') || window.location.href;
        var title = root.getAttribute('data-share-title') || document.title;
        var campaign = root.getAttribute('data-share') || 'share';

        switch (method) {
            case 'twitter':
                popup('https://twitter.com/intent/tweet?url=' + encodeURIComponent(tag(base, 'twitter', 'social', campaign)) + '&text=' + encodeURIComponent(title));
                break;
            case 'facebook':
                popup('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(tag(base, 'facebook', 'social', campaign)));
                break;
            case 'linkedin':
                popup('https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(tag(base, 'linkedin', 'social', campaign)));
                break;
            case 'reddit':
                popup('https://www.reddit.com/submit?url=' + encodeURIComponent(tag(base, 'reddit', 'social', campaign)) + '&title=' + encodeURIComponent(title));
                break;
            case 'whatsapp':
                popup('https://api.whatsapp.com/send?text=' + encodeURIComponent(title + ' ' + tag(base, 'whatsapp', 'social', campaign)));
                break;
            case 'email':
                window.location.href = 'mailto:?subject=' + encodeURIComponent(title) + '&body=' + encodeURIComponent(tag(base, 'email', 'email', campaign));
                break;
            case 'copy':
                var link = tag(base, 'copy', 'referral', campaign);
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(link).then(function () { flash(btn); });
                } else {
                    var t = document.createElement('textarea');
                    t.value = link; document.body.appendChild(t); t.select();
                    try { document.execCommand('copy'); } catch (err) {}
                    document.body.removeChild(t); flash(btn);
                }
                break;
        }
    });
})();
</script>
