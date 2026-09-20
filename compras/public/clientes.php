<?php
require __DIR__.'/../app/core/bootstrap.php';
auth();
require __DIR__.'/../app/core/layout.php';

$ak = akauntingPdo();
$q = trim($_GET['q'] ?? '');
$clientes = akauntingClientes($ak, $q);
head('Clientes');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
 <div><h2 class="h4 mb-1">Clientes</h2><div class="text-secondary">Catálogo en tiempo real desde Akaunting. No se crea una copia local.</div></div>
</div>
<div class="alert alert-info">Fuente: <code>ak4s_contacts</code> de la base de datos de Akaunting, filtrando <code>type = customer</code>, activos y no eliminados.</div>
<form class="card card-body mb-3" method="get">
 <div class="row g-2"><div class="col-md-10"><input class="form-control" name="q" value="<?=e($q)?>" placeholder="Buscar nombre, correo, teléfono, RFC o ciudad..."></div><div class="col-md-2 d-grid"><button class="btn btn-dark">Buscar</button></div></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-striped mb-0">
<thead><tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>RFC</th><th>Dirección</th><th>Ciudad</th><th>Estado</th></tr></thead><tbody>
<?php foreach($clientes as $c): ?><tr>
<td><?= (int)$c['id'] ?></td><td class="fw-semibold"><?=e($c['name'])?></td><td><?=e($c['email']??'')?></td><td><?=e($c['phone']??'')?></td><td><?=e($c['tax_number']??'')?></td><td><?=e($c['address']??'')?></td><td><?=e($c['city']??'')?></td><td><?=e($c['state']??'')?></td>
</tr><?php endforeach; if(!$clientes): ?><tr><td colspan="8" class="text-center text-secondary py-4">No hay clientes que coincidan.</td></tr><?php endif; ?></tbody></table></div></div>
<?php foot(); ?>
