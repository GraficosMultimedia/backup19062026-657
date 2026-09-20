<?php
declare(strict_types=1);
require_once __DIR__ . '/config/runtime.php';
require_once __DIR__ . '/includes/company.php';

$company = company_profile();
$pdo = db();

function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }

function cp_base_path(): string {
    static $base = null;
    if ($base !== null) return $base;
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string)$_SERVER['DOCUMENT_ROOT']) : false;
    $projectRoot = realpath(__DIR__);
    if ($documentRoot && $projectRoot) {
        $doc = rtrim(str_replace('\\','/',$documentRoot), '/');
        $root = str_replace('\\','/',$projectRoot);
        if ($root === $doc) return $base = '';
        $prefix = $doc . '/';
        if (str_starts_with($root, $prefix)) {
            $relative = trim(substr($root, strlen($prefix)), '/');
            return $base = $relative !== '' ? '/' . $relative : '';
        }
    }
    $script = str_replace('\\','/',(string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $candidate = trim(dirname($script), '/.');
    return $base = $candidate !== '' ? '/' . $candidate : '';
}
function cp_url(string $path = '', array $query = []): string {
    $path = trim($path);
    if ($path !== '' && preg_match('#^(?:https?:)?//#i', $path)) $url = $path;
    else $url = cp_base_path() . '/' . ltrim($path, '/');
    $query = array_filter($query, static fn($v) => $v !== null && $v !== '');
    return $query ? $url . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : $url;
}
function cp_origin(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}
function cp_absolute(string $path = ''): string { return cp_origin() . cp_url($path); }
function public_asset(?string $path): string {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (preg_match('#^(?:https?:)?//#i', $path)) return $path;
    return cp_url($path);
}
function money_mx($value): string { return is_numeric($value) ? '$' . number_format((float)$value, 2, '.', ',') . ' MXN' : ''; }
function wa_url(string $phone, string $message): string {
    $raw = preg_replace('/\D+/', '', $phone);
    if ($raw !== '' && !str_starts_with($raw, '52')) $raw = '52' . $raw;
    return $raw !== '' ? 'https://wa.me/' . $raw . '?text=' . rawurlencode($message) : '#';
}

$brand = trim((string)($company['trade_name'] ?? '')) ?: 'Colibrí Print';
$phone = (string)($company['phone'] ?? '');
$email = (string)($company['email'] ?? '');
$location = trim((string)($company['city'] ?? 'Hidalgo del Parral')) . ', ' . trim((string)($company['state'] ?? 'Chihuahua'));
$wa = wa_url($phone, 'Hola Colibrí Print México, quiero cotizar un proyecto.');
$noindex = cp_base_path() !== '';

$categories = $pdo->query("SELECT c.id,c.name,
    (SELECT i.path FROM cp_products p INNER JOIN cp_product_images i ON i.product_id=p.id AND i.enabled=1 WHERE p.category_id=c.id AND p.enabled=1 AND p.visible_web=1 ORDER BY p.id DESC,i.sort_order,i.id LIMIT 1) image_path
    FROM cp_categories c WHERE c.type='product' AND c.enabled=1 ORDER BY c.sort_order,c.name LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

$products = $pdo->query("SELECT p.id,p.name,p.description,p.sale_price,p.pricing_type,p.sku,c.name category_name,
    (SELECT i.path FROM cp_product_images i WHERE i.product_id=p.id AND i.enabled=1 ORDER BY i.sort_order,i.id LIMIT 1) image_path
    FROM cp_products p LEFT JOIN cp_categories c ON c.id=p.category_id
    WHERE p.enabled=1 AND p.visible_web=1 ORDER BY p.id DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

$promotions = $pdo->query("SELECT pr.id,pr.title,pr.label,pr.description,pr.normal_price,pr.promo_price,pr.end_date,pr.image_path,
    (SELECT pi.path FROM cp_promotion_products pp2 INNER JOIN cp_product_images pi ON pi.product_id=pp2.product_id AND pi.enabled=1 WHERE pp2.promotion_id=pr.id ORDER BY pp2.sort_order,pi.sort_order,pi.id LIMIT 1) product_image
    FROM cp_promotions pr WHERE pr.show_web=1 AND pr.status <> 'draft' AND pr.start_date <= CURDATE() AND (pr.end_date IS NULL OR pr.end_date >= CURDATE())
    ORDER BY pr.id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

$services = [
 ['title'=>'Branding y Diseño Gráfico Pro','short'=>'Imagen de marca que se reconoce.','text'=>'Corporativo, logos, isologos, papelería, catálogos y piezas publicitarias pensadas para comunicar con claridad.','color'=>'pink'],
 ['title'=>'Letras Corpóreas y Corte CNC','short'=>'Volumen que se hace notar.','text'=>'Letras tridimensionales y logotipos independientes en acrílico, metal, plástico o madera.','color'=>'yellow'],
 ['title'=>'Grabado Láser de Alta Precisión','short'=>'Detalle que deja huella.','text'=>'Personalización de promocionales, placas, reconocimientos y termos con acabados de alta precisión.','color'=>'blue'],
 ['title'=>'Sellos de Goma Autoentintables','short'=>'Tu identidad también firma.','text'=>'Sellos Trodat y soluciones autoentintables en distintos tamaños para uso comercial y administrativo.','color'=>'green'],
 ['title'=>'Impresión Comestible','short'=>'Diseño que también se disfruta.','text'=>'Oblea de azúcar de colores intensos y oblea de arroz o papa para repostería, celebraciones y eventos.','color'=>'orange'],
 ['title'=>'Textiles, Promocionales y Souvenirs','short'=>'Tu marca, en todas partes.','text'=>'Playeras, tazas, termos, vasos, tarros, botellas, cojines, rompecabezas, fotobotones y viniles.','color'=>'red'],
];

$companyLogoText = trim((string)($company['trade_name'] ?? 'Colibrí Print'));
$heroImage = cp_url('assets/img/home/hero-colibri-v1.png');
$collageImage = cp_url('assets/img/home/category-collage-v1.png');
$markImage = cp_url('assets/img/home/colibri-mark-v1.png');
$canonical = cp_absolute('');
$metaDescription = 'Diseño, impresión, grabado láser, corte CNC, textiles y productos personalizados en Hidalgo del Parral, Chihuahua.';

$schema = [
 '@context'=>'https://schema.org','@type'=>'LocalBusiness','name'=>$brand,'url'=>$canonical,'description'=>$metaDescription,
 'telephone'=>$phone ?: null,'image'=>$canonical.'assets/img/home/hero-colibri-v1.png',
 'address'=>['@type'=>'PostalAddress','streetAddress'=>(string)($company['address'] ?? ''),'addressLocality'=>(string)($company['city'] ?? ''),'addressRegion'=>(string)($company['state'] ?? ''),'postalCode'=>(string)($company['postal_code'] ?? ''),'addressCountry'=>'MX']
];
$schema = array_filter($schema, static fn($v)=>$v!==null&&$v!=='');
$robots = $noindex ? 'noindex,nofollow,noarchive' : 'index,follow';
?>
<!doctype html>
<html lang="es-MX">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="<?= h($metaDescription) ?>">
<meta name="robots" content="<?= h($robots) ?>">
<meta name="theme-color" content="#08090d">
<link rel="canonical" href="<?= h($canonical) ?>">
<meta property="og:title" content="Colibrí Print México | Imprimimos tus ideas">
<meta property="og:description" content="<?= h($metaDescription) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= h($canonical) ?>">
<meta property="og:image" content="<?= h($canonical.'assets/img/home/hero-colibri-v1.png') ?>">
<title>Colibrí Print México | Imprimimos tus ideas</title>
<link rel="stylesheet" href="<?= h(cp_url('assets/css/home-v5.css')) ?>?v=20260919-1">
<link rel="stylesheet" href="<?= h(cp_url('assets/css/pasarela-cotizador.css')) ?>?v=20260919-3">
<link rel="stylesheet" href="<?= h(cp_url('assets/css/home-legibility-v5.css')) ?>?v=20260919-1">
<script defer src="<?= h(cp_url('assets/js/home-v5.js')) ?>?v=20260919-1"></script>
<script type="application/ld+json"><?= json_encode($schema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>

<div class="topbar">
  <div class="shell topbar-inner">
    <div class="topbar-group">
      <?php if($phone): ?><a href="tel:<?= h($phone) ?>"><span class="ico">☎</span><?= h($phone) ?></a><a href="<?= h($wa) ?>" target="_blank" rel="noopener"><span class="ico">◔</span><?= h($phone) ?></a><?php endif; ?>
      <?php if($email): ?><a href="mailto:<?= h($email) ?>"><span class="ico">✉</span><?= h($email) ?></a><?php endif; ?>
      <span><span class="ico">⌖</span><?= h((string)($company['address'] ?? 'C. Alemania 87')) ?>, <?= h($location) ?></span>
    </div>
    <div class="topbar-tag">IMPRIMIMOS TUS IDEAS <span class="rainbow"></span></div>
  </div>
</div>

<header class="header" id="inicio">
  <div class="shell nav-row">
    <a class="brand" href="<?= h(cp_url('')) ?>" aria-label="Colibrí Print México">
      <img src="<?= h($markImage) ?>" alt="" class="brand-mark">
      <span class="brand-copy"><strong>Colibrí <b>Print</b></strong><small>MÉXICO · SOLUCIONES GRÁFICAS</small></span>
    </a>

    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mainMenu"><span>MENÚ</span><i>☰</i></button>
    <nav class="nav" id="mainMenu" aria-label="Navegación principal">
      <a class="active" href="#inicio">Inicio</a>
      <a href="#servicios">Servicios</a>
      <a href="<?= h(cp_url('catalogo.php')) ?>">Catálogo</a>
      <a href="#promociones">Promociones</a>
      <a href="#proceso">Cómo trabajamos</a>
      <a href="#nosotros">Nosotros</a>
      <a href="#contacto">Contacto</a>
    </nav>

    <div class="nav-actions">
      <span class="search-pill">⌕</span>
      <span class="location-pill"><b>⌖</b><span><strong><?= h($location) ?></strong><small>Atención en todo México</small></span></span>
      <?php if($phone): ?><a class="nav-cta" href="<?= h($wa) ?>" target="_blank" rel="noopener">Cotizar ahora <span>→</span></a><?php endif; ?>
    </div>
  </div>
</header>

<main id="contenido">
<section class="hero" style="--hero:url('<?= h($heroImage) ?>')">
  <div class="hero-bg"></div>
  <div class="paint paint-red"></div><div class="paint paint-yellow"></div><div class="paint paint-blue"></div><div class="paint paint-green"></div>
  <div class="shell hero-inner">
    <div class="hero-copy reveal">
      <div class="eyebrow">SOLUCIONES GRÁFICAS PARA TU MARCA</div>
      <h1><span>IMPRIMIMOS</span><strong>TUS <em>IDEAS</em></strong><b>FABRICAMOS <i>TU MARCA</i></b></h1>
      <p>Diseño, impresión, grabado láser, corte CNC y productos personalizados para convertir lo que imaginas en algo que puedas tocar.</p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= h(cp_url('catalogo.php')) ?>">▣ <span>Ver catálogo</span> <b>→</b></a>
        <?php if($phone): ?><a class="btn btn-outline" href="<?= h($wa) ?>" target="_blank" rel="noopener">◔ <span>WhatsApp</span> <b>↗</b></a><?php endif; ?>
      </div>
      <div class="hero-checks"><span>✦ Calidad</span><span>✦ Diseño</span><span>✦ Producción</span><span>✦ Entrega</span></div>
    </div>

    <div class="hero-art reveal delay-1">
      <div class="hero-art-card"><img src="<?= h($heroImage) ?>" alt="Productos y procesos de Colibrí Print" fetchpriority="high"></div>
      <span class="bubble bubble-laser">GRABADO<br><b>LÁSER</b></span>
      <span class="bubble bubble-cnc">CORTE<br><b>CNC</b></span>
      <span class="bubble bubble-brand">TU MARCA<br><b>AQUÍ</b></span>
    </div>
  </div>

  <div class="shell trust-strip reveal delay-2">
    <div><b>+<?= (int)$pdo->query('SELECT COUNT(*) FROM cp_customers')->fetchColumn() ?></b><span>Clientes registrados</span></div>
    <div><b>+<?= (int)$pdo->query('SELECT COUNT(*) FROM cp_orders')->fetchColumn() ?></b><span>Órdenes registradas</span></div>
    <div><b>100%</b><span>Atención personalizada</span></div>
    <div><b>MX</b><span>Atención en todo México</span></div>
  </div>
</section>

<section class="category-band" id="categorias">
  <div class="shell">
    <div class="section-head light reveal"><div><div class="eyebrow">LO HACEMOS REAL</div><h2>Imprime. Personaliza. <span>Destaca.</span></h2></div><a href="<?= h(cp_url('catalogo.php')) ?>">Ver todo <b>→</b></a></div>
    <div class="category-grid">
      <?php
      $fallbackNames=['IMPRESIÓN DIGITAL','GRAN FORMATO','SUBLIMACIÓN','CORTE CNC','GRABADO LÁSER','ARTÍCULOS PERSONALIZADOS'];
      foreach($fallbackNames as $idx=>$name):
        $category=$categories[$idx] ?? null;
        $label = $category['name'] ?? $name;
        $href = $category ? cp_url('catalogo.php',['categoria'=>(int)$category['id']]) : cp_url('catalogo.php');
        $pos = ['0% 0%','100% 0%','0% 50%','100% 50%','0% 100%','100% 100%'][$idx];
        $accent=['red','yellow','blue','green','red2','orange'][$idx];
      ?>
      <a class="category-card reveal" href="<?= h($href) ?>" style="--pos:<?= h($pos) ?>;--accent:var(--<?= h($accent) ?>)">
        <div class="category-image" style="background-image:url('<?= h($collageImage) ?>');"></div>
        <div class="category-overlay"></div>
        <div class="category-content"><small>0<?= $idx+1 ?></small><strong><?= h($label) ?></strong><span>Explorar <b>→</b></span></div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="services" id="servicios">
  <div class="service-glow service-glow-red"></div><div class="service-glow service-glow-blue"></div>
  <div class="shell">
    <div class="section-head light reveal"><div><div class="eyebrow">NUESTRAS ESPECIALIDADES</div><h2>Mucho más que <span>impresión.</span></h2><p>Servicios profesionales que se cotizan según materiales, medidas, cantidades, acabados y alcance del proyecto.</p></div><a class="outline-link" href="<?= h(cp_url('servicios.php')) ?>">Ver servicios <b>→</b></a></div>
    <div class="service-grid">
      <?php foreach($services as $i=>$service): ?>
      <article class="service-card reveal" style="--accent:var(--<?= h($service['color']) ?>)">
        <div class="service-no">0<?= $i+1 ?></div><div class="service-icon">✦</div>
        <h3><?= h($service['title']) ?></h3><p><?= h($service['short']) ?></p><p class="service-detail"><?= h($service['text']) ?></p>
        <a href="<?= h($wa) ?>" target="_blank" rel="noopener">Cotizar este servicio <b>→</b></a>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/pasarela_cotizador.php'; ?>

<section class="products" id="productos">
  <div class="shell">
    <div class="section-head reveal"><div><div class="eyebrow">CATÁLOGO PROPIO</div><h2>Productos para <span>comprar o personalizar.</span></h2></div><a href="<?= h(cp_url('catalogo.php')) ?>">Abrir catálogo <b>→</b></a></div>
    <div class="product-grid">
      <?php foreach($products as $product): $image=public_asset($product['image_path']??''); $fixed=$product['pricing_type']==='fixed' && $product['sale_price']!==null; ?>
      <article class="product-card reveal">
        <a class="product-media" href="<?= h(cp_url('producto.php',['id'=>(int)$product['id']])) ?>">
          <?php if($image): ?><img src="<?= h($image) ?>" alt="<?= h($product['name']) ?>" loading="lazy"><?php else: ?><span>CP</span><?php endif; ?>
          <small><?= $fixed ? 'PRECIO DEFINIDO' : 'PERSONALIZABLE' ?></small>
        </a>
        <div class="product-body"><div class="product-cat"><?= h($product['category_name'] ?: 'Colibrí Print') ?></div><h3><?= h($product['name']) ?></h3><p><?= h(trim((string)($product['description'] ?? '')) ?: 'Producto personalizable de Colibrí Print México.') ?></p><div class="product-foot"><strong><?= $fixed ? h(money_mx($product['sale_price'])) : 'Cotización' ?></strong><a href="<?= h(cp_url('producto.php',['id'=>(int)$product['id']])) ?>">Ver detalle <b>→</b></a></div></div>
      </article>
      <?php endforeach; if(!$products): ?><div class="empty">El catálogo propio todavía no tiene productos publicados.</div><?php endif; ?>
    </div>
  </div>
</section>

<?php if($promotions): ?>
<section class="promos" id="promociones">
  <div class="shell"><div class="section-head light reveal"><div><div class="eyebrow">PROMOCIONES ACTIVAS</div><h2>Hay ofertas que vale la pena <span>aprovechar.</span></h2></div><a href="<?= h(cp_url('catalogo.php')) ?>">Ver catálogo <b>→</b></a></div>
  <div class="promo-grid">
    <?php foreach($promotions as $promo): $image=public_asset($promo['image_path'] ?: ($promo['product_image'] ?? '')); ?>
      <article class="promo-card reveal"><div class="promo-image"><?php if($image): ?><img src="<?= h($image) ?>" alt="<?= h($promo['title']) ?>" loading="lazy"><?php endif; ?><span><?= h($promo['label'] ?: 'OFERTA') ?></span></div><div class="promo-body"><small><?= $promo['end_date'] ? 'Hasta '.date('d/m/Y',strtotime((string)$promo['end_date'])) : 'Por tiempo limitado' ?></small><h3><?= h($promo['title']) ?></h3><?php if(!empty($promo['description'])): ?><p><?= h((string)$promo['description']) ?></p><?php endif; ?><div><strong><?= $promo['promo_price']!==null ? h(money_mx($promo['promo_price'])) : 'Consultar' ?></strong><?php if($promo['normal_price']!==null): ?><del><?= h(money_mx($promo['normal_price'])) ?></del><?php endif; ?></div></div></article>
    <?php endforeach; ?>
  </div></div>
</section>
<?php else: ?>
<section class="quote-banner" id="cotiza-banner"><div class="shell quote-banner-inner reveal"><div><div class="eyebrow">PROMOCIONES Y PROYECTOS ESPECIALES</div><h2>¿Tienes una idea que no aparece en el catálogo?</h2><p>Cuéntanos qué necesitas y preparamos la ruta de cotización.</p></div><a class="btn btn-primary" href="<?= h($wa) ?>" target="_blank" rel="noopener">Hablar con Colibrí <b>↗</b></a></div></section>
<?php endif; ?>

<section class="about" id="nosotros"><div class="shell about-grid"><div class="about-card reveal"><div class="eyebrow">COLIBRÍ PRINT MÉXICO</div><h2>Imprimimos tus ideas. <span>Fabricamos tu marca.</span></h2><p>Diseño, producción y personalización desde Hidalgo del Parral, Chihuahua, con atención para proyectos locales y de todo México.</p><div class="about-chips"><span>Diseño</span><span>Impresión</span><span>Grabado</span><span>CNC</span><span>Textiles</span><span>Promocionales</span></div></div><div class="about-art reveal"><img src="<?= h($heroImage) ?>" alt="Estudio de producción de Colibrí Print" loading="lazy"><div class="about-stamp">TU PROYECTO<br><b>COBRA VIDA</b></div></div></div></section>
</main>

<footer class="footer" id="contacto">
  <div class="shell footer-grid"><div><div class="footer-brand"><img src="<?= h($markImage) ?>" alt=""><div><strong>Colibrí <b>Print</b></strong><small>MÉXICO · SOLUCIONES GRÁFICAS</small></div></div><p><?= h((string)($company['address'] ?? 'C. Alemania 87')) ?> · <?= h($location) ?></p></div><div><strong>Contacto</strong><?php if($phone): ?><a href="tel:<?= h($phone) ?>"><?= h($phone) ?></a><?php endif; ?><?php if($email): ?><a href="mailto:<?= h($email) ?>"><?= h($email) ?></a><?php endif; ?></div><div><strong>Explora</strong><a href="#servicios">Servicios</a><a href="<?= h(cp_url('catalogo.php')) ?>">Catálogo</a><a href="#proceso">Cómo trabajamos</a></div><div><strong>¿Listo?</strong><a class="footer-cta" href="<?= h($wa) ?>" target="_blank" rel="noopener">Cotizar ahora →</a></div></div>
  <div class="shell footer-bottom"><span>© <?= date('Y') ?> <?= h($brand) ?>. Todos los derechos reservados.</span><span>Imprimimos tus ideas <b class="rainbow"></b></span></div>
</footer>

<a class="float-wa" href="<?= h($wa) ?>" target="_blank" rel="noopener" aria-label="Cotizar por WhatsApp">◔</a>
<script defer src="<?= h(cp_url('assets/js/pasarela-cotizador.js')) ?>?v=20260919-3"></script>
</body>
</html>
