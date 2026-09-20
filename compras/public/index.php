<?php
require '../app/core/bootstrap.php'; auth(); require '../app/core/layout.php';
$stats=[];
$stats['compras']=(int)$pdo->query('SELECT COUNT(*) FROM compras')->fetchColumn();
$stats['total']=(float)$pdo->query('SELECT COALESCE(SUM(total),0) FROM compras WHERE estado<>"cancelada"')->fetchColumn();
$stats['pagado']=(float)$pdo->query('SELECT COALESCE(SUM(monto),0) FROM pagos p JOIN compras c ON c.id=p.compra_id WHERE c.estado<>"cancelada"')->fetchColumn();
$stats['pendiente']=max(0,$stats['total']-$stats['pagado']);
$stats['productos']=(int)$pdo->query('SELECT COUNT(*) FROM productos WHERE activo=1')->fetchColumn();
$rows=$pdo->query("SELECT DATE_FORMAT(fecha_emision,'%Y-%m') mes,SUM(total) total FROM compras WHERE fecha_emision IS NOT NULL AND estado<>'cancelada' GROUP BY mes ORDER BY mes")->fetchAll();
head('Dashboard');
?>
<div class="row g-3">
<?php foreach([['Compras',$stats['compras'],''],['Total comprado',money($stats['total']),''],['Total pagado',money($stats['pagado']),'text-success'],['Saldo pendiente',money($stats['pendiente']),'text-danger']] as $c):?><div class="col-xl-3 col-md-6"><div class="card h-100"><div class="card-body"><div class="text-secondary small"><?=e($c[0])?></div><div class="fs-3 fw-bold <?=$c[2]?>"><?=e($c[1])?></div></div></div></div><?php endforeach?>
</div>
<div class="row g-3 mt-1"><div class="col-lg-8"><div class="card"><div class="card-body"><h2 class="h5">Compras por mes</h2><canvas id="chart" height="110"></canvas></div></div></div><div class="col-lg-4"><div class="card"><div class="card-body"><h2 class="h5">Resumen</h2><p class="mb-2">Productos activos <strong><?=$stats['productos']?></strong></p><p class="mb-2">Pagado <strong class="text-success"><?=money($stats['pagado'])?></strong></p><p class="mb-0">Pendiente <strong class="text-danger"><?=money($stats['pendiente'])?></strong></p></div></div></div></div>
<script>new Chart(document.getElementById('chart'),{type:'bar',data:{labels:<?=json_encode(array_column($rows,'mes'))?>,datasets:[{label:'Compras MXN',data:<?=json_encode(array_map('floatval',array_column($rows,'total')))?>}]},options:{responsive:true,plugins:{legend:{display:true}}}});</script>
<?php foot();
