<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

cp_check_csrf($_POST['csrf'] ?? null);

$name  = trim((string)($_POST['customer_name'] ?? ''));
$email = trim((string)($_POST['customer_email'] ?? ''));
$phone = trim((string)($_POST['customer_phone'] ?? ''));
$items = json_decode((string)($_POST['items_json'] ?? '[]'), true);

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '') {
    http_response_code(422);
    exit('Datos de solicitud incompletos.');
}

if (!is_array($items) || !$items) {
    http_response_code(422);
    exit('No se recibieron las configuraciones de los archivos.');
}

/*
 * Esta versión NO analiza PDFs ni intenta contar páginas automáticamente.
 * El número de páginas se toma únicamente de items_json.pages.
 * Si no viene informado, se considera 1 página.
 */

$files = $_FILES['files'] ?? null;
if (!is_array($files) || !isset($files['name'])) {
    http_response_code(422);
    exit('No se recibió ningún archivo. El formulario debe enviar los archivos como files[].');
}

$allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];
$maxBytes = 25 * 1024 * 1024;

function normalize_uploaded_files(array $files): array
{
    $normalized = [];
    $count = is_array($files['name'] ?? null) ? count($files['name']) : 0;

    for ($i = 0; $i < $count; $i++) {
        $normalized[] = [
            'name'     => (string)($files['name'][$i] ?? ''),
            'type'     => (string)($files['type'][$i] ?? ''),
            'tmp_name' => (string)($files['tmp_name'][$i] ?? ''),
            'error'    => (int)($files['error'][$i] ?? UPLOAD_ERR_NO_FILE),
            'size'     => (int)($files['size'][$i] ?? 0),
        ];
    }

    return $normalized;
}

$uploaded = normalize_uploaded_files($files);
if (!$uploaded) {
    http_response_code(422);
    exit('No se recibió ningún archivo. El formulario debe enviar los archivos como files[].');
}

