<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
cp_check_csrf($_POST['csrf']??null);
$db=cp_db();
$st=$db->prepare("INSERT INTO cp_print_prices(size_id,material_id,finish_id,color_mode,pricing_mode,unit_price,enabled) VALUES(?,?,?,?,?,?,1)
ON DUPLICATE KEY UPDATE unit_price=VALUES(unit_price),enabled=1");
$st->execute([(int)$_POST['size_id'],(int)$_POST['material_id'],(int)$_POST['finish_id'],($_POST['color_mode']??'color'),($_POST['pricing_mode']??'per_page'),(float)$_POST['unit_price']]);
cp_redirect('impresion_precios.php?ok=Tarifa guardada');
