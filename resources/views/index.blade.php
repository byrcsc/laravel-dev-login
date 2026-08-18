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
            padding: 1.5rem 1rem;
            background:
                radial-gradient(circle at top left, rgba(245, 158, 11, .10), transparent 28rem),
                #f1f5f9;
            color: #0f172a;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
        }
        main {
            width: 100%;
            max-width: 46rem;
            padding: clamp(1.25rem, 3vw, 1.75rem);
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: #ffffff;
            box-shadow: 0 1.5rem 4rem rgba(15, 23, 42, .08);
        }
        header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .eyebrow {
            margin: 0 0 .25rem;
            color: #64748b;
            font-size: .625rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        h1 { margin: 0; font-size: clamp(1.125rem, 3vw, 1.375rem); line-height: 1.2; }
        .env {
            display: inline-block;
            padding: .125rem .5rem;
            border-radius: 9999px;
            background: #fef08a;
            color: #713f12;
            font-size: .625rem;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        footer {
            margin-top: 1.25rem;
            padding-top: .875rem;
            border-top: 1px solid #e2e8f0;
            font-size: .6875rem;
            color: #64748b;
        }
    </style>
</head>
<body>
<main>
    <header>
        <div>
            <p class="eyebrow">Developer access</p>
            <h1>{{ config('app.name') }}</h1>
        </div>
        <span class="env">{{ app()->environment() }}</span>
    </header>

    <x-dev-login::profiles />

    <footer>Development login. Anybody who can reach this page can sign in as any profile on it.</footer>
</main>
</body>
</html>
