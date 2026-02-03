<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zweryfikuj swój adres e-mail</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            text-align: center;
        }

        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
        }

        .content {
            padding: 40px 30px;
        }

        .content h2 {
            color: #333333;
            font-size: 24px;
            margin-top: 0;
        }

        .content p {
            color: #666666;
            font-size: 16px;
            margin: 15px 0;
        }

        .button-container {
            text-align: center;
            margin: 35px 0;
        }

        .verify-button {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
            box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .verify-button:hover {
            box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
            transform: translateY(-1px);
        }

        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
        }

        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #555555;
        }

        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }

        .footer p {
            color: #999999;
            font-size: 14px;
            margin: 5px 0;
        }

        .link-text {
            word-break: break-all;
            color: #667eea;
            font-size: 12px;
            margin-top: 20px;
        }

        @media only screen and (max-width: 600px) {
            .content {
                padding: 30px 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .verify-button {
                padding: 12px 30px;
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="header">
            <h1>{{ $appName }}</h1>
        </div>

        <div class="content">
            <h2>Witaj, {{ $user->first_name }}! 👋</h2>

            <p>Dziękujemy za rejestrację w {{ $appName }}. Cieszymy się, że dołączasz do naszej społeczności!</p>

            <p>Aby rozpocząć i zapewnić bezpieczeństwo Twojego konta, zweryfikuj swój adres e-mail, klikając przycisk
                poniżej:</p>

            <div class="button-container">
                <a href="{{ $verificationUrl }}" class="verify-button">Zweryfikuj adres e-mail</a>
            </div>

            <div class="info-box">
                <p><strong>⏱️ Ważne:</strong> Ten link weryfikacyjny wygaśnie za
                    {{ config('auth.verification.expire', 60) }} minut ze względów bezpieczeństwa.</p>
            </div>

            <p>Jeśli nie zakładałeś konta w {{ $appName }}, zignoruj tę wiadomość — nie musisz podejmować żadnych
                działań.</p>

            <p>Jeśli masz problem z kliknięciem przycisku, skopiuj i wklej poniższy link do przeglądarki:</p>

            <p class="link-text">{{ $verificationUrl }}</p>
        </div>

        <div class="footer">
            <p><strong>{{ $appName }}</strong></p>
            <p>To jest automatyczna wiadomość e-mail, proszę nie odpowiadać.</p>
            <p>&copy; {{ date('Y') }} {{ $appName }}. Wszelkie prawa zastrzeżone.</p>
        </div>
    </div>
</body>

</html>
