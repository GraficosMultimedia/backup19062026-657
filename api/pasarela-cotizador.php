<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/runtime.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function cpq_json(bool $ok, array $data = [], int $code = 200): never {
    http_response_code($code);
    echo json_encode(array_merge(['ok'=>$ok], $data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function cpq_clean(string $value, int $max=5000): string {
    $value = trim($value);
    if (strlen($value) > $max) $value = substr($value, 0, $max);
    return $value;
}
function cpq_phone(string $value): string {
    return preg_replace('/[^0-9+ ()-]/', '', trim($value)) ?: '';
}
function cpq_rate_limit(): void {
    $key = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') . '|cpq');
    $dir = sys_get_temp_dir() . '/cpq_rate';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    $file = $dir . '/' . $key;
    $now = time();
    if (is_file($file)) {
        $last = (int)@file_get_contents($file);
        if ($last > 0 && ($now - $last) < 20) {
            cpq_json(false, ['message'=>'Espera unos segundos antes de enviar otra solicitud.'], 429);
        }
    }
    @file_put_contents($file, (string)$now, LOCK_EX);
}
function cpq_services(): array {
    return [
        'playeras'=>'Playeras personalizadas',
        'bordado'=>'Bordado',
        'sublimacion'=>'Sublimación',
        'dtf'=>'DTF',
        'impresion'=>'Impresión',
        'gran_formato'=>'Gran formato',
        'etiquetas'=>'Etiquetas y stickers',
        'sellos'=>'Sellos personalizados',
        'laser'=>'Grabado láser',
        'cnc'=>'Corte CNC',
        'corporea'=>'Letras corpóreas',
        'diseno'=>'Diseño gráfico',
        'comestible'=>'Impresión comestible',
        'promo'=>'Artículos promocionales',
        'vinil'=>'Vinil de corte',
        'invitaciones'=>'Invitaciones especiales',
        'otro'=>'Otro proyecto'
    ];
}
function cpq_upload(array $file, string $token): ?array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? 0) !== UPLOAD_ERR_OK) throw new RuntimeException('No se pudo recibir el archivo.');
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > 10 * 1024 * 1024) throw new RuntimeException('El archivo debe pesar entre 1 byte y 10 MB.');

    $tmp = (string)$file['tmp_name'];
    $mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $allowed = [
        'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp',
        'application/pdf'=>'pdf',
        'application/zip'=>'zip','application/x-zip-compressed'=>'zip',
        'application/postscript'=>'eps',
        'image/svg+xml'=>'svg',
    ];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Tipo de archivo no permitido.');
    }

    $dir = __DIR__ . '/../uploads/cotizador/' . $token;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException('No se pudo crear el almacenamiento del archivo.');

    $ext = $allowed[$mime];
    $filename = bin2hex(random_bytes(12)) . '.' . $ext;
    $dest = $dir . '/' . $filename;
    if (!move_uploaded_file($tmp, $dest)) throw new RuntimeException('No se pudo guardar el archivo.');

    return [
        'original_name'=>basename((string)($file['name'] ?? $filename)),
        'relative_path'=>'/uploads/cotizador/' . $token . '/' . $filename,
        'mime'=>$mime,
        'size'=>$size,
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cpq_json(false, ['message'=>'Método no permitido.'], 405);
}

cpq_rate_limit();

if (!empty($_POST['website'] ?? '')) {
    cpq_json(false, ['message'=>'Solicitud rechazada.'], 400);
}

$serviceKey = cpq_clean((string)($_POST['service'] ?? ''), 80);
$services = cpq_services();
if (!isset($services[$serviceKey])) {
    cpq_json(false, ['message'=>'Selecciona un servicio válido.'], 422);
}

$name = cpq_clean((string)($_POST['name'] ?? ''), 190);
$phone = cpq_phone((string)($_POST['phone'] ?? ''));
$email = cpq_clean((string)($_POST['email'] ?? ''), 190);
$desiredDate = trim((string)($_POST['desired_date'] ?? ''));
$delivery = cpq_clean((string)($_POST['delivery_method'] ?? ''), 100);
$design = cpq_clean((string)($_POST['design_status'] ?? ''), 100);
$application = cpq_clean((string)($_POST['application'] ?? ''), 120);
$notes = cpq_clean((string)($_POST['notes'] ?? ''), 4000);

if ($name === '' || $phone === '') {
    cpq_json(false, ['message'=>'Nombre y WhatsApp son obligatorios.'], 422);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    cpq_json(false, ['message'=>'El correo electrónico no es válido.'], 422);
}
if ($desiredDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $desiredDate)) {
    cpq_json(false, ['message'=>'La fecha solicitada no es válida.'], 422);
}

