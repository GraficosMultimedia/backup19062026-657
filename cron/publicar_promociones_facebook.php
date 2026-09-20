<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/runtime.php';
require_once __DIR__ . '/../includes/promociones.php';
require_once __DIR__ . '/../includes/facebook.php';

if (!facebook_configured() || !facebook_auto_publish_enabled()) {
    echo "Facebook no configurado o auto-publicación desactivada.\n";
    exit(0);
}

$ids = facebook_pending_promotions(20);
if (!$ids) {
    echo "No hay promociones pendientes.\n";
    exit(0);
}
foreach ($ids as $id) {
    $result = facebook_publish_promotion($id);
    echo '#' . $id . ' => ' . ($result['status'] ?? 'unknown') . ' | ' . ($result['message'] ?? '') . "\n";
}
