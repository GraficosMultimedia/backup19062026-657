<?php
function head(string $title): void {
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($title)?> — Colibrí Compras</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body{font-size:.95rem}.sidebar{min-height:calc(100vh - 56px)}.nav-link{color:#dee2e6;border-radius:.5rem;margin-bottom:3px}.nav-link:hover,.nav-link.active{background:#343a40;color:#fff}.card{box-shadow:0 1px 3px rgba(0,0,0,.08)}.table td,.table th{vertical-align:middle}.money{white-space:nowrap}.status{font-size:.78rem;padding:.35rem .6rem;border-radius:999px;font-weight:700}.status-pagada{background:#d1e7dd;color:#0f5132}.status-pendiente{background:#fff3cd;color:#664d03}.status-parcial{background:#cff4fc;color:#055160}.status-cancelada{background:#f8d7da;color:#842029}
</style></head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark px-3"><a class="navbar-brand fw-semibold" href="index.php">🦋 Colibrí Compras</a><div class="text-white small"><?=e($_SESSION['nombre'] ?? '')?></div></nav>
<div class="container-fluid"><div class="row">
<aside class="col-lg-2 col-md-3 bg-dark sidebar p-3">
<nav class="nav flex-column">
<a class="nav-link" href="index.php">Dashboard</a>
<a class="nav-link" href="compras.php">Compras</a>
<a class="nav-link" href="pagos.php">Pagos</a>
<a class="nav-link" href="clientes.php">Clientes</a>
<a class="nav-link" href="xml.php">Importar XML</a>
<a class="nav-link" href="productos.php">Productos</a>
<a class="nav-link" href="proveedores.php">Proveedores</a>
<a class="nav-link" href="trabajos.php">Trabajos / Costos</a>
<a class="nav-link" href="calidad.php">Calidad</a>
<a class="nav-link" href="precios.php">Precios</a>
<hr class="border-secondary">
<a class="nav-link" href="logout.php">Cerrar sesión</a>
</nav></aside>
<main class="col-lg-10 col-md-9 p-4">
<?php if (!empty($_SESSION['flash'])) { $f=$_SESSION['flash']; unset($_SESSION['flash']); ?>
<div class="alert alert-<?=e($f[1])?> alert-dismissible fade show" role="alert"><?=e($f[0])?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php } ?>
<h1 class="h3 mb-4"><?=e($title)?></h1>
<?php }
function foot(): void { ?>
</main></div></div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>
<?php }
