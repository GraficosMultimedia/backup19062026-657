<?php
require __DIR__.'/../app/core/bootstrap.php';
auth();
flash('Ya no se importan clientes. Colibrí Compras los consulta directamente desde Akaunting.', 'info');
redirect('clientes.php');
