<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline — DentalPortal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0f2d4a 0%, #0a8f8f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 24px;
        }
        .wrap { max-width: 400px; }
        .icon {
            font-size: 5rem;
            margin-bottom: 24px;
            display: block;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        h1 { font-size: 1.8rem; font-weight: 700; margin-bottom: 12px; }
        p { opacity: 0.75; line-height: 1.7; margin-bottom: 28px; }
        .btn {
            display: inline-block;
            background: white;
            color: #0f2d4a;
            padding: 12px 28px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .badge {
            display: inline-block;
            background: rgba(255,255,255,0.15);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.78rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <span class="icon">🦷</span>
        <div class="badge">DentalPortal</div>
        <h1>You're Offline</h1>
        <p>DentalPortal needs an active internet connection to sync patient data and appointments. Please check your connection and try again.</p>
        <a href="/" class="btn">↺ Try Again</a>
    </div>
</body>
</html>
