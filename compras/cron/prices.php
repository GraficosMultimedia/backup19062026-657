<?php
require __DIR__.'/../app/core/bootstrap.php';require __DIR__.'/../app/services/AvanceScraper.php';
// URLs a monitorear: agregue productos públicos de Avance aquí.
$urls=[];
foreach($urls as $url){try{$data=(new AvanceScraper)->fetch($url);foreach($data as $x)$pdo->prepare('INSERT INTO precios_web(sku,url,precio,fuente) VALUES(?,?,?,?)')->execute([$x['sku'],$x['url'],$x['precio'],'Avance y Tecnología']);}catch(Throwable $e){file_put_contents($config['storage'].'/logs/cron.log',date('c').' '.$e->getMessage().PHP_EOL,FILE_APPEND);}}
