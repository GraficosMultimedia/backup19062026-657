<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/seguimiento.php';
require_once __DIR__ . '/includes/company.php';

$company = company_profile();
$token = trim((string)($_GET['t'] ?? ''));
$order = $token !== '' ? tracking_order_by_token($token) : null;
$currentInternal = $order ? tracking_current_stage((int)$order['id']) : '';
$history = $order ? tracking_history((int)$order['id']) : [];
$clientStages = tracking_client_stages();
$currentClient = $order ? tracking_internal_to_client_stage($currentInternal) : 'received';
$clientKeys = array_keys($clientStages);
$currentIndex = array_search($currentClient, $clientKeys, true);
if ($currentIndex === false) $currentIndex = 0;
$showApproval = $order ? tracking_has_design_approval($history, $currentInternal) : false;
$clientHistory = $order ? tracking_client_history($history, $showApproval) : [];
$latestMessage = $order ? tracking_latest_client_message($history, $currentInternal) : '';
$items = $order ? tracking_order_items((int)$order['id']) : [];
$finance = $order ? tracking_payment_summary((int)$order['id']) : ['order_total'=>0,'paid_total'=>0,'balance'=>0,'payment_count'=>0,'first_payment'=>null,'last_payment'=>null];
$payments = $order ? tracking_confirmed_payments((int)$order['id']) : [];
$paymentReceipts = $order ? tracking_payment_receipts((int)$order['id']) : [];

