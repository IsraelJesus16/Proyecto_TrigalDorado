<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Página no encontrada | El Trigal Dorado</title>
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL ?? '/'; ?>assets/img/logo.png">
    <!-- Google Fonts (local) -->
    <link rel="stylesheet" href="<?php echo BASE_URL ?? '/'; ?>assets/css/fonts.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #1C1209;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }
        .error-code {
            font-family: 'Merriweather', serif;
            font-size: clamp(6rem, 15vw, 12rem);
            font-weight: 900;
            background: linear-gradient(135deg, #D4AF37, #F0D060);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 1rem;
        }
        .error-title {
            font-family: 'Merriweather', serif;
            font-size: clamp(1.2rem, 3vw, 1.8rem);
            font-weight: 700;
            margin-bottom: 1rem;
            color: rgba(255,255,255,0.85);
        }
        .error-desc {
            font-size: 0.95rem;
            color: rgba(255,255,255,0.45);
            max-width: 400px;
            margin: 0 auto 2rem;
            line-height: 1.7;
        }
        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            background: linear-gradient(135deg, #D4AF37, #B8941F);
            color: #1C1209;
            font-weight: 700;
            border-radius: 999px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.4s cubic-bezier(0.34,1.56,0.64,1);
            box-shadow: 0 6px 20px rgba(212,175,55,0.4);
        }
        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(212,175,55,0.55);
        }
        .error-emoji { font-size: 3rem; margin-bottom: 1.5rem; display: block; }
    </style>
</head>
<body>
    <main>
        <span class="error-emoji" role="img" aria-label="Pan">🍞</span>
        <div class="error-code" aria-hidden="true">404</div>
        <h1 class="error-title">¡Ups! Esta página no existe</h1>
        <p class="error-desc">
            La ruta que buscas no se encontró en el sistema.
            Puede que haya sido movida o eliminada.
        </p>
        <a href="<?php echo defined('BASE_URL') ? BASE_URL : '/'; ?>" class="btn-home">
            ← Volver al inicio
        </a>
    </main>
</body>
</html>
