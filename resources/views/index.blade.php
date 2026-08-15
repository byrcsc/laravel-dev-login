{{-- Placeholder page. The real one, and the component it wraps, land in issue 06. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dev login</title>
</head>
<body>
<h1>Dev login</h1>

@forelse ($profiles as $profile)
    <form method="POST" action="{{ route('dev-login.attempt', $profile->key) }}">
        @csrf
        <button type="submit">{{ $profile->label }}</button>
    </form>
@empty
    <p>No profiles are configured. Add one to the <code>profiles</code> key in <code>config/dev-login.php</code>.</p>
@endforelse
</body>
</html>
