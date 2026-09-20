<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/runtime.php';
require_once __DIR__ . '/../includes/actions.php';
require_auth();

$title='Solicitudes web de cotización';
$error=null;
$pdo=db();

function e2($v): string { return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!csrf_check($_POST['_csrf'] ?? null)){
        $error='La sesión expiró. Recarga la página.';
    } else {
        $id=(int)($_POST['id'] ?? 0);
        $status=(string)($_POST['status'] ?? '');
        if($id>0 && in_array($status,['new','reviewing','quoted','closed','spam'],true)){
            $st=$pdo->prepare('UPDATE cp_web_quote_requests SET status=?,updated_at=NOW() WHERE id=?');
            $st->execute([$status,$id]);
        }
    }
}

$rows=[];
try{
    $rows=$pdo->query(
        "SELECT id,request_token,service_key,customer_name,email,phone,quantity,desired_date,attachment_name,status,created_at,updated_at,request_text
         FROM cp_web_quote_requests
         ORDER BY id DESC LIMIT 100"
    )->fetchAll(PDO::FETCH_ASSOC);
}catch(Throwable $e){
    $error='No se pudieron cargar las solicitudes web.';
}

require __DIR__ . '/../includes/header.php';
?>
<style>
.cpq-admin{max-width:1280px;margin:0 auto}.cpq-admin-head{display:flex;justify-content:space-between;gap:20px;align-items:end;margin-bottom:22px}.cpq-admin-head h2{margin:5px 0}.cpq-admin-head p{margin:0;color:#8d9aaa}.cpq-admin-list{display:grid;gap:12px}.cpq-admin-card{background:#0e1822;border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:17px}.cpq-admin-top{display:flex;justify-content:space-between;gap:15px;align-items:start}.cpq-admin-top strong{font-size:17px}.cpq-admin-meta{display:flex;gap:12px;flex-wrap:wrap;color:#93a0ae;font-size:11px;margin-top:5px}.cpq-admin-meta b{color:#ffbf19}.cpq-admin-body{display:grid;grid-template-columns:1.5fr 1fr;gap:14px;margin-top:12px}.cpq-admin-preview{white-space:pre-wrap;color:#b6c1cd;background:#091018;padding:13px;border-radius:11px;font-size:11px;max-height:170px;overflow:auto}.cpq-admin-side{display:grid;gap:8px}.cpq-admin-side a,.cpq-admin-side button,.cpq-admin-side select{border:1px solid rgba(255,255,255,.12);background:#111c26;color:#fff;border-radius:9px;padding:10px 11px;font-weight:800;font-size:11px}.cpq-status-new{color:#ffbf19}.cpq-status-reviewing{color:#27bfff}.cpq-status-quoted{color:#19d38b}.cpq-status-closed{color:#9aa7b5}.cpq-status-spam{color:#ff5c76}@media(max-width:800px){.cpq-admin-head{align-items:start;flex-direction:column}.cpq-admin-body{grid-template-columns:1fr}}
</style>
<div class="cpq-admin">
  <div class="cpq-admin-head">
    <div><span class="eyebrow">ENTRADA PÚBLICA</span><h2>Solicitudes de cotización web</h2><p>Las solicitudes creadas por la pasarela pública aparecen aquí sin alterar automáticamente el precio de tus servicios.</p></div>
    <span class="count-pill"><?=count($rows)?></span>
  </div>

  <?php if($error): ?><div class="notice danger"><?=e2($error)?></div><?php endif; ?>

  <div class="cpq-admin-list">
  <?php foreach($rows as $row): ?>
    <?php
      $statusClass='cpq-status-'.preg_replace('/[^a-z]/','',strtolower((string)$row['status']));
      $reference='CPQ-'.str_pad((string)$row['id'],6,'0',STR_PAD_LEFT);
      $requestText=(string)$row['request_text'];
      $filePath='';
      if(preg_match('/\[CPQ_JSON\]\s*(\{.*)$/s',$requestText,$m)){
        $json=json_decode((string)$m[1],true);
        if(is_array($json) && !empty($json['attachment']['relative_path'])){
          $filePath=(string)$json['attachment']['relative_path'];
        }
      }
    ?>
    <article class="cpq-admin-card">
      <div class="cpq-admin-top">
        <div>
          <strong><?=e2($reference)?> · <?=e2($row['customer_name'])?></strong>
          <div class="cpq-admin-meta">
            <span><b>Servicio:</b> <?=e2($row['service_key'])?></span>
            <?php if($row['quantity']!==''): ?><span><b>Cantidad:</b> <?=e2($row['quantity'])?></span><?php endif; ?>
            <?php if($row['desired_date']): ?><span><b>Fecha:</b> <?=e2($row['desired_date'])?></span><?php endif; ?>
            <span><b>Recibido:</b> <?=e2(date('d/m/Y H:i',strtotime((string)$row['created_at'])))?></span>
          </div>
        </div>
        <strong class="<?=$statusClass?>"><?=e2(strtoupper((string)$row['status']))?></strong>
      </div>
      <div class="cpq-admin-body">
        <div class="cpq-admin-preview"><?=e2($requestText)?></div>
        <div class="cpq-admin-side">
          <?php if($row['phone']): ?><a href="https://wa.me/<?=e2(preg_replace('/\D+/','',(string)$row['phone']))?>?text=<?=rawurlencode('Hola '.(string)$row['customer_name'].', soy de Colibrí Print. Damos seguimiento a tu solicitud '.$reference.'.')?> " target="_blank" rel="noopener">💬 WhatsApp</a><?php endif; ?>
          <?php if($row['email']): ?><a href="mailto:<?=e2($row['email'])?>">✉ <?=e2($row['email'])?></a><?php endif; ?>
          <?php if($row['attachment_name']): ?>
  <div style="color:#aab5c2;font-size:10px">📎 <?=e2($row['attachment_name'])?></div>
  <?php if($filePath): ?><a href="<?=e2($filePath)?>" target="_blank" rel="noopener">Abrir archivo</a><?php endif; ?>
<?php endif; ?>
          <form method="post">
            <input type="hidden" name="_csrf" value="<?=e2(csrf_token())?>">
            <input type="hidden" name="id" value="<?=((int)$row['id'])?>">
            <select name="status" aria-label="Estado de solicitud">
              <?php foreach(['new'=>'Nueva','reviewing'=>'En revisión','quoted'=>'Cotizada','closed'=>'Cerrada','spam'=>'Spam'] as $k=>$label): ?>
              <option value="<?=e2($k)?>" <?=$row['status']===$k?'selected':''?>><?=e2($label)?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit">Guardar estado</button>
          </form>
        </div>
      </div>
    </article>
  <?php endforeach; ?>
  <?php if(!$rows): ?><div class="card"><p class="muted">Todavía no hay solicitudes web.</p></div><?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
