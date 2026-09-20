<?php
return [
    'app' => [
        'timezone' => 'America/Chihuahua',
        'name' => 'Colibrí Compras',
    ],
    'db' => [
        'host' => 'localhost',
        'name' => 'colibrip_compras',
        'user' => 'TU_USUARIO_MYSQL',
        'pass' => 'TU_PASSWORD_MYSQL',
        'charset' => 'utf8mb4',
    ],
    'akaunting' => [
        'host' => 'localhost',
        'name' => 'colibrip_akau488',
        'user' => 'TU_USUARIO_MYSQL',
        'pass' => 'TU_PASSWORD_MYSQL',
        'charset' => 'utf8mb4',
    ],
    'storage' => dirname(__DIR__, 2) . '/storage',
];
