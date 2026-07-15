<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Iniciar sesión en el sistema TPS de El Trigal Dorado.">
    <title><?php echo $titulo ?? 'Iniciar Sesión — El Trigal Dorado'; ?></title>

    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/img/logo.png">

    <!-- Google Fonts (local) -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/fonts.css">
    <!-- Boxicons (local) -->
    <script src="<?php echo BASE_URL; ?>assets/js/vendor/boxicons.js"></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --color-primary: #D4AF37;
            --color-secondary: #4A3018;
            --color-bg-dark: #1C1209;
            --color-bg: #F8F9FA;
            --shadow-glow: 0 0 30px rgba(212,175,55,0.35);
            --transition-spring: all 0.4s cubic-bezier(0.34,1.56,0.64,1);
        }
        html { font-size: 16px; -webkit-font-smoothing: antialiased; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--color-bg-dark);
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
        @media (max-width: 768px) { body { grid-template-columns: 1fr; } }

        /* Panel izquierdo: imagen + branding */
        .auth-panel--left {
            background:
                radial-gradient(ellipse 80% 60% at 50% 40%, rgba(212,175,55,0.12) 0%, transparent 70%),
                linear-gradient(160deg, #1C1209 0%, #2D1E0F 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }
        @media (max-width: 768px) { .auth-panel--left { display: none; } }

        .auth-panel--left::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('<?php echo BASE_URL; ?>assets/img/hero-bg.jpg') center/cover no-repeat;
            opacity: 0.15;
        }

        .auth-branding {
            position: relative;
            z-index: 1;
            text-align: center;
            color: #fff;
        }
        .auth-branding__logo {
            width: 100px; height: 100px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 0 0 4px var(--color-primary), var(--shadow-glow);
            margin: 0 auto 2rem;
            display: block;
        }
        .auth-branding__name {
            font-family: 'Merriweather', serif;
            font-size: 2rem;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 0.5rem;
        }
        .auth-branding__name span { color: var(--color-primary); }
        .auth-branding__sub {
            font-size: 0.75rem;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.45);
        }
        .auth-branding__quote {
            margin-top: 3rem;
            padding: 1.5rem;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(12px);
            max-width: 340px;
        }
        .auth-branding__quote p {
            font-family: 'Merriweather', serif;
            font-style: italic;
            font-size: 0.95rem;
            color: rgba(255,255,255,0.7);
            line-height: 1.6;
        }
        .auth-branding__quote cite {
            display: block;
            margin-top: 0.75rem;
            font-family: 'Inter', sans-serif;
            font-size: 0.72rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--color-primary);
        }

        /* Panel derecho: formulario */
        .auth-panel--right {
            background: var(--color-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .auth-form-wrap {
            width: 100%;
            max-width: 420px;
        }

        .auth-form__header {
            margin-bottom: 2.5rem;
        }
        .auth-form__logo-mobile {
            display: none;
            width: 60px; height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1.5rem;
            box-shadow: 0 0 0 3px var(--color-primary);
        }
        @media (max-width: 768px) { .auth-form__logo-mobile { display: block; } }

        .auth-form__title {
            font-family: 'Merriweather', serif;
            font-size: 1.8rem;
            font-weight: 900;
            color: var(--color-secondary);
            margin-bottom: 0.5rem;
        }
        .auth-form__subtitle {
            font-size: 0.875rem;
            color: #7A7063;
            line-height: 1.5;
        }

        .form-group { margin-bottom: 1.25rem; }
        .form-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--color-secondary);
            margin-bottom: 6px;
            letter-spacing: 0.2px;
        }
        .form-control {
            width: 100%;
            padding: 13px 16px;
            border: 1.5px solid rgba(74,48,24,0.12);
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            color: var(--color-secondary);
            background: #fff;
            transition: all 0.25s cubic-bezier(0.25,0.8,0.25,1);
            outline: none;
        }
        .form-control:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 4px rgba(212,175,55,0.15);
        }
        .form-control::placeholder { color: rgba(74,48,24,0.3); }

        .btn-login {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            background: linear-gradient(135deg, #D4AF37 0%, #B8941F 100%);
            color: #1C1209;
            box-shadow: 0 6px 20px rgba(212,175,55,0.4);
            transition: all 0.4s cubic-bezier(0.34,1.56,0.64,1);
            margin-top: 0.5rem;
            letter-spacing: 0.3px;
        }
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(212,175,55,0.55);
        }
        .btn-login:active { transform: translateY(0); }
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .alert-error {
            background: rgba(231,76,60,0.08);
            border: 1px solid rgba(231,76,60,0.3);
            border-left: 4px solid #E74C3C;
            color: #8a2020;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .auth-divider {
            text-align: center;
            font-size: 0.8rem;
            color: #7A7063;
            margin: 1.5rem 0;
        }
        .auth-back {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 0.875rem;
            color: #7A7063;
            text-decoration: none;
            transition: color 0.2s;
        }
        .auth-back:hover { color: var(--color-primary); }

        .spinner {
            display: inline-block;
            width: 14px; height: 14px;
            border: 2px solid rgba(28,18,9,0.25);
            border-top-color: #1C1209;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            vertical-align: middle;
            margin-right: 6px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<!-- Panel Izquierdo — Branding -->
<div class="auth-panel--left" aria-hidden="true">
    <div class="auth-branding">
        <img class="auth-branding__logo"
             src="<?php echo BASE_URL; ?>assets/img/logo.png"
             alt="Logo El Trigal Dorado">
        <h1 class="auth-branding__name">
            El Trigal <span>Dorado</span>
        </h1>
        <p class="auth-branding__sub">Sistema TPS · Panel Administrativo</p>

        <blockquote class="auth-branding__quote">
            <p>"Del trigo artesanal a la línea industrial: la misma pasión, la misma calidad."</p>
            <cite>— Familia Mendoza, Barquisimeto 2016</cite>
        </blockquote>
    </div>
</div>

<!-- Panel Derecho — Formulario -->
<section class="auth-panel--right">
    <main class="auth-form-wrap">

        <!-- Logo móvil -->
        <img class="auth-form__logo-mobile"
             src="<?php echo BASE_URL; ?>assets/img/logo.png"
             alt="Logo El Trigal Dorado">

        <header class="auth-form__header">
            <h2 class="auth-form__title">Bienvenido de vuelta</h2>
            <p class="auth-form__subtitle">
                Ingresa tus credenciales para acceder al panel administrativo.
            </p>
        </header>

        <!-- Alerta de error -->
        <?php if ($error ?? false): ?>
        <div class="alert-error" role="alert" aria-live="assertive">
            <span aria-hidden="true">⚠️</span>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Formulario de login -->
        <form method="POST"
              action="<?php echo BASE_URL; ?>?page=Login"
              id="form-login"
              novalidate>

            <input type="hidden" name="peticion" value="login">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

            <div class="form-group">
                <label class="form-label" for="input-username">
                    Usuario o Cédula
                </label>
                <input class="form-control"
                       type="text"
                       id="input-username"
                       name="username"
                       placeholder="Ej: admin o V-12345678"
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                       autocomplete="username"
                       required
                       autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="input-password">
                    Contraseña
                </label>
                <div style="position: relative;">
                    <input class="form-control"
                           type="password"
                           id="input-password"
                           name="password"
                           placeholder="Tu contraseña"
                           autocomplete="current-password"
                           style="padding-right: 40px;"
                           required>
                    <button type="button" aria-label="Mostrar contraseña"
                            onclick="const p=document.getElementById('input-password'); const isPass = p.type==='password'; p.type=isPass?'text':'password'; this.innerHTML=isPass?`<box-icon name='hide' color='var(--color-secondary)' size='sm'></box-icon>`:`<box-icon name='show' color='var(--color-secondary)' size='sm'></box-icon>`;"
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; display: flex; align-items: center; padding: 0;">
                        <box-icon name='show' color='var(--color-secondary)' size='sm'></box-icon>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login" id="btn-ingresar">
                Ingresar al Sistema
            </button>
        </form>

        <div class="auth-divider">— o —</div>

        <a href="<?php echo BASE_URL; ?>" class="auth-back">
            ← Volver al catálogo público
        </a>

        <p style="text-align:center; margin-top:2rem; font-size:0.72rem; color:rgba(74,48,24,0.3); font-family:'Inter',sans-serif;">
            TPS El Trigal Dorado v1.0 · UNEFA 2026
        </p>

    </main>
</section>

<script>
    // Feedback visual al enviar el formulario
    document.querySelector('#form-login')?.addEventListener('submit', function() {
        const btn = document.querySelector('#btn-ingresar');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Verificando...';
    });
</script>

</body>
</html>