function track_e(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function track_money(float $value): string { return '$' . number_format($value, 2, '.', ','); }
function track_date(?string $value, string $format='d/m/Y'): string {
    if (!$value) return 'Por confirmar';
    $ts = strtotime($value);
    return $ts ? date($format, $ts) : 'Por confirmar';
}
function track_company_address(array $company): string {
    $parts = [];
    foreach (['address','neighborhood','city','state','postal_code','country'] as $key) {
        $value = trim((string)($company[$key] ?? ''));
        if ($value !== '') $parts[] = $value;
    }
    return implode(' · ', $parts);
}
$companyDisplayName = trim((string)($company['trade_name'] ?? '')) !== ''
    ? (string)$company['trade_name']
    : ((string)($company['legal_name'] ?? '') !== '' ? (string)$company['legal_name'] : 'Colibrí Print');
$companyLegalName = trim((string)($company['legal_name'] ?? ''));
$companyAddress = track_company_address($company);
$companyPhone = trim((string)($company['phone'] ?? ''));
$companyEmail = trim((string)($company['email'] ?? ''));
$companyWebsite = trim((string)($company['website'] ?? ''));
$companyRfc = trim((string)($company['rfc'] ?? ''));
$companyWebsiteLabel = preg_replace('#^https?://#i', '', $companyWebsite);
$companyPhoneHref = preg_replace('/[^0-9+]/', '', $companyPhone);
$companyWhatsAppHref = preg_replace('/[^0-9]/', '', $companyPhone);
if ($companyWhatsAppHref !== '' && strlen($companyWhatsAppHref) === 10) $companyWhatsAppHref = '52' . $companyWhatsAppHref;
$pdfUrl = $order ? '/seguimiento_comprobante.php?t=' . rawurlencode($token) : '#';
$waUrl = ($order && $companyWhatsAppHref !== '') ? 'https://wa.me/' . $companyWhatsAppHref . '?text=' . rawurlencode('Hola Colibrí Print México, quiero consultar mi pedido ' . ($order['order_number'] ?? '') . '.') : '#';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Seguimiento de pedido | <?=track_e($companyDisplayName)?></title>
<link rel="stylesheet" href="/assets/css/seguimiento.css?v=20260919-6">
<link rel="stylesheet" href="/assets/css/seguimiento-recibo-v1.css?v=20260919-1">
<link rel="stylesheet" href="/assets/css/seguimiento-responsive-v2.css?v=20260919-2">
<link rel="stylesheet" href="/assets/css/payment-receipts.css?v=20260919-1">
</head>
<body>
<div class="tracking-page">
<?php if(isset($_GET['receipt_sent'])): ?><div class="tracking-card" style="border-color:#b9ecd5;background:#effbf6;margin-bottom:10px"><strong style="color:#0b6a4b">✓ Comprobante enviado correctamente.</strong><p style="margin:5px 0 0;color:#577467;font-size:11px">Quedó pendiente de revisión por Administración.</p></div><?php endif; ?>
<header class="tracking-company-header">
    <div class="tracking-company-top">
        <div class="tracking-company-brand">
            <?php if (!empty($company['logo_path'])): ?><div class="company-logo"><img src="<?=track_e($company['logo_path'])?>" alt="<?=track_e($companyDisplayName)?>"></div>
            <?php else: ?><div class="company-logo company-logo-fallback">🐦</div><?php endif; ?>
            <div class="company-identity">
                <strong><?=track_e($companyDisplayName)?></strong>
                <?php if ($companyLegalName !== '' && $companyLegalName !== $companyDisplayName): ?><span><?=track_e($companyLegalName)?></span><?php endif; ?>
                <small>Seguimiento de pedidos</small>
            </div>
        </div>
        <div class="company-header-badge">
            <span class="badge-dot"></span> Consulta segura
        </div>
    </div>
    <?php if ($companyAddress !== '' || $companyPhone !== '' || $companyEmail !== '' || $companyWebsite !== '' || $companyRfc !== ''): ?>
    <div class="tracking-company-details">
        <?php if ($companyAddress !== ''): ?><div class="company-address">📍 <?=track_e($companyAddress)?></div><?php endif; ?>
        <div class="contact-line">
            <?php if ($companyPhone !== ''): ?><a href="tel:<?=track_e($companyPhoneHref)?>">📞 <?=track_e($companyPhone)?></a><?php endif; ?>
            <?php if ($companyPhone !== '' && $companyWhatsAppHref !== ''): ?><a href="https://wa.me/<?=track_e($companyWhatsAppHref)?>" target="_blank" rel="noopener noreferrer">💬 WhatsApp</a><?php endif; ?>
            <?php if ($companyEmail !== ''): ?><a href="mailto:<?=track_e($companyEmail)?>">✉️ <?=track_e($companyEmail)?></a><?php endif; ?>
            <?php if ($companyWebsite !== ''): ?><a href="<?=track_e($companyWebsite)?>" target="_blank" rel="noopener noreferrer">🌐 <?=track_e($companyWebsiteLabel)?></a><?php endif; ?>
            <?php if ($companyRfc !== ''): ?><span>RFC: <?=track_e($companyRfc)?></span><?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</header>

<?php if (!$order): ?>
<section class="tracking-card not-found">
    <div class="big-icon">🔎</div>
    <h1>No encontramos este pedido</h1>
    <p>El enlace de seguimiento no es válido o ya no está disponible.</p>
</section>
<?php else: ?>
<section class="hero-card">
    <div>
        <span class="eyebrow">SEGUIMIENTO DE PEDIDO</span>
        <h1><?=track_e($order['order_number'])?></h1>
        <p><?=track_e($order['customer_name'] ?: 'Cliente')?> · <?=track_e($order['quote_number'] ?: 'Orden de servicio')?></p>
        <div class="tracking-tagline">En 1, 2 por 3, tu pedido listo. ✨</div>
    </div>
    <div class="current-pill"><span>Estado actual</span><strong><?=$clientStages[$currentClient]['icon']?> <?=track_e($clientStages[$currentClient]['label'])?></strong></div>
</section>
<section class="tracking-receipt">
  <div class="receipt-head">
    <div><span class="eyebrow">CONSTANCIA COMERCIAL PREVIA</span><h2>Recibo de servicio y anticipo</h2><p>Pedido <?=track_e($order['order_number'])?> · <?=track_date(date('Y-m-d'))?></p></div>
    <span class="receipt-badge"><?= $finance['paid_total'] > 0 ? 'ANTICIPO REGISTRADO' : 'ANTICIPO PENDIENTE' ?></span>
  </div>
  <div class="receipt-kpis">
    <div><span>Total del servicio</span><strong><?=track_money((float)$finance['order_total'])?></strong></div>
    <div class="paid"><span>Pagado / anticipo</span><strong><?=track_money((float)$finance['paid_total'])?></strong></div>
    <div class="balance"><span>Saldo pendiente</span><strong><?=track_money((float)$finance['balance'])?></strong></div>
  </div>
  <div class="receipt-service">
    <span class="eyebrow">SERVICIO SOLICITADO</span><h3>Descripción del servicio</h3>
    <?php if (!$items): ?><p class="muted">No hay conceptos registrados.</p><?php else: ?>
      <div class="receipt-items"><?php foreach($items as $item): ?><div class="receipt-item"><div><strong><?=nl2br(track_e((string)$item['description']))?></strong><small>Cantidad: <?=track_e((string)$item['quantity'])?></small></div><strong><?=track_money((float)$item['subtotal'])?></strong></div><?php endforeach; ?></div>
    <?php endif; ?>
  </div>
  <?php if ($finance['first_payment']): $fp=$finance['first_payment']; ?><div class="advance-confirmed"><span>💳</span><div><strong>Anticipo / primer pago registrado: <?=track_money((float)$fp['amount'])?></strong><small><?=track_e((string)$fp['method_label'])?> · <?=track_date((string)$fp['payment_date'])?><?php if(trim((string)$fp['reference'])!==''): ?> · Ref. <?=track_e((string)$fp['reference'])?><?php endif; ?></small></div></div>
  <?php else: ?><div class="advance-pending"><span>🧾</span><div><strong>Anticipo pendiente de registro</strong><small>Cuando Administración confirme el pago, aparecerá aquí y se reflejará en el comprobante PDF.</small></div></div><?php endif; ?>
  <?php if (count($payments)>1): ?><div class="payment-history-mini"><span class="eyebrow">PAGOS REGISTRADOS</span><div><?php foreach($payments as $pay): ?><span><?=track_date((string)$pay['payment_date'])?> · <?=track_money((float)$pay['amount'])?></span><?php endforeach; ?></div></div><?php endif; ?>
  <div class="receipt-actions"><a class="pdf" href="<?=track_e($pdfUrl)?>">📄 Descargar PDF como comprobante</a><?php if($waUrl!=='#'): ?><a class="wa" href="<?=track_e($waUrl)?>" target="_blank" rel="noopener noreferrer">💬 Consultar por WhatsApp</a><?php endif; ?></div>
  <p class="receipt-disclaimer">Esta constancia sirve como comprobante comercial previo del servicio solicitado y de los pagos confirmados en el sistema. No sustituye un CFDI.</p>
</section>

<section class="tracking-card payment-receipts-public" id="comprobantes-pago">
  <div class="section-title"><div><span class="eyebrow">PAGOS Y COMPROBANTES</span><h2>Comprobantes de pago</h2></div></div>
  <?php if($paymentReceipts): ?>
    <div class="public-receipt-links">
      <?php foreach($paymentReceipts as $receipt): ?>
        <a class="public-receipt-link" href="/comprobante_pago.php?t=<?=rawurlencode($token)?>&rid=<?=((int)$receipt['id'])?>">
          <span class="public-receipt-icon">🧾</span>
          <span><strong><?=track_e($receipt['original_name'])?></strong><small><?=track_e(ucfirst((string)$receipt['status']))?> · <?=track_date((string)$receipt['created_at'])?></small></span>
          <b>↗</b>
        </a>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="muted">Los comprobantes enviados para esta orden aparecerán aquí.</p>
  <?php endif; ?>

  <form class="public-receipt-upload" action="/api/comprobante_pago_cliente.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="tracking_token" value="<?=track_e($token)?>">
    <label><span>Enviar comprobante de pago</span><input type="file" name="receipt" accept="image/jpeg,image/png,image/webp,application/pdf" required></label>
    <label><span>Nota (opcional)</span><textarea name="note" rows="2" placeholder="Ej. Anticipo de la orden OS-2026-00004"></textarea></label>
    <button type="submit">🧾 Enviar comprobante</button>
    <small>JPG, PNG, WEBP o PDF · máximo 10 MB. El envío no confirma automáticamente el pago.</small>
  </form>
</section>

<section class="tracking-card">
  <div class="section-title"><div><span class="eyebrow">PROGRESO</span><h2>Así va tu pedido</h2></div></div>
    <div class="progress-line client-progress">
    <?php foreach ($clientStages as $key=>$stage): $idx=array_search($key,$clientKeys,true); $done=$idx < $currentIndex; $active=$key===$currentClient; ?>
        <div class="stage <?=($done?'done ':'').($active?'active':'')?>">
            <div class="stage-dot"><?=($done?'✓':$stage['icon'])?></div>
            <span><?=track_e($stage['label'])?></span>
        </div>
    <?php endforeach; ?>
    </div>
    <?php if ($showApproval): ?>
    <div class="approval-note <?=($currentInternal==='approval'?'is-current':'')?>">
        <div class="approval-note-icon">✅</div>
        <div><strong>Aprobación de diseño</strong><span><?=$currentInternal==='approval'?'Estamos esperando tu aprobación para continuar.':'El diseño ya pasó por esta etapa.'?></span></div>
    </div>
    <?php endif; ?>
</section>
<section class="two-col">
    <article class="tracking-card current-message">
        <span class="eyebrow">ACTUALIZACIÓN</span>
        <h2><?=$clientStages[$currentClient]['icon']?> <?=track_e($clientStages[$currentClient]['label'])?></h2>
        <p><?=nl2br(track_e($latestMessage))?></p>
        <small>Última actualización: <?=track_date($history ? $history[count($history)-1]['created_at'] : null,'d/m/Y H:i')?></small>
    </article>
    <article class="tracking-card order-summary">
        <span class="eyebrow">INFORMACIÓN</span>
        <div class="info-row"><span>Pedido</span><strong><?=track_e($order['order_number'])?></strong></div>
        <div class="info-row"><span>Fecha de pedido</span><strong><?=track_date($order['order_date'])?></strong></div>
        <div class="info-row"><span>Entrega estimada</span><strong><?=track_date($order['due_date'])?></strong></div>
        <div class="info-row total"><span>Total</span><strong><?=track_money((float)$order['total'])?></strong></div>
    </article>
</section>
<section class="tracking-card history">
    <div class="section-title"><div><span class="eyebrow">ACTUALIZACIONES</span><h2>Lo que ha pasado</h2></div></div>
    <?php if (!$clientHistory): ?><p class="muted">Aún no hay actualizaciones registradas.</p>
    <?php else: ?><div class="timeline">
        <?php foreach ($clientHistory as $entry): ?>
        <div class="timeline-item <?=($entry['key']==='approval'?'timeline-approval':'')?>">
            <div class="timeline-dot"><?=$entry['icon']?></div>
            <div><strong><?=track_e($entry['label'])?></strong><time><?=track_date($entry['created_at'],'d/m/Y H:i')?></time><?php if(trim((string)$entry['note'])!==''): ?><p><?=nl2br(track_e($entry['note']))?></p><?php endif; ?></div>
        </div>
        <?php endforeach; ?>
    </div><?php endif; ?>
</section>
<?php endif; ?>
<footer>
    <strong><?=track_e($companyDisplayName)?></strong>
    <?php if ($companyAddress !== ''): ?> · <?=track_e($companyAddress)?><?php endif; ?>
    <br><span>Seguimiento de pedido · La información mostrada es de carácter informativo y operativo.</span>
</footer>
</div>
</body>
</html>
