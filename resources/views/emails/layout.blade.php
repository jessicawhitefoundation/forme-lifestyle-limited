<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'FORMÉ' }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #FAF8F5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1a1a1a;
            -webkit-font-smoothing: antialiased;
        }
        table {
            border-collapse: collapse;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #EFEAE6;
            border-radius: 4px;
            overflow: hidden;
        }
        .header {
            padding: 36px 32px 24px;
            text-align: center;
            background-color: #ffffff;
            border-bottom: 1px solid #F0ECE8;
        }
        .brand-title {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 26px;
            letter-spacing: 4px;
            color: #6F1D2F;
            text-decoration: none;
            font-weight: 600;
            margin: 0;
        }
        .brand-sub {
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #8c827a;
            margin-top: 6px;
        }
        .content {
            padding: 36px 32px;
            line-height: 1.6;
        }
        .button {
            display: inline-block;
            background-color: #6F1D2F;
            color: #ffffff !important;
            padding: 14px 28px;
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            text-decoration: none;
            font-weight: 600;
            border-radius: 2px;
            margin: 20px 0;
        }
        .footer {
            padding: 28px 32px;
            text-align: center;
            font-size: 12px;
            color: #8c827a;
            background-color: #FAF8F5;
            border-top: 1px solid #EFEAE6;
            line-height: 1.7;
        }
        .footer a {
            color: #6F1D2F;
            text-decoration: none;
        }
        .tag {
            display: inline-block;
            font-size: 10px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 2px;
            background-color: #F8F1F2;
            color: #6F1D2F;
            font-weight: 600;
        }
        @media only screen and (max-width: 600px) {
            .container {
                width: 100% !important;
                border-left: none !important;
                border-right: none !important;
                border-radius: 0 !important;
            }
            .content, .header, .footer {
                padding: 24px 20px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 24px 0; background-color: #FAF8F5;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #FAF8F5;">
        <tr>
            <td align="center">
                <table class="container" role="presentation" width="600" cellpadding="0" cellspacing="0">
                    <!-- Header -->
                    <tr>
                        <td class="header">
                            <a href="{{ config('app.url') }}" style="text-decoration: none;">
                                <h1 class="brand-title">FORMÉ</h1>
                            </a>
                            <div class="brand-sub">Made for every form of movement</div>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td class="content">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="footer">
                            <p style="margin: 0 0 8px 0; font-weight: 500; color: #1a1a1a;">FORMÉ Activewear · Lagos, Nigeria</p>
                            <p style="margin: 0 0 12px 0;">
                                Questions? Reach us at <a href="mailto:hello@forme.ng">hello@forme.ng</a> or WhatsApp <a href="https://wa.me/2347026126265">+234 702 612 6265</a>
                            </p>
                            <p style="margin: 0; font-size: 11px; color: #a19a93;">
                                © {{ date('Y') }} FORMÉ. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
