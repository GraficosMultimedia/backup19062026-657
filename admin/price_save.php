<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

cp_check_csrf($_POST['csrf'] ?? null);

$db = cp_db();

$sizeId      = (int)($_POST['size_id'] ?? 0);
$materialId  = (int)($_POST['material_id'] ?? 0);
$finishId    = (int)($_POST['finish_id'] ?? 0);
$colorMode   = trim((string)($_POST['color_mode'] ?? 'color'));
$pricingMode = trim((string)($_POST['pricing_mode'] ?? 'per_page'));
$unitPrice   = (float)($_POST['unit_price'] ?? 0);

if ($sizeId < 1 || $materialId < 1 || $finishId < 1 || $unitPrice < 0) {
    cp_redirect('impresion_precios.php?ok=Datos de tarifa inválidos');
}

if (!in_array($colorMode, ['color', 'bw'], true)) {
    cp_redirect('impresion_precios.php?ok=Modo de color inválido');
}

if (!in_array($pricingMode, ['per_page', 'per_sheet'], true)) {
    cp_redirect('impresion_precios.php?ok=Modo de cobro inválido');
}

$find = $db->prepare(
    'SELECT id FROM cp_print_prices
     WHERE size_id = ? AND material_id = ? AND finish_id = ?
       AND color_mode = ? AND pricing_mode = ?
     LIMIT 1'
);
$find->execute([
    $sizeId, $materialId, $finishId, $colorMode, $pricingMode
]);

$existingId = (int)($find->fetchColumn() ?: 0);

if ($existingId > 0) {
    $st = $db->prepare(
        'UPDATE cp_print_prices
         SET unit_price = ?, min_qty = 1, enabled = 1, updated_at = NOW()
         WHERE id = ?'
    );
    $st->execute([$unitPrice, $existingId]);
} else {
    $st = $db->prepare(
        'INSERT INTO cp_print_prices
         (size_id, material_id, finish_id, color_mode, pricing_mode,
          unit_price, min_qty, enabled, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, 1, 1, NOW(), NOW())'
    );
    $st->execute([
        $sizeId, $materialId, $finishId, $colorMode,
        $pricingMode, $unitPrice
    ]);
}

cp_redirect('impresion_precios.php?ok=Tarifa guardada');
