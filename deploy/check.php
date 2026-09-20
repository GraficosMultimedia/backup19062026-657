<?php
declare(strict_types=1);
foreach(['pdo','pdo_mysql','json','mbstring'] as $e)echo(extension_loaded($e)?'OK ':'FAIL ').$e."\n";echo"PHP ".PHP_VERSION."\n";echo file_exists(realpath(__DIR__.'/../..').'/config/runtime.php')?'OK runtime.php'."\n":'FAIL runtime.php'."\n";
