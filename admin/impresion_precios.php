<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
$db=cp_db();
$sizes=$db->query("SELECT * FROM cp_print_sizes ORDER BY sort_order,id")->fetchAll();
$materials=$db->query("SELECT * FROM cp_print_materials ORDER BY sort_order,id")->fetchAll();
$finishes=$db->query("SELECT * FROM cp_print_finishes ORDER BY sort_order,id")->fetchAll();
$prices=$db->query("SELECT p.*,s.name size_name,m.name material_name,f.name finish_name FROM cp_print_prices p JOIN cp_print_sizes s ON s.id=p.size_id JOIN cp_print_materials m ON m.id=p.material_id JOIN cp_print_finishes f ON f.id=p.finish_id ORDER BY s.sort_order,m.sort_order,f.sort_order,p.color_mode")->fetchAll();
$msg=$_GET['ok']??'';
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Precios de impresión · Colibrí Print</title><link rel="stylesheet" href="../assets/css/colibri-print.css"></head>
<body><div class="cp-wrap">
<div class="cp-brand">COLIBRÍ PRINT · CONFIGURACIÓN</div>
<div class="cp-hero"><div><h1>Precios de impresión</h1><p>Administra tamaños, materiales, acabados y las combinaciones de precios que verá el cliente.</p></div><div class="cp-admin-nav"><a class="cp-btn cp-btn-soft" href="recepcion_impresiones.php">Recepción</a><a class="cp-btn cp-btn-primary" href="../solicitar_impresion.php">Ver formulario</a></div></div>
<?php if($msg):?><div class="cp-success">✓ <?=cp_e($msg)?></div><?php endif;?>
<div class="cp-admin-grid">
<section class="cp-card cp-section"><div class="cp-card-head"><div class="cp-section-title"><div class="cp-section-num">1</div><div><h2>Tamaños</h2><p>Modifica nombre, código y medidas.</p></div></div></div>
<table class="cp-admin-table"><tr><th>Nombre</th><th>Medidas</th><th>Activo</th><th></th></tr>
<?php foreach($sizes as $s): $fid='size-'.$s['id'];?><tr>
<td><input form="<?=$fid?>" name="name" value="<?=cp_e($s['name'])?>"></td>
<td><input form="<?=$fid?>" name="width_mm" type="number" step=".01" value="<?=$s['width_mm']?>" style="width:90px"> × <input form="<?=$fid?>" name="height_mm" type="number" step=".01" value="<?=$s['height_mm']?>" style="width:90px"></td>
<td><input form="<?=$fid?>" name="enabled" type="checkbox" value="1" <?=$s['enabled']?'checked':''?>></td>
<td><form id="<?=$fid?>" method="post" action="catalog_save.php"><input type="hidden" name="csrf" value="<?=cp_e(cp_csrf())?>"><input type="hidden" name="type" value="size"><input type="hidden" name="id" value="<?=$s['id']?>"><button class="cp-btn cp-btn-soft" type="submit">Guardar</button></form></td>
</tr><?php endforeach;?>
</table></section>
<section class="cp-card cp-section"><div class="cp-card-head"><div class="cp-section-title"><div class="cp-section-num">2</div><div><h2>Materiales</h2><p>Los materiales disponibles para cotizar.</p></div></div></div>
<table class="cp-admin-table"><tr><th>Nombre</th><th>Unidad</th><th>Activo</th><th></th></tr>
<?php foreach($materials as $m): $fid='material-'.$m['id'];?><tr>
<td><input form="<?=$fid?>" name="name" value="<?=cp_e($m['name'])?>"></td>
<td><input form="<?=$fid?>" name="unit_label" value="<?=cp_e($m['unit_label'])?>" style="width:100px"></td>
<td><input form="<?=$fid?>" name="enabled" type="checkbox" value="1" <?=$m['enabled']?'checked':''?>></td>
<td><form id="<?=$fid?>" method="post" action="catalog_save.php"><input type="hidden" name="csrf" value="<?=cp_e(cp_csrf())?>"><input type="hidden" name="type" value="material"><input type="hidden" name="id" value="<?=$m['id']?>"><button class="cp-btn cp-btn-soft" type="submit">Guardar</button></form></td>
</tr><?php endforeach;?>
</table></section>
</div>

<section class="cp-card cp-section"><div class="cp-card-head"><div class="cp-section-title"><div class="cp-section-num">3</div><div><h2>Acabados</h2><p>Modifica los nombres sin tocar código.</p></div></div></div>
<table class="cp-admin-table"><tr><th>Nombre</th><th>Código</th><th>Activo</th><th></th></tr>
<?php foreach($finishes as $f): $fid='finish-'.$f['id'];?><tr>
<td><input form="<?=$fid?>" name="name" value="<?=cp_e($f['name'])?>"></td>
<td><?=cp_e($f['code'])?></td>
<td><input form="<?=$fid?>" name="enabled" type="checkbox" value="1" <?=$f['enabled']?'checked':''?>></td>
<td><form id="<?=$fid?>" method="post" action="catalog_save.php"><input type="hidden" name="csrf" value="<?=cp_e(cp_csrf())?>"><input type="hidden" name="type" value="finish"><input type="hidden" name="id" value="<?=$f['id']?>"><button class="cp-btn cp-btn-soft" type="submit">Guardar</button></form></td>
</tr><?php endforeach;?></table></section>

<section class="cp-card"><div class="cp-card-head"><div class="cp-section-title"><div class="cp-section-num">4</div><div><h2>Matriz de tarifas</h2><p>Esta es la parte que realmente controla el precio del formulario.</p></div></div></div>
<form class="cp-admin-price" method="post" action="price_save.php" style="margin-bottom:18px"><input type="hidden" name="csrf" value="<?=cp_e(cp_csrf())?>">
<select name="size_id" required><option value="">Tamaño</option><?php foreach($sizes as $s):?><option value="<?=$s['id']?>"><?=cp_e($s['name'])?></option><?php endforeach;?></select>
<select name="material_id" required><option value="">Material</option><?php foreach($materials as $m):?><option value="<?=$m['id']?>"><?=cp_e($m['name'])?></option><?php endforeach;?></select>
<select name="finish_id" required><option value="">Acabado</option><?php foreach($finishes as $f):?><option value="<?=$f['id']?>"><?=cp_e($f['name'])?></option><?php endforeach;?></select>
<select name="color_mode"><option value="color">Color</option><option value="bw">B/N</option></select>
<input type="number" name="unit_price" step=".01" min="0" placeholder="Precio" required>
<select name="pricing_mode"><option value="per_page">Por página</option><option value="per_sheet">Por hoja</option></select>
<button class="cp-btn cp-btn-primary" type="submit">Guardar tarifa</button></form>
<table class="cp-admin-table"><tr><th>Tamaño</th><th>Material</th><th>Acabado</th><th>Modo</th><th>Precio</th><th></th></tr>
<?php foreach($prices as $p):?><tr><td><?=cp_e($p['size_name'])?></td><td><?=cp_e($p['material_name'])?></td><td><?=cp_e($p['finish_name'])?></td><td><?=cp_e($p['color_mode'])?> · <?=cp_e($p['pricing_mode'])?></td><td><?=cp_money($p['unit_price'])?></td><td><form method="post" action="price_delete.php" onsubmit="return confirm('¿Desactivar esta tarifa?')"><input type="hidden" name="csrf" value="<?=cp_e(cp_csrf())?>"><input type="hidden" name="id" value="<?=$p['id']?>"><button class="cp-btn cp-btn-danger">Desactivar</button></form></td></tr><?php endforeach;?></table></section>
</div></body></html>
