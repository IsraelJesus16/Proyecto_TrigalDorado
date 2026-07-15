<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="El Trigal Dorado — Panadería industrial artesanal en Barquisimeto. Galletas, ponqués y panes de larga duración con la técnica tradicional de doble horneado de la familia Mendoza.">
    <meta name="keywords" content="panadería, galletas, ponqués, pan, Barquisimeto, Venezuela, El Trigal Dorado">
    <meta property="og:title" content="El Trigal Dorado — Panadería Industrial Artesanal">
    <meta property="og:description" content="Tradición artesanal venezolana con tecnología industrial moderna.">

    <title><?php echo $titulo ?? 'El Trigal Dorado — Panadería Industrial Artesanal'; ?></title>

    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/img/logo.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>assets/img/logo.png">

    <!-- Google Fonts (local) -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/fonts.css">

    <!-- AOS (Animate On Scroll) (local) -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/vendor/aos.min.css">
    
    <!-- BoxIcons (local) -->
    <script src="<?php echo BASE_URL; ?>assets/js/vendor/boxicons.js"></script>

    <!-- Estilos del sistema -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/public.css?v=<?php echo time(); ?>">

    <!-- Variables globales para JS -->
    <script>
        window.BASE_URL   = '<?php echo BASE_URL; ?>';
        const BASE_URL    = window.BASE_URL;
        window.CSRF_TOKEN = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
        const CSRF_TOKEN  = window.CSRF_TOKEN;
        window.AUTENTICADO = <?php echo ($estaAutenticado ?? false) ? 'true' : 'false'; ?>;
        const AUTENTICADO  = window.AUTENTICADO;
    </script>
</head>
<body>

<!-- ── Loader de página ───────────────────────────────────────────────── -->
<div class="page-loader" id="page-loader" aria-hidden="true">
    <img class="page-loader__logo"
         src="<?php echo BASE_URL; ?>assets/img/logo.png"
         alt="Cargando El Trigal Dorado">
</div>

<!-- ── Overlay del carrito ────────────────────────────────────────────── -->
<div id="cart-overlay"
     style="position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:800;opacity:0;visibility:hidden;transition:all 0.3s ease;backdrop-filter:blur(2px);"
     onclick="document.querySelector('#carrito-drawer').classList.remove('open');this.style.opacity='0';this.style.visibility='hidden';">
</div>

<!-- ════════════════════════════════════════════════════════════════════
     NAVBAR — Navegación pública flotante
     ════════════════════════════════════════════════════════════════════ -->
<header>
    <nav class="navbar navbar--scrolled" id="navbar" role="navigation" aria-label="Navegación principal">
        <div class="navbar__container">

            <!-- Logo + Marca -->
            <a href="<?php echo BASE_URL; ?>" class="navbar__brand" aria-label="El Trigal Dorado — Inicio">
                <img class="navbar__logo"
                     src="<?php echo BASE_URL; ?>assets/img/logo.png"
                     alt="Logo El Trigal Dorado — Espigas de trigo doradas">
                <div class="navbar__brand-name">
                    El Trigal Dorado
                    <span>Desde 2016 · Barquisimeto</span>
                </div>
            </a>

            <!-- Links de navegación -->
            <ul class="navbar__nav" role="list">
                <li><a href="#catalogo" class="navbar__nav-link">Catálogo</a></li>
                <li><a href="#nosotros" class="navbar__nav-link">Nosotros</a></li>
                <li><a href="#contacto" class="navbar__nav-link">Contacto</a></li>
                <?php if ($estaAutenticado ?? false): ?>
                    <li><a href="<?php echo BASE_URL; ?>?page=Dashboard" class="navbar__nav-link">Panel Admin</a></li>
                <?php endif; ?>
            </ul>

            <!-- Acciones del navbar -->
            <div class="navbar__actions">
                <?php if ($estaAutenticado ?? false): ?>
                    <a href="<?php echo BASE_URL; ?>?page=Dashboard" class="btn btn--ghost">
                        <box-icon name='cog' color='currentColor' animation='spin-hover'></box-icon> Panel Admin
                    </a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>?page=Login" class="btn btn--ghost" id="btn-login-nav">
                        Iniciar Sesión
                    </a>
                <?php endif; ?>

                <!-- Botón del carrito con contador -->
                <button class="btn btn--cart" id="btn-abrir-carrito" aria-label="Abrir carrito de compras">
                    <box-icon name='cart' color='var(--color-bg-dark)' animation='tada-hover'></box-icon>
                    <span class="cart-count" id="cart-count" style="display:none;" aria-live="polite">0</span>
                </button>
            </div>

        </div>
    </nav>
