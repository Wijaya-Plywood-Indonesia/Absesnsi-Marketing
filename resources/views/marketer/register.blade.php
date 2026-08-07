<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Marketer — Wijaya Plywood</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --bg-outer: #0b0a08;
            --bg-app: #15130f;
            --surface: #201d17;
            --surface-2: #2a271f;
            --border: #3c372a;
            --accent: #f2a93b;
            --accent-ink: #1c1509;
            --accent-soft: #3a2f18;
            --danger: #c1502e;
            --text: #ede8dc;
            --text-muted: #a39a86;
            --radius: 14px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: var(--bg-outer);
            background-image:
                radial-gradient(circle at 20% 15%, #1a1712 0%, transparent 55%),
                radial-gradient(circle at 85% 80%, #171410 0%, transparent 50%);
            font-family: 'Inter', sans-serif;
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 16px;
        }

        .login-card {
            width: 390px;
            max-width: 100%;
            background: var(--bg-app);
            border-radius: 28px;
            border: 1px solid #423d2f;
            box-shadow: 0 30px 60px -20px rgba(0, 0, 0, .7), 0 0 0 8px #060504;
            padding: 32px 24px;
            display: flex;
            flex-direction: column;
        }

        .logo-area {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-area h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--text);
            margin-top: 8px;
        }

        .logo-area p {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .badge {
            display: inline-block;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--accent);
            background: var(--accent-soft);
            padding: 4px 10px;
            border-radius: 6px;
            margin-bottom: 8px;
        }

        .field {
            margin-bottom: 20px;
        }

        .field label {
            display: block;
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .field input {
            width: 100%;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 13px 14px;
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            transition: border-color 0.2s;
        }

        .field input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .field small {
            display: block;
            margin-top: 6px;
            font-size: 11.5px;
            color: var(--text-muted);
        }

        .btn {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 15px;
            border-radius: 12px;
            border: none;
            padding: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            background: var(--accent);
            color: var(--accent-ink);
            margin-top: 10px;
            transition: transform 0.1s, background 0.2s;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .error-box {
            background: rgba(193, 80, 46, 0.15);
            border: 1px solid var(--danger);
            color: #f7a892;
            padding: 12px;
            border-radius: 10px;
            font-size: 13.5px;
            margin-bottom: 20px;
        }

        .error-box ul {
            list-style: none;
        }

        .footer-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .footer-link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="logo-area">
            <span class="badge">Sales Motoris</span>
            <h1>Daftar Akun</h1>
            <p>Akun Anda akan diverifikasi admin sebelum bisa digunakan</p>
        </div>

        @if ($errors->any())
            <div class="error-box">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST">
            @csrf
            <div class="field">
                <label for="name">Nama Lengkap</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                    placeholder="Nama Anda">
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                    placeholder="nama@email.com">
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
                <small>Minimal 8 karakter</small>
            </div>

            <div class="field">
                <label for="password_confirmation">Konfirmasi Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                    placeholder="••••••••">
            </div>

            <button type="submit" class="btn">Daftar</button>
        </form>

        <div class="footer-link">
            Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>
        </div>
    </div>

</body>

</html>
