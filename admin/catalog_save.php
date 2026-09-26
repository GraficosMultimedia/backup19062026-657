<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
cp_check_csrf($_POST['csrf']??null);
$db=cp_db(); $type=$_POST['type']??''; $id=(int)($_POST['id']??0); $enabled=isset($_POST['enabled'])?1:0;
if($type==='size'){
 $st=$db->prepare("UPDATE cp_print_sizes SET name=?,width_mm=?,height_mm=?,enabled=? WHERE id=?");
 $st->execute([trim($_POST['name']??''),(float)$_POST['width_mm'],(float)$_POST['height_mm'],$enabled,$id]);
}elseif($type==='material'){
 $st=$db->prepare("UPDATE cp_print_materials SET name=?,unit_label=?,enabled=? WHERE id=?");
 $st->execute([trim($_POST['name']??''),trim($_POST['unit_label']??'hoja'),$enabled,$id]);
}elseif($type==='finish'){
 $st=$db->prepare("UPDATE cp_print_finishes SET name=?,enabled=? WHERE id=?");
 $st->execute([trim($_POST['name']??''),$enabled,$id]);
}
cp_redirect('impresion_precios.php?ok=Catálogo actualizado');