</header>

<!-- ════════════════════════════════════════════════════════════════════
     MAIN — Contenido principal
     ════════════════════════════════════════════════════════════════════ -->
<main>

    <!-- ── CATÁLOGO DE PRODUCTOS (Separado por categorías) ──────────────────────────────────────── -->
    <section class="catalog" id="catalogo" aria-labelledby="catalogo-title" style="padding-top: 150px;">
        <div class="container">

            <header class="catalog__header" data-aos="fade-up">
                <p class="section-eyebrow">
                    <box-icon name='package' type='solid' color='var(--color-primary)' size='sm'></box-icon> Nuestro Catálogo Completo
                </p>
                <h1 class="section-title" id="catalogo-title">
                    Explora todos nuestros productos
                </h1>
                <p class="section-desc">
                    Divididos por categorías para tu comodidad.
                </p>
                <div style="margin-top: 1.5rem;">
                    <a href="<?php echo BASE_URL; ?>" class="btn btn--ghost" style="display: inline-flex; align-items: center; gap: 0.5rem; border-color: var(--color-primary); color: var(--color-primary);">
                        <box-icon name='left-arrow-alt' color='var(--color-primary)' size='sm' animation='fade-left-hover'></box-icon> Volver al Inicio
                    </a>
                </div>
            </header>

            <?php if (empty($catalogo)): ?>
                <!-- Estado vacío si no hay productos -->
                <div style="text-align:center; padding:4rem; color:var(--color-text-muted);" data-aos="fade-up">
                    <div style="margin-bottom:1rem;">
                        <box-icon name='package' size='lg' color='var(--color-text-muted)' animation='fade-up-hover'></box-icon>
                    </div>
                    <p style="font-family:var(--font-admin); font-size:1rem; font-weight:600;">Catálogo en preparación</p>
                    <p style="font-size:0.875rem; margin-top:0.5rem;">Próximamente nuestros productos estarán disponibles.</p>
                </div>
            <?php else: ?>
                
                <?php foreach ($catalogo ?? [] as $categoria => $productos): ?>
                    <section class="catalog-category-section" style="margin-bottom: 4rem;">
                        <h2 style="font-family: var(--font-public); color: var(--color-primary); border-bottom: 2px solid var(--color-bg-dark); padding-bottom: 0.5rem; margin-bottom: 2rem;" data-aos="fade-up">
                            <?php echo htmlspecialchars($categoria); ?>
                        </h2>
                        
                        <div class="catalog__grid" role="list">
                            <?php
                            $delay = 0;
                            foreach ($productos as $producto):
                                $esFeatured = $producto['destacado'] ?? 0;
                                $stock      = (int) ($producto['stock'] ?? 0);
                                $stockMin   = (int) ($producto['stock_minimo'] ?? 10);
                                $stockClass = $stock <= 0 ? 'out' : ($stock <= $stockMin ? 'low' : 'ok');
                                $stockLabel = $stock <= 0 ? 'Agotado' : ($stock <= $stockMin ? "Últimas {$stock} uds." : "En stock");
                            ?>
                            <article class="card <?php echo $esFeatured ? 'card--featured' : ''; ?>"
                                     data-aos="fade-up"
                                     data-aos-delay="<?php echo $delay; ?>"
                                     data-aos-duration="900"
                                     role="listitem">

                                <!-- Imagen del producto -->
                                <div class="card__img-wrap">
                                    <img class="card__img"
                                         src="<?php echo BASE_URL . 'assets/img/productos/' . ($producto['imagen_url'] ?? 'placeholder.jpg'); ?>"
                                         alt="<?php echo htmlspecialchars($producto['nombre']); ?> — El Trigal Dorado"
                                         loading="lazy"
                                         width="400" height="300" onerror="this.onerror=null; this.src='<?php echo BASE_URL; ?>assets/img/placeholder.png';">

                                    <!-- Overlay con botón de vista rápida -->
                                    <div class="card__overlay" aria-hidden="true">
                                        <button class="btn btn--ghost"
                                                style="font-size:0.8rem; padding:8px 18px;"
                                                data-accion="vista-rapida"
                                                data-id="<?php echo htmlspecialchars($producto['id_producto']); ?>">
                                            <box-icon name='show' color='currentColor' animation='tada-hover'></box-icon> Vista Rápida
                                        </button>
                                    </div>

                                    <!-- Badge de stock -->
                                    <span class="card__stock card__stock--<?php echo $stockClass; ?>"
                                          aria-label="Disponibilidad: <?php echo $stockLabel; ?>">
                                        <?php echo $stockLabel; ?>
                                    </span>
                                </div>

                                <!-- Cuerpo de la card -->
                                <div class="card__body">
                                    <h3 class="card__title" style="display:flex; align-items:flex-start; gap:8px; justify-content:space-between;">
                                        <span style="flex:1; word-break: break-word; line-height: 1.2;"><?php echo htmlspecialchars($producto['nombre']); ?></span>
                                        <?php if($esFeatured): ?>
                                            <div style="flex-shrink: 0; margin-top: -2px;">
                                                <box-icon name='star' type='solid' color='var(--color-primary)' size='sm' animation='tada-hover' title="Producto Destacado"></box-icon>
                                            </div>
                                        <?php endif; ?>
                                    </h3>
                                    <p class="card__meta">
                                        <?php echo htmlspecialchars($producto['unidad_venta']); ?>
                                        <?php if ($producto['peso_neto']): ?>
                                            · <?php echo $producto['peso_neto']; ?>g
                                        <?php endif; ?>
                                    </p>
                                    <?php if ($producto['descripcion']): ?>
                                        <p class="card__meta" style="line-height:1.5; font-size:0.78rem; color:var(--color-text-muted);">
                                            <?php echo htmlspecialchars(mb_substr($producto['descripcion'], 0, 80)); ?>...
                                        </p>
                                    <?php endif; ?>
                                    <p class="card__price">
                                        <span>Bs.</span>
                                        <?php echo number_format($producto['precio_venta'], 2); ?>
                                    </p>
                                </div>

                                <!-- Footer de la card -->
                                <footer class="card__footer">
                                    <button class="card__btn"
                                            data-accion="agregar-carrito"
                                            data-id="<?php echo htmlspecialchars($producto['id_producto']); ?>"
                                            data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                            data-precio="<?php echo $producto['precio_venta']; ?>"
                                            data-imagen="<?php echo BASE_URL . 'assets/img/productos/' . ($producto['imagen_url'] ?? 'placeholder.jpg'); ?>"
                                            <?php echo $stock <= 0 ? 'disabled aria-disabled="true"' : ''; ?>
                                            aria-label="Añadir <?php echo htmlspecialchars($producto['nombre']); ?> al carrito">
                                        <?php if($stock <= 0): ?>
                                            <box-icon name='x-circle' color='currentColor'></box-icon> Agotado
                                        <?php else: ?>
                                            <box-icon name='cart-add' color='currentColor' animation='tada-hover'></box-icon> Añadir al Carrito
                                        <?php endif; ?>
                                    </button>
                                </footer>

                            </article>
                            <?php
                                    $delay = ($delay + 100) % 500;
                                endforeach;
                            ?>
                        </div>
                    </section>
                <?php endforeach; ?>

            <?php endif; ?>

        </div>
    </section><!-- /catalog -->



