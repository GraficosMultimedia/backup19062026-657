<?php
require __DIR__.'/../app/core/bootstrap.php';
auth();
require __DIR__.'/../app/core/layout.php';

ensureTrabajoColumns($pdo);
$ak = akauntingPdo();

// Los clientes se leen DIRECTAMENTE de Akaunting. No se importan ni se duplican.
$qCliente = trim($_GET['q_cliente'] ?? '');
$clientes = akauntingClientes($ak, $qCliente);

$editId = (int)($_GET['editar'] ?? 0);
$edit = null;
if ($editId > 0) {
    $s = $pdo->prepare('SELECT * FROM trabajos WHERE id=?');
    $s->execute([$editId]);
    $edit = $s->fetch();
    if (!$edit) {
        flash('El trabajo no existe.', 'warning');
        redirect('trabajos.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $accion = $_POST['accion'] ?? 'crear';

    if ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $s = $pdo->prepare('DELETE FROM trabajos WHERE id=?');
            $s->execute([$id]);
            logAction($pdo, 'Eliminar trabajo', 'Trabajo #'.$id);
            flash('Trabajo eliminado.');
        }
        redirect('trabajos.php');
    }

    $clienteId = (int)($_POST['cliente_id'] ?? 0);
    $cliente = $clienteId > 0 ? akauntingCliente($ak, $clienteId) : null;
    if (!$cliente) {
        flash('Selecciona un cliente válido del catálogo de Akaunting.', 'warning');
        redirect('trabajos.php'.($editId ? '?editar='.$editId : ''));
    }

    $clienteNombre = trim($cliente['name'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $venta = (float)($_POST['venta'] ?? 0);
    $mano = (float)($_POST['mano_obra'] ?? 0);
    $otros = (float)($_POST['otros'] ?? 0);
    $estado = $_POST['estado'] ?? 'cotizacion';
    $estados = ['cotizacion','produccion','terminado','entregado'];
    if (!in_array($estado, $estados, true)) $estado = 'cotizacion';

    if ($accion === 'actualizar') {
        $id = (int)($_POST['id'] ?? 0);
        $s = $pdo->prepare('UPDATE trabajos SET akaunting_contact_id=?, cliente=?, descripcion=?, estado=?, precio_venta=?, mano_obra=?, otros_costos=? WHERE id=?');
        $s->execute([$clienteId, $clienteNombre, $descripcion ?: null, $estado, $venta, $mano, $otros, $id]);
        logAction($pdo, 'Actualizar trabajo', 'Trabajo #'.$id);
        flash('Trabajo actualizado correctamente.');
        redirect('trabajos.php');
    }

    // Folio automático, sin escribirlo manualmente.
    $pdo->beginTransaction();
    try {
        $folio = siguienteFolioTrabajo($pdo);
        $s = $pdo->prepare('INSERT INTO trabajos(folio,akaunting_contact_id,cliente,descripcion,estado,precio_venta,mano_obra,otros_costos) VALUES(?,?,?,?,?,?,?,?)');
        $s->execute([$folio, $clienteId, $clienteNombre, $descripcion ?: null, $estado, $venta, $mano, $otros]);
        $newId = (int)$pdo->lastInsertId();
        $pdo->commit();
        logAction($pdo, 'Crear trabajo', $folio.' · '.$clienteNombre);
        flash('Trabajo '.$folio.' creado.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Crear trabajo: '.$e->getMessage());
        flash('No se pudo crear el trabajo.', 'danger');
    }
    redirect('trabajos.php');
}

$rows = $pdo->query("SELECT t.*, COALESCE((SELECT SUM(costo_total) FROM trabajo_materiales tm WHERE tm.trabajo_id=t.id),0) materiales
                     FROM trabajos t ORDER BY t.id DESC")->fetchAll();

head('Trabajos / Costos');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h4 mb-1">Trabajos / Costos</h2>
        <div class="text-secondary">Clientes tomados directamente de Akaunting. No se importan.</div>
    </div>
    <a href="clientes.php" class="btn btn-outline-secondary">Ver clientes</a>
</div>

<div class="card card-body mb-3">
    <h2 class="h5 mb-3"><?= $edit ? 'Editar trabajo '.$edit['folio'] : 'Nuevo trabajo' ?></h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?=csrf()?>">
        <input type="hidden" name="accion" value="<?= $edit ? 'actualizar' : 'crear' ?>">
        <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
        <div class="row g-3">
            <div class="col-lg-5">
                <label class="form-label">Cliente de Akaunting</label>
                <select name="cliente_id" class="form-select" required>
                    <option value="">Seleccionar cliente...</option>
                    <?php foreach ($clientes as $c): ?>
                        <?php $label = trim($c['name'] ?? ''); $extra=[]; if($c['phone'])$extra[]=$c['phone']; if($c['email'])$extra[]=$c['email']; ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $edit && (int)$edit['akaunting_contact_id']===(int)$c['id'] ? 'selected' : '' ?>>
                            <?=e($label)?><?= $extra ? ' | '.e(implode(' | ', $extra)) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">ID de contacto Akaunting: <?= $edit ? (int)$edit['akaunting_contact_id'] : 'se asigna al seleccionar' ?></div>
            </div>
            <div class="col-lg-4">
                <label class="form-label">Descripción del trabajo</label>
                <input name="descripcion" class="form-control" value="<?=e($edit['descripcion'] ?? '')?>" placeholder="Descripción">
            </div>
            <div class="col-lg-3">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-select">
                    <?php foreach(['cotizacion'=>'Cotización','produccion'=>'Producción','terminado'=>'Terminado','entregado'=>'Entregado'] as $k=>$v): ?>
                        <option value="<?=$k?>" <?=($edit['estado']??'cotizacion')===$k?'selected':''?>><?=$v?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4"><label class="form-label">Venta</label><input name="venta" type="number" step=".01" min="0" class="form-control" value="<?=e($edit['precio_venta'] ?? '0')?>"></div>
            <div class="col-md-4"><label class="form-label">Mano de obra</label><input name="mano_obra" type="number" step=".01" min="0" class="form-control" value="<?=e($edit['mano_obra'] ?? '0')?>"></div>
            <div class="col-md-4"><label class="form-label">Otros costos</label><input name="otros" type="number" step=".01" min="0" class="form-control" value="<?=e($edit['otros_costos'] ?? '0')?>"></div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary"><?= $edit ? 'Actualizar trabajo' : 'Crear trabajo' ?></button>
                <?php if($edit): ?><a href="trabajos.php" class="btn btn-outline-secondary">Cancelar</a><?php endif; ?>
                <?php if(!$edit): ?><span class="align-self-center text-secondary small">El folio se genera automáticamente.</span><?php endif; ?>
            </div>
        </div>
    </form>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2">
            <div class="col-md-10"><input name="q_cliente" class="form-control" value="<?=e($qCliente)?>" placeholder="Filtrar clientes de Akaunting por nombre, teléfono, correo, RFC o ciudad"></div>
            <div class="col-md-2 d-grid"><button class="btn btn-dark">Buscar cliente</button></div>
        </form>
    </div>
</div>

<div class="card">
<div class="table-responsive">
<table class="table table-striped mb-0">
<thead><tr><th>Folio</th><th>Cliente</th><th>Trabajo</th><th>Estado</th><th>Materiales</th><th>Venta</th><th>Utilidad</th><th>Acciones</th></tr></thead>
<tbody>
<?php foreach($rows as $r): $costos=(float)$r['materiales']+(float)$r['mano_obra']+(float)$r['otros_costos']; ?>
<tr>
<td class="fw-semibold"><?=e($r['folio'] ?: ('#'.$r['id']))?></td>
<td><?=e($r['cliente'])?></td>
<td><?=e($r['descripcion'])?></td>
<td><span class="badge text-bg-secondary"><?=e($r['estado'])?></span></td>
<td><?=money($r['materiales'])?></td>
<td><?=money($r['precio_venta'])?></td>
<td><?=money((float)$r['precio_venta']-$costos)?></td>
<td class="text-nowrap">
<a class="btn btn-sm btn-outline-primary" href="trabajos.php?editar=<?=(int)$r['id']?>">Editar</a>
<form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar este trabajo?');"><input type="hidden" name="csrf" value="<?=csrf()?>"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?=(int)$r['id']?>"><button class="btn btn-sm btn-outline-danger">Borrar</button></form>
</td>
</tr>
<?php endforeach; if(!$rows): ?><tr><td colspan="8" class="text-center text-secondary py-4">No hay trabajos registrados.</td></tr><?php endif; ?>
</tbody></table>
</div></div>
<?php foot(); ?>
