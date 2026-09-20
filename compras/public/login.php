<?php
require __DIR__.'/../app/core/bootstrap.php';
if (!empty($_SESSION['uid'])) redirect('index.php');
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    $s=$pdo->prepare('SELECT * FROM usuarios WHERE email=? AND activo=1 LIMIT 1'); $s->execute([trim($_POST['email']??'')]); $u=$s->fetch();
    if ($u && password_verify($_POST['password']??'', $u['password_hash'])) { session_regenerate_id(true); $_SESSION['uid']=$u['id']; $_SESSION['nombre']=$u['nombre']; redirect('index.php'); }
    $error='Correo o contraseña incorrectos.';
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Acceso — Colibrí Compras</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light"><div class="container py-5" style="max-width:440px"><div class="card"><div class="card-body p-4"><h1 class="h3 mb-4">🦋 Colibrí Compras</h1><?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif?><form method="post"><input type="hidden" name="csrf" value="<?=csrf()?>"><label class="form-label">Correo</label><input class="form-control mb-3" type="email" name="email" required autofocus><label class="form-label">Contraseña</label><input class="form-control mb-4" type="password" name="password" required><button class="btn btn-primary w-100">Entrar</button></form></div></div></div></body></html>