$config = require __DIR__ . '/../config/config.php';
$dbCfg = $config['db'];
$dsn = 'mysql:host=' . $dbCfg['host'] . ';dbname=' . $dbCfg['name'] . ';charset=' . $dbCfg['charset'];
$db = new PDO($dsn, (string)$dbCfg['user'], (string)$dbCfg['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$sizeStmt = $db->prepare('SELECT * FROM cp_print_sizes WHERE id=? AND enabled=1 LIMIT 1');
$matStmt  = $db->prepare('SELECT * FROM cp_print_materials WHERE id=? AND enabled=1 LIMIT 1');
$finStmt  = $db->prepare('SELECT * FROM cp_print_finishes WHERE id=? AND enabled=1 LIMIT 1');
$priceStmt = $db->prepare(
    'SELECT * FROM cp_print_prices
     WHERE size_id=? AND material_id=? AND finish_id=? AND color_mode=? AND pricing_mode=? AND enabled=1
     LIMIT 1'
);

$validated = [];
$total = 0.0;

foreach ($uploaded as $index => $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(422);
        exit('No fue posible recibir el archivo ' . basename($file['name']) . '. Código de carga: ' . $file['error']);
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        http_response_code(422);
        exit('El archivo ' . basename($file['name']) . ' no fue recibido correctamente.');
    }

    if ($file['size'] <= 0 || $file['size'] > $maxBytes) {
        http_response_code(422);
        exit('El archivo ' . basename($file['name']) . ' supera el límite permitido de 25 MB o está vacío.');
    }

    $originalName = basename($file['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExt, true)) {
        http_response_code(422);
        exit('El archivo ' . $originalName . ' no tiene un formato permitido. Usa PDF, JPG o PNG.');
    }

    $item = is_array($items[$index] ?? null) ? $items[$index] : [];

    $sizeId = (int)($item['size_id'] ?? 0);
    $matId  = (int)($item['material_id'] ?? 0);
    $finId  = (int)($item['finish_id'] ?? 0);
    $color  = (($item['color_mode'] ?? 'color') === 'bw') ? 'bw' : 'color';
    $mode   = (($item['pricing_mode'] ?? 'per_page') === 'per_sheet') ? 'per_sheet' : 'per_page';
    $copies = max(1, min(9999, (int)($item['copies'] ?? 1)));

    /* Páginas manuales. No se analiza el archivo. */
    $pages = max(1, min(100000, (int)($item['pages'] ?? 1)));

    $sizeStmt->execute([$sizeId]);
    $sizeRow = $sizeStmt->fetch();
    $matStmt->execute([$matId]);
    $matRow = $matStmt->fetch();
    $finStmt->execute([$finId]);
    $finRow = $finStmt->fetch();
    $priceStmt->execute([$sizeId, $matId, $finId, $color, $mode]);
    $priceRow = $priceStmt->fetch();

    if (!$sizeRow || !$matRow || !$finRow || !$priceRow) {
        http_response_code(422);
        exit('Una de las combinaciones seleccionadas no tiene una tarifa activa.');
    }

    $qty = $mode === 'per_sheet' ? (int)ceil($pages / 2) : $pages;
    $unit = (float)$priceRow['unit_price'];
    $subtotal = $unit * $qty * $copies;
    $total += $subtotal;

    $mime = '';
    if (function_exists('finfo_open')) {
        $f = finfo_open(FILEINFO_MIME_TYPE);
        if ($f) {
            $mime = (string)finfo_file($f, $file['tmp_name']);
            finfo_close($f);
        }
    }
    if ($mime === '') {
        $mime = (string)$file['type'];
    }
    if ($mime === '') {
        $mime = 'application/octet-stream';
    }

    $stored = bin2hex(random_bytes(16)) . '.' . $extension;

    $validated[] = [
        'tmp_name' => $file['tmp_name'],
        'stored' => $stored,
        'name' => $originalName,
        'mime' => $mime,
        'size' => $file['size'],
        'pages' => $pages,
        'sheets' => $mode === 'per_sheet' ? (int)ceil($pages / 2) * $copies : $pages * $copies,
        'size_id' => $sizeId,
        'material_id' => $matId,
        'finish_id' => $finId,
        'color' => $color,
        'copies' => $copies,
        'mode' => $mode,
        'unit' => $unit,
        'subtotal' => $subtotal,
    ];
}

if (!$validated) {
    http_response_code(422);
    exit('No hay archivos válidos para registrar.');
}

$finalDir = __DIR__ . '/../uploads/print_requests';
if (!is_dir($finalDir) && !mkdir($finalDir, 0775, true) && !is_dir($finalDir)) {
    http_response_code(500);
    exit('No fue posible preparar la carpeta de archivos.');
}

$db->beginTransaction();
$moved = [];

try {
    $st = $db->prepare(
        'INSERT INTO cp_print_requests(customer_name,customer_email,customer_phone,status,total_estimate)
         VALUES(?,?,?,?,?)'
    );
    $st->execute([$name, $email, $phone, 'new', $total]);
    $requestId = (int)$db->lastInsertId();

    $requestDir = $finalDir . '/' . $requestId;
    if (!is_dir($requestDir) && !mkdir($requestDir, 0775, true) && !is_dir($requestDir)) {
        throw new RuntimeException('No fue posible crear la carpeta de la solicitud.');
    }

    $itemSt = $db->prepare(
        'INSERT INTO cp_print_request_items
        (request_id,original_name,stored_path,mime_type,file_size,page_count,sheet_count,size_id,material_id,finish_id,color_mode,copies,pricing_mode,unit_price,subtotal)
        VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );

    foreach ($validated as $v) {
        $finalPath = $requestDir . '/' . $v['stored'];

        if (!move_uploaded_file($v['tmp_name'], $finalPath) || !is_file($finalPath)) {
            throw new RuntimeException('No fue posible guardar el archivo ' . $v['name']);
        }
        $moved[] = $finalPath;

        $relative = 'uploads/print_requests/' . $requestId . '/' . $v['stored'];
        $itemSt->execute([
            $requestId,
            $v['name'],
            $relative,
            $v['mime'],
            $v['size'],
            $v['pages'],
            $v['sheets'],
            $v['size_id'],
            $v['material_id'],
            $v['finish_id'],
            $v['color'],
            $v['copies'],
            $v['mode'],
            $v['unit'],
            $v['subtotal'],
        ]);
    }

    $db->commit();
    cp_redirect('../solicitud_enviada.php?id=' . $requestId);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    foreach ($moved as $path) {
        @unlink($path);
    }

    $errorId = bin2hex(random_bytes(4));
    error_log('Colibri Print submit_request [' . $errorId . ']: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());

    http_response_code(500);
    exit('No fue posible registrar la solicitud. Código de error: ' . $errorId);
}