$serviceName = $services[$serviceKey];
$dynamic = [];
foreach ($_POST as $key=>$value) {
    if (!is_string($value)) continue;
    if (in_array($key,['service','service_name','name','phone','email','desired_date','delivery_method','design_status','application','notes','website'],true)) continue;
    $dynamic[$key] = cpq_clean($value, 600);
}

$requestToken = bin2hex(random_bytes(32));
$attachment = null;

try {
    if (!empty($_FILES['attachment']) && is_array($_FILES['attachment'])) {
        $attachment = cpq_upload($_FILES['attachment'], $requestToken);
    }

    $payload = [
        'version'=>2,
        'service'=>[
            'key'=>$serviceKey,
            'name'=>$serviceName,
        ],
        'details'=>$dynamic,
        'production'=>[
            'design_status'=>$design,
            'application'=>$application,
            'notes'=>$notes,
        ],
        'delivery'=>[
            'method'=>$delivery,
            'desired_date'=>$desiredDate,
        ],
        'attachment'=>$attachment,
        'submitted_at'=>date('c'),
        'source'=>'public_quote_wizard',
        'ip_hash'=>hash('sha256',(string)($_SERVER['REMOTE_ADDR'] ?? '')),
    ];

    $summary = "Servicio: {$serviceName}\n";
    if ($dynamic) {
        foreach ($dynamic as $key=>$value) {
            $summary .= $key . ': ' . $value . "\n";
        }
    }
    $summary .= "Diseño: {$design}\n";
    $summary .= "Aplicación/instalación: {$application}\n";
    $summary .= "Entrega: {$delivery}\n";
    $summary .= "Fecha solicitada: " . ($desiredDate !== '' ? $desiredDate : 'Por confirmar') . "\n";
    if ($notes !== '') $summary .= "Notas: {$notes}\n";
    if ($attachment) $summary .= "Archivo: " . $attachment['original_name'] . "\n";
    $summary .= "\n[CPQ_JSON]\n" . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $pdo = db();
    $st = $pdo->prepare(
        'INSERT INTO cp_web_quote_requests
        (request_token,customer_id,service_key,customer_name,email,phone,request_text,quantity,desired_date,attachment_name,status,created_at,updated_at)
        VALUES(?,NULL,?,?,?,?,?,?,?,?,?,NOW(),NOW())'
    );

    $quantity = isset($dynamic['quantity']) ? (string)$dynamic['quantity'] : null;
    $attachmentName = $attachment['original_name'] ?? null;

    $st->execute([
        $requestToken,
        $serviceKey,
        $name,
        $email !== '' ? $email : null,
        $phone !== '' ? $phone : null,
        $summary,
        $quantity,
        $desiredDate !== '' ? $desiredDate : null,
        $attachmentName,
        'new',
    ]);

    $id = (int)$pdo->lastInsertId();
    $reference = 'CPQ-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);

    $message = "Hola Colibrí Print México.\n"
             . "Quiero dar seguimiento a la solicitud {$reference}.\n\n"
             . "Servicio: {$serviceName}\n"
             . "Nombre: {$name}\n"
             . "WhatsApp: {$phone}\n"
             . "Entrega: " . ($delivery ?: 'Por confirmar') . "\n"
             . "Fecha solicitada: " . ($desiredDate ?: 'Por confirmar');

    $phoneDigits = preg_replace('/\D+/', '', $phone);
    if ($phoneDigits !== '' && !str_starts_with($phoneDigits,'52')) $phoneDigits = '52' . $phoneDigits;
    if (strlen($phoneDigits) < 10) $phoneDigits = '526271470053';
    $waUrl = 'https://wa.me/526271470053?text=' . rawurlencode($message);

    cpq_json(true, [
        'id'=>$id,
        'reference'=>$reference,
        'request_token'=>$requestToken,
        'message'=>'Tu solicitud quedó registrada. El equipo de Colibrí Print revisará los detalles y confirmará la cotización formal.',
        'whatsapp_url'=>$waUrl,
    ], 201);

} catch (Throwable $e) {
    if ($attachment && !empty($attachment['relative_path'])) {
        $absolute = __DIR__ . '/..' . $attachment['relative_path'];
        if (is_file($absolute)) @unlink($absolute);
    }
    cpq_json(false, ['message'=>'No se pudo registrar la solicitud. Intenta nuevamente.'], 500);
}
?>
