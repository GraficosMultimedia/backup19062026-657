<?php
$configFile = __DIR__ . '/../config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    die('Falta app/config/config.php. Copie config.example.php como config.php y coloque sus datos de MySQL.');
}
$config = require $configFile;

date_default_timezone_set($config['app']['timezone'] ?? 'America/Chihuahua');
session_name('colibri_compras');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

try {
    $dsn = 'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['name'] . ';charset=' . ($config['db']['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    error_log('Colibri Compras DB: ' . $e->getMessage());
    http_response_code(500);
    die('Error de conexión a BD. Revise app/config/config.php');
}

function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function check_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) die('CSRF inválido');
}
function auth(): void {
    if (empty($_SESSION['uid'])) { header('Location: login.php'); exit; }
}
function flash(string $message, string $type = 'success'): void { $_SESSION['flash'] = [$message, $type]; }
function redirect(string $url): never { header('Location: ' . $url); exit; }
function money($value): string { return '$' . number_format((float)$value, 2); }
function purchasePaid(PDO $pdo, int $id): float {
    $s = $pdo->prepare('SELECT COALESCE(SUM(monto),0) FROM pagos WHERE compra_id=?');
    $s->execute([$id]); return (float)$s->fetchColumn();
}
function purchaseStatus(float $total, float $paid): string {
    if ($paid <= 0.00001) return 'pendiente';
    if ($paid + 0.00001 >= $total) return 'pagada';
    return 'parcial';
}
function syncPurchaseStatus(PDO $pdo, int $id): void {
    $s = $pdo->prepare('SELECT total FROM compras WHERE id=?'); $s->execute([$id]);
    $total = (float)$s->fetchColumn();
    if ($total === 0.0) { $pdo->prepare("UPDATE compras SET estado='pendiente' WHERE id=?")->execute([$id]); return; }
    $paid = purchasePaid($pdo, $id);
    $status = purchaseStatus($total, $paid);
    $pdo->prepare('UPDATE compras SET estado=? WHERE id=?')->execute([$status, $id]);
}

function akauntingPdo(): PDO {
    global $config;
    static $akPdo = null;
    if ($akPdo instanceof PDO) return $akPdo;

    $ak = $config['akaunting'] ?? [];
    if (empty($ak['name'])) {
        throw new RuntimeException('Falta la configuración de la base de datos de Akaunting.');
    }

    $dsn = 'mysql:host=' . ($ak['host'] ?? 'localhost') . ';dbname=' . $ak['name'] . ';charset=' . ($ak['charset'] ?? 'utf8mb4');
    $akPdo = new PDO($dsn, $ak['user'] ?? '', $ak['pass'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $akPdo;
}

function akauntingClientes(PDO $akPdo, string $q = ''): array {
    $sql = "SELECT id, name, email, tax_number, phone, address, city, zip_code, state, country
            FROM ak4s_contacts
            WHERE company_id = 1 AND type = 'customer' AND enabled = 1 AND deleted_at IS NULL";
    $params = [];
    if ($q !== '') {
        $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR tax_number LIKE ? OR city LIKE ?)";
        $like = '%' . $q . '%';
        $params = [$like, $like, $like, $like, $like];
    }
    $sql .= ' ORDER BY name ASC LIMIT 1000';
    $s = $akPdo->prepare($sql);
    $s->execute($params);
    return $s->fetchAll();
}

function akauntingCliente(PDO $akPdo, int $id): ?array {
    $s = $akPdo->prepare("SELECT id, name, email, tax_number, phone, address, city, zip_code, state, country
                          FROM ak4s_contacts
                          WHERE id = ? AND company_id = 1 AND type = 'customer' AND enabled = 1 AND deleted_at IS NULL
                          LIMIT 1");
    $s->execute([$id]);
    $row = $s->fetch();
    return $row ?: null;
}

function ensureTrabajoColumns(PDO $pdo): void {
    $checks = [
        'akaunting_contact_id' => 'BIGINT UNSIGNED NULL',
    ];
    foreach ($checks as $column => $definition) {
        $q = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'trabajos' AND column_name = ?");
        $q->execute([$column]);
        if (!(int)$q->fetchColumn()) {
            $pdo->exec("ALTER TABLE trabajos ADD COLUMN `$column` $definition");
        }
    }
    $idx = $pdo->query("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'trabajos' AND index_name = 'idx_trabajos_akaunting_contact'")->fetchColumn();
    if (!(int)$idx) {
        $pdo->exec("ALTER TABLE trabajos ADD INDEX idx_trabajos_akaunting_contact (akaunting_contact_id)");
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS trabajos_folios (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY) ENGINE=InnoDB");
}

function siguienteFolioTrabajo(PDO $pdo): string {
    $pdo->exec("INSERT INTO trabajos_folios VALUES (NULL)");
    $n = (int)$pdo->lastInsertId();
    return 'TR-' . str_pad((string)$n, 6, '0', STR_PAD_LEFT);
}

function logAction(PDO $pdo, string $action, string $detail = ''): void {
    $s = $pdo->prepare('INSERT INTO logs(usuario_id,accion,detalle) VALUES(?,?,?)');
    $s->execute([$_SESSION['uid'] ?? null, $action, $detail]);
}
