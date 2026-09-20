<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require '../app/core/bootstrap.php';

auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: compras.php');
    exit;
}

$id = (int)($_POST['compra_id'] ?? 0);
$monto = (float)($_POST['monto'] ?? 0);
$formaPago = trim((string)($_POST['forma_pago'] ?? ''));
$uuidPago = trim((string)($_POST['uuid_pago'] ?? ''));
$csrf = (string)($_POST['csrf'] ?? '');

if ($id <= 0) {
    die('Compra inválida.');
}

if (!hash_equals((string)($_SESSION['csrf'] ?? ''), $csrf)) {
    die('Token CSRF inválido.');
}

if ($monto <= 0) {
    die('El monto debe ser mayor que cero.');
}

if ($formaPago === '') {
    die('La forma de pago es obligatoria.');
}

try {
    $pdo->beginTransaction();

    // Bloqueamos la compra durante el cálculo para evitar pagos simultáneos
    // que superen el saldo.
    $stmt = $pdo->prepare("
        SELECT id, total, estado
        FROM compras
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->execute([$id]);
    $compra = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$compra) {
        throw new RuntimeException('La compra no existe.');
    }

    if (($compra['estado'] ?? '') === 'cancelada') {
        throw new RuntimeException('No se puede pagar una compra cancelada.');
    }

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(monto), 0)
        FROM pagos
        WHERE compra_id = ?
    ");
    $stmt->execute([$id]);
    $pagado = (float)$stmt->fetchColumn();

    $total = (float)$compra['total'];
    $saldo = max($total - $pagado, 0);

    // Tolerancia mínima por redondeos de centavos.
    if ($monto > $saldo + 0.009) {
        throw new RuntimeException(
            'El pago excede el saldo disponible: $' . number_format($saldo, 2)
        );
    }

    $nuevoPagado = $pagado + $monto;
    $saldoInsoluto = max($total - $nuevoPagado, 0);

    // La tabla pagos ya existe en la instalación actual.
    // Se usan únicamente columnas que ya consume compra.php.
    $stmt = $pdo->prepare("
        INSERT INTO pagos
            (compra_id, fecha_pago, uuid_pago, forma_pago, num_parcialidad, monto, saldo_insoluto)
        VALUES
            (?, NOW(), ?, ?, ?, ?, ?)
    ");

    $numParcialidad = $pdo->prepare("
        SELECT COUNT(*) + 1
        FROM pagos
        WHERE compra_id = ?
    ");
    $numParcialidad->execute([$id]);
    $parcialidad = (int)$numParcialidad->fetchColumn();

    $stmt->execute([
        $id,
        $uuidPago !== '' ? $uuidPago : null,
        $formaPago,
        $parcialidad,
        $monto,
        $saldoInsoluto
    ]);

    // Sincronizamos el estado administrativo de la compra.
    $nuevoEstado = ($saldoInsoluto <= 0.009) ? 'pagada' : 'pendiente';

    $stmt = $pdo->prepare("
        UPDATE compras
        SET estado = ?
        WHERE id = ?
    ");
    $stmt->execute([$nuevoEstado, $id]);

    $pdo->commit();

    header('Location: compra.php?id=' . $id);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    require_once '../app/core/layout.php';
    head('Error al registrar pago');

    echo '<div class="alert alert-danger">';
    echo '<strong>No se pudo registrar el pago.</strong><br>';
    echo e($e->getMessage());
    echo '</div>';

    echo '<a href="compra.php?id=' . (int)$id . '" class="btn btn-secondary">';
    echo 'Volver a la compra';
    echo '</a>';

    foot();
}
