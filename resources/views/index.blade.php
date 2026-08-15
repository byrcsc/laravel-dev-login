{{--
    The dev login page: the application's name, the environment it is running
    in, and one button per profile.

    The environment badge is a safety feature aimed at the person looking at
    the screen, which is why this page is deliberately not themeable into
    something that could be mistaken for a real login.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dev login &mdash; {{ config('app.name') }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background: #f1f5f9;
            color: #0f172a;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
        }
        main {
            width: 100%;
            max-width: 24rem;
            padding: 2rem;
            border: 1px solid #e2e8f0;
            border-radius: .75rem;
            background: #ffffff;
        }
        header { margin-bottom: 1.5rem; }
        h1 { margin: 0 0 .5rem; font-size: 1.25rem; }
        .env {
            display: inline-block;
            padding: .125rem .5rem;
            border-radius: 9999px;
            background: #fef08a;
            color: #713f12;
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        footer { margin-top: 1.5rem; font-size: .8125rem; color: #475569; }
    </style>
</head>
<body>
<main>
    <header>
        <h1>{{ config('app.name') }}</h1>
        <span class="env">{{ app()->environment() }}</span>
    </header>

    <x-dev-login::profiles />

    <footer>Development login. Anybody who can reach this page can sign in as any profile on it.</footer>
</main>
</body>
</html>
