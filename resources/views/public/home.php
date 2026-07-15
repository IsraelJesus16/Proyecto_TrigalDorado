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
    <nav class="navbar navbar--transparent" id="navbar" role="navigation" aria-label="Navegación principal">
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

    <!-- ── HERO: Bento Box Layout ─────────────────────────────────────── -->
    <section class="hero" id="inicio" aria-labelledby="hero-title">

        <!-- Orbes de fondo animados -->
        <div class="hero__orb hero__orb--1" aria-hidden="true"></div>
        <div class="hero__orb hero__orb--2" aria-hidden="true"></div>

        <div class="hero__grid">

            <!-- Celda principal: Texto -->
            <article class="hero__cell hero__cell--main" data-aos="zoom-in" data-aos-duration="1000">
                <div>
                    <p class="hero__eyebrow" aria-label="Etiqueta de presentación">
                        <box-icon name='leaf' type='solid' color='var(--color-primary)' size='sm' animation='flashing-hover'></box-icon> Tradición · Innovación · Sabor
                    </p>

                    <h1 class="hero__title" id="hero-title">
                        El pan que nutre
                        <span class="highlight">generaciones</span>
                    </h1>

                    <p class="hero__desc">
                        Fundada en 2016, llevamos la técnica artesanal del <em>doble horneado</em>
                        — heredada de la familia Mendoza — a escala industrial.
                        Galletas, ponqués y panes de larga duración para toda Venezuela.
                    </p>

                    <div class="hero__actions">
                        <a href="#catalogo" class="btn btn--primary">
                            <box-icon name='store-alt' color='var(--color-bg-dark)' animation='tada-hover'></box-icon> Ver Catálogo
                        </a>
                        <a href="#nosotros" class="btn btn--ghost">
                            Nuestra Historia
                        </a>
                    </div>

                    <div class="hero__stats">
                        <div>
                            <div class="hero__stat-num">2016</div>
                            <div class="hero__stat-label">Año de fundación</div>
                        </div>
                        <div>
                            <div class="hero__stat-num">3+</div>
                            <div class="hero__stat-label">Líneas de producción</div>
                        </div>
                        <div>
                            <div class="hero__stat-num">100%</div>
                            <div class="hero__stat-label">Artesanal industrial</div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- Celda de imagen Hero -->
            <figure class="hero__cell hero__cell--image" data-aos="fade-left" data-aos-duration="1200">
                <img src="<?php echo BASE_URL; ?>assets/img/hero-bg.jpg"
                     alt="Productos El Trigal Dorado — Galletas, ponqués y panes artesanales recién horneados"
                     loading="eager"
                     width="600" height="750">
            </figure>

            <!-- Mini celdas del Bento -->
            <article class="hero__cell hero__cell--mini" data-aos="fade-up" data-aos-delay="200">
                <p class="mini-label">Técnica</p>
                <p class="mini-value">Bis Cotus</p>
                <p class="mini-desc">Doble horneado romano adaptado a producción continua moderna.</p>
            </article>

            <article class="hero__cell hero__cell--mini hero__cell--wide" data-aos="fade-up" data-aos-delay="400">
                <div>
                    <p class="mini-label">Cobertura</p>
                    <p class="mini-value">Edo. Lara</p>
                </div>
                <p class="mini-desc" style="max-width: 400px; text-align: right;">Distribución a supermercados y puntos de venta en Barquisimeto y zona metropolitana.</p>
            </article>

        </div><!-- /hero__grid -->

        <!-- Indicador de scroll -->
        <div class="scroll-indicator" aria-hidden="true">
            <span class="scroll-indicator__text">Explorar</span>
            <div class="scroll-indicator__arrow"></div>
        </div>

    </section><!-- /hero -->

    <!-- ── PARALLAX NARRATIVO 1: Despertar ────────────────────────────── -->
    <section class="parallax-section parallax-bg-wheat" aria-label="Introducción a nuestros trigos">
        <div class="parallax-content" data-aos="fade-up" data-aos-duration="1200">
            <box-icon name='sun' type='solid' color='var(--color-primary)' size='md' animation='spin-hover'></box-icon>
            <h2>Despierta con el aroma a tradición</h2>
            <p>Seleccionamos los mejores trigos bajo el sol larense para llevar a tu mesa un sabor auténtico que evoca los recuerdos de la panadería de antaño, fusionado con la calidad de hoy.</p>
        </div>
    </section>
    <!-- ── CATÁLOGO DE PRODUCTOS ──────────────────────────────────────── -->
    <section class="catalog" id="catalogo" aria-labelledby="catalogo-title">
        <div class="container">

            <header class="catalog__header" data-aos="fade-up">
                <p class="section-eyebrow">
                    <box-icon name='package' type='solid' color='var(--color-primary)' size='sm'></box-icon> Nuestros Productos
                </p>
                <h2 class="section-title" id="catalogo-title">
                    Catálogo El Trigal Dorado
                </h2>
                <p class="section-desc">
                    Descubre nuestra línea completa de productos. Disponibles para distribución B2B y B2C.
                    Inicia sesión para realizar tu pedido.
                </p>
            </header>

            <!-- Filtros de categoría -->
            <nav class="catalog__filters" aria-label="Filtrar por categoría" data-aos="fade-up" data-aos-delay="100">
                <button class="filter-btn active" id="filter-todos" data-filtro="todos">
                    Todos
                </button>
                <?php foreach ($categoriasFiltro ?? [] as $nombreCat): ?>
                    <button class="filter-btn"
                            id="filter-<?php echo strtolower(str_replace(' ', '-', htmlspecialchars($nombreCat))); ?>"
                            data-filtro="<?php echo htmlspecialchars($nombreCat); ?>">
                        <?php echo htmlspecialchars($nombreCat); ?>
                    </button>
                <?php endforeach; ?>
            </nav>

            <!-- Grid de productos -->
            <div class="catalog__grid" id="catalogo-grid" role="list">
                <?php
                $delay = 0;
                foreach ($catalogo ?? [] as $categoria => $productos):
                    foreach ($productos as $producto):
                        $esFeatured = $producto['destacado'] ?? 0;
                        $stock      = (int) ($producto['stock'] ?? 0);
                        $stockMin   = (int) ($producto['stock_minimo'] ?? 10);
                        $stockClass = $stock <= 0 ? 'out' : ($stock <= $stockMin ? 'low' : 'ok');
                        $stockLabel = $stock <= 0 ? 'Agotado' : ($stock <= $stockMin ? "Últimas {$stock} uds." : "En stock");
                ?>
                <article class="card <?php echo $esFeatured ? 'card--featured' : ''; ?>"
                         data-categoria="<?php echo htmlspecialchars($categoria); ?>"
                         data-aos="fade-up"
                         data-aos-delay="<?php echo $delay; ?>"
                         data-aos-duration="900"
                         role="listitem">

                    <!-- Badge de categoría -->
                    <div class="card__badge"><?php echo htmlspecialchars($categoria); ?></div>

                    <!-- Imagen del producto -->
                    <div class="card__img-wrap">
                        <img class="card__img"
                             src="<?php echo empty($producto['imagen_url']) ? BASE_URL . 'assets/img/placeholder.png' : BASE_URL . 'assets/img/productos/' . $producto['imagen_url']; ?>"
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
                                data-imagen="<?php echo empty($producto['imagen_url']) ? BASE_URL . 'assets/img/placeholder.png' : BASE_URL . 'assets/img/productos/' . $producto['imagen_url']; ?>"
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
                endforeach;
                ?>

                <!-- Estado vacío si no hay productos -->
                <?php if (empty($catalogo)): ?>
                <div style="grid-column:1/-1; text-align:center; padding:4rem; color:var(--color-text-muted);"
                     data-aos="fade-up">
                    <div style="margin-bottom:1rem;">
                        <box-icon name='package' size='lg' color='var(--color-text-muted)' animation='fade-up-hover'></box-icon>
                    </div>
                    <p style="font-family:var(--font-admin); font-size:1rem; font-weight:600;">
                        Catálogo en preparación
                    </p>
                    <p style="font-size:0.875rem; margin-top:0.5rem;">
                        Próximamente nuestros productos estarán disponibles.
                    </p>
                </div>
                <?php endif; ?>

            </div><!-- /catalog__grid -->

            <?php if ($isHome ?? true): ?>
            <div style="text-align: center; margin-top: 3rem;" data-aos="fade-up">
                <a href="<?php echo BASE_URL; ?>?page=Catalogo" class="btn btn--primary" style="display: inline-flex; align-items: center; gap: 0.5rem; border-radius: 99px; padding: 0.8rem 2rem; font-weight: 600;">
                    Ver Catálogo Completo <box-icon name='right-arrow-alt' color='var(--color-bg)' size='sm'></box-icon>
                </a>
            </div>
            <?php endif; ?>

        </div>
    </section><!-- /catalog -->

    <!-- ── PARALLAX NARRATIVO 2: El Proceso ───────────────────────────── -->
    <section class="parallax-section parallax-bg-oven" aria-label="Nuestro proceso de horneado">
        <div class="parallax-content" data-aos="zoom-in" data-aos-duration="1200">
            <box-icon name='hot' type='solid' color='var(--color-primary)' size='md' animation='flashing-hover'></box-icon>
            <h2>Doble horneado para un crujido inigualable</h2>
            <p>Nuestra técnica <em>bis cotus</em> asegura que cada galleta y cada pan alcance la textura perfecta: dorado, crujiente por fuera y lleno de sabor por dentro. Una promesa de durabilidad y calidad en cada bocado.</p>
        </div>
    </section>
    <!-- ── SECCIÓN NOSOTROS ───────────────────────────────────────────── -->
    <section class="about" id="nosotros" aria-labelledby="nosotros-title">
        <div class="container">
            <div class="about__grid">

                <!-- Imagen -->
                <figure class="about__img-wrap" data-aos="fade-right">
                    <img class="about__img"
                         src="<?php echo BASE_URL; ?>assets/img/hero-bg.jpg"
                         alt="Familia Mendoza — Fundadores de El Trigal Dorado, tradición panadera venezolana"
                         loading="lazy"
                         width="480" height="600">
                </figure>

                <!-- Contenido -->
                <div class="about__content" data-aos="fade-left" data-aos-delay="150">
                    <p class="section-eyebrow about__section-eyebrow">
                        <box-icon name='book-open' type='solid' color='var(--color-primary)' size='sm'></box-icon> Nuestra Historia
                    </p>
                    <h2 class="about__title" id="nosotros-title">
                        Del horno artesanal a la línea industrial
                    </h2>

                    <p class="about__text">
                        En 2016, la familia Mendoza fundó Industrias Alimenticias El Trigal Dorado, C.A.,
                        llevando su técnica ancestral de <strong>doble horneado</strong> — conocida en latín
                        como <em>bis cotus</em> — desde el horno de leña familiar hasta una planta de
                        producción continua en Barquisimeto, Estado Lara.
                    </p>
                    <p class="about__text">
                        Hoy producimos galletas, ponqués y panes de larga duración con los más altos
                        estándares de inocuidad alimentaria, atendiendo cadenas de supermercados y
                        distribuidores a lo largo de Venezuela.
                    </p>

                    <div>
                        <div class="about__feature">
                            <div class="about__feature-icon">
                                <box-icon name='hot' type='solid' color='var(--color-primary)' animation='flashing-hover'></box-icon>
                            </div>
                            <div>
                                <p class="about__feature-title">Técnica Bis Cotus</p>
                                <p class="about__feature-text">Doble horneado romano adaptado a líneas de producción continua para lograr la textura y durabilidad únicas que nos distinguen.</p>
                            </div>
                        </div>

                        <div class="about__feature">
                            <div class="about__feature-icon">
                                <box-icon name='buildings' type='solid' color='var(--color-primary)' animation='tada-hover'></box-icon>
                            </div>
                            <div>
                                <p class="about__feature-title">Planta Industrial Moderna</p>
                                <p class="about__feature-text">Instalaciones en Barquisimeto con líneas automatizadas que garantizan consistencia y volumen sin sacrificar la calidad artesanal.</p>
                            </div>
                        </div>

                        <div class="about__feature">
                            <div class="about__feature-icon">
                                <box-icon name='leaf' type='solid' color='var(--color-primary)' animation='tada-hover'></box-icon>
                            </div>
                            <div>
                                <p class="about__feature-title">Calidad e Inocuidad</p>
                                <p class="about__feature-text">Control estricto de materias primas, trazabilidad total del proceso y cumplimiento de las normativas venezolanas de seguridad alimentaria.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section><!-- /nosotros -->


    <!-- ── SECCIÓN CONTACTO ───────────────────────────────────────────── -->
    <section id="contacto" style="padding: var(--space-3xl) 0; background: var(--color-bg-warm);" aria-labelledby="contacto-title">
        <div class="container">
            <div data-aos="fade-up" style="text-align:center;">
                <p class="section-eyebrow">
                    <box-icon name='phone-call' type='solid' color='var(--color-primary)' size='sm' animation='tada-hover'></box-icon> Contáctenos
                </p>
                <h2 class="section-title" id="contacto-title" style="margin-bottom: var(--space-md);">
                    ¿Interesado en nuestros productos?
                </h2>
                <p class="section-desc">
                    Comunícate con nuestro equipo de ventas para conocer precios de distribución,
                    condiciones comerciales y disponibilidad de inventario. Visítanos en nuestra sede en Barquisimeto.
                </p>
            </div>
            
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-xl); margin-top: var(--space-2xl);">
                <div data-aos="fade-right">
                    <h3 style="font-family:var(--font-public); margin-bottom: 1rem; color:var(--color-secondary);">Nuestra Ubicación</h3>
                    <!-- Google Maps Iframe de Barquisimeto -->
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d125745.3621516246!2d-69.41400877073719!3d10.063711904576363!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8e8767137f26d3f7%3A0x6b19a9ebc379ea!2sBarquisimeto%2C%20Lara%2C%20Venezuela!5e0!3m2!1ses!2ses!4v1700000000000!5m2!1ses!2ses" 
                            width="100%" height="300" style="border:0; border-radius: var(--radius-md); box-shadow: var(--shadow-sm);" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                
                <div style="display:flex; flex-direction:column; justify-content:center; gap: var(--space-lg);" data-aos="fade-left">
                    <div style="display:flex; align-items:center; gap:15px; background:#fff; padding:20px; border-radius:var(--radius-md); box-shadow:var(--shadow-sm);">
                        <box-icon name='map' color='var(--color-primary)' size='md'></box-icon>
                        <div>
                            <strong style="display:block; color:var(--color-secondary);">Dirección Planta</strong>
                            <span style="color:var(--color-text-muted); font-size:0.9rem;">Zona Industrial I, Barquisimeto, Edo. Lara, Venezuela.</span>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:15px; background:#fff; padding:20px; border-radius:var(--radius-md); box-shadow:var(--shadow-sm);">
                        <box-icon name='envelope' color='var(--color-primary)' size='md'></box-icon>
                        <div>
                            <strong style="display:block; color:var(--color-secondary);">Correo Electrónico</strong>
                            <span style="color:var(--color-text-muted); font-size:0.9rem;">ventas@trigaldorado.com</span>
                        </div>
                    </div>
                    
                    <div style="display:flex; flex-wrap:wrap; gap: var(--space-md); margin-top: 1rem;">
                        <a href="https://wa.me/584140000000" target="_blank" class="btn" style="flex:1; min-width:130px; justify-content:center; background-color: #25D366; color: white; border: none; box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);">
                            <box-icon type='logo' name='whatsapp' color='white' animation='tada-hover'></box-icon> WhatsApp
                        </a>
                        <a href="tel:+582512000000" class="btn btn--primary" style="flex:1; min-width:130px; justify-content:center;">
                            <box-icon name='phone' color='var(--color-bg-dark)' animation='tada-hover'></box-icon> Llamar
                        </a>
                        <a href="<?php echo BASE_URL; ?>?page=Pedido" class="btn btn--ghost" style="flex:1; min-width:130px; justify-content:center; border-color:var(--color-primary); color:var(--color-primary);">
                            <box-icon name='shopping-bag' color='var(--color-primary)' animation='tada-hover'></box-icon> Hacer Pedido
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

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
    const onScroll = () => {
        const scrolled = window.scrollY > 60;
        navbar?.classList.toggle('navbar--scrolled',     scrolled);
        navbar?.classList.toggle('navbar--transparent', !scrolled);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll(); // Ejecutar al cargar

    // ── Page Loader ────────────────────────────────────────────────────────
    window.addEventListener('load', () => {
        setTimeout(() => {
            document.querySelector('#page-loader')?.classList.add('loaded');
        }, 800);
    });

    // ── Carrito: inicializar interceptor ──────────────────────────────────
    initCarrito();

    // ── Filtros del catálogo (JavaScript DOM puro) ─────────────────────────
    const filtros   = document.querySelectorAll('.filter-btn');
    const tarjetas  = document.querySelectorAll('.card');

    filtros.forEach(btn => {
        btn.addEventListener('click', () => {
            // Actualizar estado activo
            filtros.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const filtro = btn.dataset.filtro;
            
            // Re-trigger AOS animations on filter
            let visibleCount = 0;
            tarjetas.forEach(card => {
                const cat = card.dataset.categoria;
                const visible = filtro === 'todos' || cat === filtro;
                
                if (visible) {
                    card.style.display = '';
                    card.classList.remove('aos-animate');
                    
                    // Force reflow and re-add class for animation
                    void card.offsetWidth; 
                    
                    setTimeout(() => {
                        card.classList.add('aos-animate');
                    }, visibleCount * 50); // Stagger effect
                    
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });


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

    // ── ScrollSpy dinámico (Actualizar enlaces del navbar al hacer scroll) ──
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.navbar__nav-link');
    
    const observerOptions = {
        root: null,
        rootMargin: '-20% 0px -70% 0px', // Ajustado para que marque activo cuando la sección llega al top
        threshold: 0
    };

    const sectionObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.getAttribute('id');
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.getAttribute('href') === `#${id}`) {
                        link.classList.add('active');
                    }
                });
            }
        });
    }, observerOptions);

    sections.forEach(sec => sectionObserver.observe(sec));

    // ── Interacción de los Orbes (Mouse Parallax) ───────────────────────────
    const hero = document.querySelector('.hero');
    const orbs = document.querySelectorAll('.hero__orb');
    if (hero) {
        hero.addEventListener('mousemove', (e) => {
            const x = e.clientX / window.innerWidth - 0.5;
            const y = e.clientY / window.innerHeight - 0.5;
            
            orbs[0].style.transform = `translate(${x * 30}px, ${y * 30}px)`;
            orbs[1].style.transform = `translate(${x * -40}px, ${y * -40}px)`;
        });
    }

</script>

</body>
</html>
