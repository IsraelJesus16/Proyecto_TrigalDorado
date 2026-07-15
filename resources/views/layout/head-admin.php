<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo ?? 'Panel de Control — El Trigal Dorado'; ?></title>

    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/img/logo.png">

    <!-- Boxicons (local) -->
    <script src="<?php echo BASE_URL; ?>assets/js/vendor/boxicons.js"></script>

    <!-- DataTables CSS (local) — cargado PRIMERO para que admin.css lo sobrescriba -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/vendor/dataTables.min.css">

    <!-- SweetAlert2 CSS (local) -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/vendor/sweetalert2.min.css">

    <!-- Fuentes locales (Inter + Merriweather) — sin Google Fonts CDN -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/fonts.css">

    <!-- CSS del Admin — cargado AL FINAL, gana en cascada sobre DataTables -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css?v=<?php echo time(); ?>">

    <!-- Variables JS Globales -->
    <script>
        window.BASE_URL        = '<?php echo BASE_URL; ?>';
        window.CSRF_TOKEN      = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
        window.CURRENT_USER_ID = '<?php echo $_SESSION['usuario']['id_usuario'] ?? ''; ?>';
        window.ROOT_USER_ID    = 'USR-ADMIN-00000001';
    </script>
</head>
<body class="admin-body">

    <!-- Loader Admin -->
    <div class="page-loader" id="admin-loader" style="background:var(--color-bg);">
        <img class="page-loader__logo" src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="Cargando">
    </div>

    <!-- Contenedor Principal (Flex row) -->