</main><!-- /main -->


<!-- ════════════════════════════════════════════════════════════════════
     FOOTER
     ════════════════════════════════════════════════════════════════════ -->
<footer class="footer" id="footer" role="contentinfo">
    <div class="container">
        <div class="footer__grid">

            <!-- Columna marca -->
            <div>
                <p class="footer__brand-name">El Trigal <span>Dorado</span></p>
                <p class="footer__desc">
                    Industrias Alimenticias El Trigal Dorado, C.A. — Barquisimeto, Estado Lara, Venezuela.
                    Llevando la tradición panadera familiar al estándar industrial moderno desde 2016.
                </p>
            </div>

            <!-- Navegación -->
            <nav aria-label="Navegación del footer">
                <p class="footer__heading">Navegación</p>
                <ul class="footer__links" role="list">
                    <li><a href="#inicio">Inicio</a></li>
                    <li><a href="#catalogo">Catálogo</a></li>
                    <li><a href="#nosotros">Nosotros</a></li>
                    <li><a href="#contacto">Contacto</a></li>
                </ul>
            </nav>

            <!-- Productos -->
            <nav aria-label="Categorías de productos">
                <p class="footer__heading">Productos</p>
                <ul class="footer__links" role="list">
                    <li><a href="#catalogo">Galletas Tradicionales</a></li>
                    <li><a href="#catalogo">Ponqués Artesanales</a></li>
                    <li><a href="#catalogo">Panes de Larga Duración</a></li>
                </ul>
            </nav>

            <!-- Acceso -->
            <nav aria-label="Acceso al sistema">
                <p class="footer__heading">Sistema</p>
                <ul class="footer__links" role="list">
                    <li><a href="<?php echo BASE_URL; ?>?page=Login">Iniciar Sesión</a></li>
                    <?php if ($estaAutenticado ?? false): ?>
                        <li><a href="<?php echo BASE_URL; ?>?page=Dashboard">Panel Admin</a></li>
                        <li><a href="<?php echo BASE_URL; ?>?page=Logout">Cerrar Sesión</a></li>
                    <?php endif; ?>
                </ul>
            </nav>

        </div><!-- /footer__grid -->

        <div class="footer__bottom">
            <p class="footer__copy">
                © <?php echo date('Y'); ?> Industrias Alimenticias El Trigal Dorado, C.A. — Todos los derechos reservados.
            </p>
            <p style="font-size:0.72rem; color:rgba(255,255,255,0.2);">
                TPS desarrollado con PHP 8.2 MVC · MySQL · Vanilla JS ES6+
            </p>
        </div>
    </div>
