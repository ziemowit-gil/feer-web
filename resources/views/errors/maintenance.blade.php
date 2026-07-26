@php
    $settings = $settings ?? \App\Models\SiteSetting::current();
    $palette = $settings->brandPalette();
    $message = $message ?? $settings->maintenanceMessage();
@endphp
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Przerwa techniczna — {{ $settings->site_name }}</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;700&family=Montserrat:wght@400;700&display=swap">
    <style>
        :root {
            --brand: {{ $palette['color'] }};
            --brand-dark: {{ $palette['dark'] }};
            --brand-light: {{ $palette['light'] }};
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            font-family: 'Montserrat', system-ui, sans-serif;
            color: #1f2933;
            background: linear-gradient(135deg, var(--brand-light), #ffffff);
        }
        .card {
            width: 100%;
            max-width: 34rem;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 1rem;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, .06);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 4.5rem;
            height: 4.5rem;
            margin-bottom: 1.5rem;
            border-radius: 9999px;
            background: var(--brand-light);
            color: var(--brand);
            font-size: 1.75rem;
        }
        .logo { max-height: 3.5rem; margin: 0 auto 1.5rem; display: block; }
        h1 { font-family: 'Ubuntu', sans-serif; font-size: 1.6rem; margin: 0 0 .75rem; color: #111827; }
        p { margin: 0 auto; max-width: 26rem; line-height: 1.6; color: #52606d; }
        .kicker { text-transform: uppercase; letter-spacing: .08em; font-size: .8rem; font-weight: 700; color: var(--brand); margin-bottom: .5rem; }
        .login {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin-top: 2rem;
            font-size: .85rem;
            font-weight: 700;
            color: var(--brand-dark);
            text-decoration: none;
        }
        .login:hover, .login:focus-visible { text-decoration: underline; }
    </style>
</head>
<body>
    <main class="card" role="main">
        @if ($settings->logoUrl())
            <img class="logo" src="{{ $settings->logoUrl() }}" alt="{{ $settings->logoAltText() }}">
        @else
            <span class="badge" aria-hidden="true"><i class="fa-solid fa-screwdriver-wrench"></i></span>
        @endif

        <p class="kicker">Przerwa techniczna</p>
        <h1>Chwilowo prowadzimy prace serwisowe</h1>
        <p>{{ $message }}</p>

        <a class="login" href="{{ route('login') }}">
            <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
            Logowanie dla administratorów
        </a>
    </main>
</body>
</html>
