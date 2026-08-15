{{--
    A login page the demo app owns, borrowing the package's component.

    Nothing here comes from the package except the one tag, and the component
    brings its own styles, so this is what embedding actually costs.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in &mdash; workbench</title>
    <style>
        body {
            margin: 0 auto;
            padding: 3rem 1rem;
            max-width: 22rem;
            font-family: Georgia, "Times New Roman", serif;
            line-height: 1.5;
        }
        h1 { font-size: 1.5rem; }
        hr { margin: 2rem 0; border: 0; border-top: 1px solid #cbd5e1; }
    </style>
</head>
<body>
<h1>Sign in</h1>

<p>Imagine a password form here. The demo does not have one, because the
    package is not a login system.</p>

<hr>

<p>Or skip it, in development:</p>

<x-dev-login::profiles />
</body>
</html>
