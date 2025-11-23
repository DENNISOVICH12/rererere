<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;

            background-image: url("https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=2000&q=80");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-color: rgba(0,0,0,0.6);
            background-blend-mode: darken;
        }

        .login-card {
            width: 360px;
            padding: 35px 35px 45px;
            border-radius: 18px;
            background: rgba(0,0,0,0.55);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.25);
            box-shadow: 0 8px 25px rgba(0,0,0,0.45);
            color: #fff;
            text-shadow: 0 2px 6px rgba(0,0,0,0.7);
        }

        .logo {
            font-size: 40px;
            margin-bottom: 6px;
        }

        h2 {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            color: #F8ECE4;
            margin-bottom: 25px;
        }

        .field-wrapper {
            width: 85%;
            margin: auto;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            border-radius: 12px;
            border: none;
            background: rgba(255, 255, 255, 0.88);
            font-size: 14px;
            outline: none;
            text-align: center;
        }

        .btn-container {
            width: 60%;
            margin: 15px auto 0;
        }

        button {
            background: linear-gradient(135deg, #7a1522 0%, #4b0f14 100%);
            border: none;
            color: #F8ECE4;
            padding: 10px;
            width: 100%;
            border-radius: 10px;
            border: 1.4px solid rgba(255,255,255,0.45);
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: 0.35s;
        }

        button:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #9c2030 0%, #65141b 100%);
        }
        button:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .error {
            color: #ffb3b3;
            margin-top: 10px;
            font-size: 14px;
        }
        .sr-only {
  position:absolute;
  left:-10000px;
  top:auto;
  width:1px;
  height:1px;
  overflow:hidden;
}
.sr-only {
            position:absolute;
            left:-9999px;
            width:1px;
            height:1px;
            overflow:hidden;
        }

    </style>
</head>


<body>

    <div class="login-card">
        <div class="logo">🍽️</div>
        <h2>Iniciar Sesión</h2>

        <!-- ARIA-LIVE para errores accesibles -->
        <div aria-live="assertive">
            @if ($errors->any())
                <p class="error">⚠️ {{ $errors->first() }}</p>
            @endif
        </div>

        <form action="{{ route('login.post') }}" method="POST" onsubmit="disableButton()">
            @csrf

            <div class="field-wrapper">
                <label for="usuario">Usuario</label>
                <input id="usuario" type="text" name="usuario" placeholder="Escribe tu usuario" required>

                <label for="password">Contraseña</label>
                <input id="password" type="password" name="password" placeholder="••••••••" required>
            </div>

            <div class="btn-container">
                <button id="login-btn" type="submit">Entrar</button>
            </div>
        </form>

    </div>

    <script>
        function disableButton() {
            const btn = document.getElementById('login-btn');
            btn.disabled = true;
            btn.innerText = "Procesando...";
        }
    </script>

</body>
</html>