</footer>


<!-- ════════════════════════════════════════════════════════════════════
     MODAL DE AUTENTICACIÓN (Interceptor del carrito)
     ════════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modal-login" role="dialog" aria-modal="true" aria-labelledby="modal-login-title">
    <div class="modal">
        <button class="modal__close" id="btn-cerrar-modal-login" aria-label="Cerrar modal de inicio de sesión">
            <box-icon name='x' color='currentColor'></box-icon>
        </button>

        <img class="modal__logo"
             src="<?php echo BASE_URL; ?>assets/img/logo.png"
             alt="Logo El Trigal Dorado">

        <h2 class="modal__title" id="modal-login-title">Iniciar Sesión</h2>
        <p class="modal__subtitle">Para realizar pedidos, necesitas una cuenta.</p>

        <!-- Error del modal -->
        <div class="form-error" id="modal-login-error" role="alert"></div>

        <form id="form-login-modal" novalidate>
            <div class="form-group">
                <label class="form-label" for="modal-username">Usuario o cédula</label>
                <input class="form-control"
                       type="text"
                       id="modal-username"
                       name="username"
                       placeholder="Ej: jramírez o V-12345678"
                       autocomplete="username"
                       required>
            </div>

            <div class="form-group">
                <label class="form-label" for="modal-password">Contraseña</label>
                <div style="position: relative;">
                    <input class="form-control"
                           type="password"
                           id="modal-password"
                           name="password"
                           placeholder="Tu contraseña"
                           autocomplete="current-password"
                           style="padding-right: 40px;"
                           required>
                    <button type="button" aria-label="Mostrar contraseña"
                            onclick="const p=document.getElementById('modal-password'); const isPass = p.type==='password'; p.type=isPass?'text':'password'; this.innerHTML=isPass?`<box-icon name='hide' color='var(--color-text-muted)' size='sm'></box-icon>`:`<box-icon name='show' color='var(--color-text-muted)' size='sm'></box-icon>`;"
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; display: flex; align-items: center; padding: 0;">
                        <box-icon name='show' color='var(--color-text-muted)' size='sm'></box-icon>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn--primary btn--block" id="btn-login-submit">
                Iniciar Sesión
            </button>
        </form>

        <p style="text-align:center; margin-top:var(--space-md); font-family:var(--font-admin); font-size:0.82rem; color:var(--color-text-muted);">
            ¿No tienes cuenta?
            <a href="#contacto" style="color:var(--color-primary); font-weight:600;" onclick="document.querySelector('#modal-login').classList.remove('open');">Contáctanos</a>
        </p>
    </div>
