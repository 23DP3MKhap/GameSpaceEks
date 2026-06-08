<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-pasta verifikācija</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: #0a0a0a;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding: 40px 16px;
        }
        .wrapper {
            max-width: 480px;
            margin: 0 auto;
        }
        .logo {
            font-size: 13px;
            letter-spacing: 6px;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 32px;
        }
        .card {
            background: #141414;
            border: 1px solid #2a2a2a;
            border-radius: 16px;
            padding: 40px;
        }
        .title {
            font-size: 20px;
            font-weight: 500;
            color: #f5f5f5;
            margin-bottom: 12px;
            letter-spacing: 0.02em;
        }
        .text {
            font-size: 14px;
            color: #aaa;
            line-height: 1.7;
            margin-bottom: 32px;
        }
        .divider {
            height: 1px;
            background: #2a2a2a;
            margin-bottom: 32px;
        }
        .code-label {
            font-size: 10px;
            letter-spacing: 3px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 14px;
        }
        .code {
            font-size: 44px;
            font-weight: 300;
            letter-spacing: 16px;
            color: #ffffff;
            margin-bottom: 10px;
        }
        .footer {
            margin-top: 28px;
            font-size: 12px;
            color: #444;
            line-height: 1.7;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="logo">Gamespace</div>
        <div class="card">
            <div class="title">Apstipriniet e-pastu</div>
            <p class="text">
                Paldies, ka reģistrējāties. Lai pabeigtu konta izveidi,
                ievadiet zemāk norādīto kodu verifikācijas logā.
            </p>
            <div class="divider"></div>
            <div class="code-label">Verifikācijas kods</div>
            <div class="code">{{ $code }}</div>
        </div>
        <div class="footer">
            Ja jūs nereģistrējāties GameSpace, ignorējiet šo ziņojumu.<br>
            &copy; 2026 GameSpace
        </div>
    </div>
</body>
</html>