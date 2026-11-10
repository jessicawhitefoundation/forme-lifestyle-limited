<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Error') — FORMÉ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --forme-bg: #FAF8F5;
            --forme-surface: #ffffff;
            --forme-text: #1a1a1a;
            --forme-muted: #736b63;
            --forme-accent: #6F1D2F;
            --forme-accent-hover: #541623;
            --forme-border: #EFEAE6;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            background-color: var(--forme-bg);
            color: var(--forme-text);
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            -webkit-font-smoothing: antialiased;
        }
        .header {
            padding: 28px 24px;
            text-align: center;
            border-bottom: 1px solid var(--forme-border);
            background: var(--forme-surface);
        }
        .brand-title {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 24px;
            letter-spacing: 5px;
            color: var(--forme-accent);
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
        }
        .brand-sub {
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--forme-muted);
            margin-top: 4px;
        }
        .main-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px 24px;
        }
        .error-card {
            background: var(--forme-surface);
            border: 1px solid var(--forme-border);
            border-radius: 4px;
            max-width: 540px;
            width: 100%;
            padding: 48px 36px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
        }
        .code-pill {
            display: inline-block;
            font-size: 11px;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: var(--forme-accent);
            background: #F8F1F2;
            padding: 6px 14px;
            border-radius: 30px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .headline {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: clamp(28px, 4vw, 36px);
            font-weight: 400;
            color: var(--forme-text);
            line-height: 1.25;
            margin-bottom: 16px;
        }
        .description {
            font-size: 15px;
            color: var(--forme-muted);
            line-height: 1.7;
            margin-bottom: 32px;
        }
        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-primary {
            background-color: var(--forme-accent);
            color: #ffffff;
            padding: 14px 28px;
            text-decoration: none;
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 600;
            border-radius: 2px;
            transition: background-color 0.2s;
            display: inline-block;
            border: none;
            cursor: pointer;
        }
        .btn-primary:hover {
            background-color: var(--forme-accent-hover);
        }
        .btn-secondary {
            border: 1px solid var(--forme-border);
            color: var(--forme-text);
            padding: 14px 24px;
            text-decoration: none;
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 500;
            border-radius: 2px;
            transition: all 0.2s;
            display: inline-block;
        }
        .btn-secondary:hover {
            border-color: var(--forme-accent);
            color: var(--forme-accent);
        }
        .footer {
            padding: 24px;
            text-align: center;
            font-size: 12px;
            color: var(--forme-muted);
            border-top: 1px solid var(--forme-border);
            background: var(--forme-surface);
        }
        .footer a {
            color: var(--forme-accent);
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .error-card {
                padding: 32px 20px;
            }
            .actions {
                flex-direction: column;
            }
            .btn-primary, .btn-secondary {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="/" class="brand-title">FORMÉ</a>
        <div class="brand-sub">Made for every form of movement</div>
    </header>

    <main class="main-wrapper">
        <div class="error-card">
            @yield('content')
        </div>
    </main>

    <footer class="footer">
        <p>Need urgent help with an order? WhatsApp <a href="https://wa.me/{{ config('store.whatsapp_number') }}" target="_blank">{{ config('store.whatsapp_display') }}</a> or email <a href="mailto:{{ config('store.email') }}">{{ config('store.email') }}</a></p>
    </footer>
</body>
</html>