</div>


<!-- ════════════════════════════════════════════════════════════════════
     DRAWER DEL CARRITO (lateral)
     ════════════════════════════════════════════════════════════════════ -->
<aside class="cart-drawer" id="carrito-drawer" role="complementary" aria-label="Carrito de compras">
    <header class="cart-drawer__header">
        <h2 class="cart-drawer__title">
            <box-icon name='cart' color='currentColor'></box-icon> Mi Carrito
        </h2>
        <button class="btn-action btn-action--delete" id="btn-cerrar-carrito" aria-label="Cerrar carrito" style="background:transparent;border:none;">
            <box-icon name='x' color='currentColor' size='sm'></box-icon>
        </button>
    </header>

    <div class="cart-drawer__body" id="carrito-body" aria-live="polite">
        <div style="text-align:center; padding:3rem 1rem; color:var(--color-text-muted);">
            <div style="margin-bottom:1rem;">
                <box-icon name='cart' size='lg' color='var(--color-text-muted)'></box-icon>
            </div>
            <p style="font-family:var(--font-admin); font-size:0.9rem;">Tu carrito está vacío</p>
        </div>
    </div>

    <footer class="cart-drawer__footer">
        <div class="cart-drawer__total">
            <span>Total</span>
            <span class="cart-drawer__total-amount" id="carrito-total">Bs. 0.00</span>
        </div>
        <button class="btn btn--primary btn--block" id="btn-checkout" disabled>
            Proceder al Pedido →
        </button>
        <p style="font-family:var(--font-admin); font-size:0.72rem; color:var(--color-text-muted); text-align:center;">
            Los precios están en Bolívares (Bs.). El pedido será confirmado por un asesor.
        </p>
    </footer>
</aside>


<!-- ════════════════════════════════════════════════════════════════════
     SCRIPTS (antes del cierre del body para no bloquear el renderizado)
     ════════════════════════════════════════════════════════════════════ -->

<!-- AOS (Animate On Scroll) (local) -->
<script src="<?php echo BASE_URL; ?>assets/js/vendor/aos.min.js"></script>

<!-- Módulo principal del panel público -->
<script type="module">
    import { initCarrito, mostrarToast } from './assets/js/modules/cart.js';

    // ── AOS: Configuración de animaciones de scroll ──────────────────────
    AOS.init({
        duration:  800,
        easing:    'ease-out-cubic',
        once:      true,   // Animar solo la primera vez
        offset:    120,
        delay:     0,
    });

    // ── Navbar: efecto glassmorphism al hacer scroll ──────────────────────
    const navbar = document.querySelector('#navbar');
    // Para la página del catálogo, el navbar siempre está scrolled
    if (navbar) {
        navbar.classList.add('navbar--scrolled');
        navbar.classList.remove('navbar--transparent');
    }

    // ── Page Loader ────────────────────────────────────────────────────────
    window.addEventListener('load', () => {
        setTimeout(() => {
            document.querySelector('#page-loader')?.classList.add('loaded');
        }, 800);
    });

    // ── Carrito: inicializar interceptor ──────────────────────────────────
    initCarrito();


    // ── Smooth scroll para los links del navbar ─────────────────────────────

    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const destino = document.querySelector(link.getAttribute('href'));
            if (destino) {
                destino.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

</script>

</body>
</html>
