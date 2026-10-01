<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('aegis-admin-ip-access::admin-ip-access.denied.title') }}</title>
    <style>
        :root { color-scheme: light dark; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; color: #0f172a; }
        main { max-width: 32rem; padding: 2rem; text-align: center; }
        h1 { margin: 0 0 .75rem; font-size: 1.5rem; }
        p { margin: .5rem 0; color: #475569; }
        code { font-size: .875rem; }
        @media (prefers-color-scheme: dark) { body { background: #0f172a; color: #f1f5f9; } p { color: #94a3b8; } }
    </style>
</head>
<body>
<main>
    <h1>{{ __('aegis-admin-ip-access::admin-ip-access.denied.title') }}</h1>
    <p>{{ __('aegis-admin-ip-access::admin-ip-access.denied.message') }}</p>
    @if (is_string($ip ?? null))
        <p><code>{{ __('aegis-admin-ip-access::admin-ip-access.denied.your_ip', ['ip' => $ip]) }}</code></p>
    @endif
</main>
</body>
</html>
