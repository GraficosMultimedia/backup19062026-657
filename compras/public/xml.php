<?php
require '../app/core/bootstrap.php';auth();require '../app/core/layout.php';
function xpathFirst(SimpleXMLElement $xml,string $name):?string{$a=$xml->xpath('//*[local-name()="'.$name.'"]');return $a&&isset($a[0])?(string)$a[0]:null;}
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();$files=$_FILES['xml']??null;$ok=0;$errors=[];
 if($files&&is_array($files['tmp_name'])) foreach($files['tmp_name'] as $i=>$tmp){if(($files['error'][$i]??1)!==UPLOAD_ERR_OK)continue;try{
  $raw=file_get_contents($tmp);$xml=simplexml_load_string($raw,'SimpleXMLElement',LIBXML_NONET|LIBXML_NOBLANKS);if(!$xml)throw new RuntimeException('XML inválido');
  $comp=($xml->xpath('//*[local-name()="Comprobante"]')[0]??$xml);$em=($xml->xpath('//*[local-name()="Emisor"]')[0]??null);$rec=($xml->xpath('//*[local-name()="Receptor"]')[0]??null);
  $uuid=xpathFirst($xml,'UUID');$rfcEm=(string)($em['Rfc']??'');$rfcRec=(string)($rec['Rfc']??'');$nombreEm=(string)($em['Nombre']??'Proveedor XML');$subtotal=(float)($comp['SubTotal']??0);$total=(float)($comp['Total']??0);$fecha=(string)($comp['Fecha']??'');$metodo=(string)($comp['MetodoPago']??'');$forma=(string)($comp['FormaPago']??'');$moneda=(string)($comp['Moneda']??'MXN');$tipo=(float)($comp['TipoCambio']??1);$serie=(string)($comp['Serie']??'');$folio=(string)($comp['Folio']??'');
  if($uuid){$du=$pdo->prepare('SELECT id FROM compras WHERE uuid=?');$du->execute([$uuid]);if($du->fetchColumn())throw new RuntimeException('El UUID ya está registrado.');}
  $q=$pdo->prepare('SELECT id FROM proveedores WHERE rfc=? LIMIT 1');$q->execute([$rfcEm]);$prov=$q->fetchColumn();if(!$prov){$q=$pdo->prepare('INSERT INTO proveedores(nombre,rfc) VALUES(?,?)');$q->execute([$nombreEm,$rfcEm?:null]);$prov=$pdo->lastInsertId();}
  $pdo->beginTransaction();$q=$pdo->prepare('INSERT INTO compras(proveedor_id,uuid,serie,folio,fecha_emision,fecha_recepcion,rfc_emisor,rfc_receptor,metodo_pago,forma_pago,moneda,tipo_cambio,subtotal,iva,total,estado,xml_path) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');$q->execute([$prov,$uuid,$serie,$folio,$fecha?:null,date('Y-m-d H:i:s'),$rfcEm?:null,$rfcRec?:null,$metodo?:null,$forma?:null,$moneda,$tipo,$subtotal,max(0,$total-$subtotal),$total,'pendiente',basename($files['name'][$i])]);$cid=(int)$pdo->lastInsertId();
  foreach(($xml->xpath('//*[local-name()="Conceptos"]/*[local-name()="Concepto"]')?:[]) as $c){$pdo->prepare('INSERT INTO conceptos(compra_id,clave_prod_serv,no_identificacion,descripcion,cantidad,unidad,valor_unitario,importe,descuento) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$cid,(string)$c['ClaveProdServ'],(string)$c['NoIdentificacion'],(string)$c['Descripcion'],(float)$c['Cantidad'],(string)$c['Unidad'],(float)$c['ValorUnitario'],(float)$c['Importe'],(float)$c['Descuento']]);}
  $dir=$config['storage'].'/xml';if(!is_dir($dir))mkdir($dir,0750,true);$safe=preg_replace('/[^a-zA-Z0-9_.-]/','_',basename($files['name'][$i]));file_put_contents($dir.'/'.$cid.'_'.$safe,$raw);$pdo->commit();$ok++;
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$errors[]=basename($files['name'][$i]).': '.$e->getMessage();}}
 flash('XML procesados: '.$ok.($errors?' | Errores: '.implode(' ; ',$errors):''),$errors?'warning':'success');redirect('xml.php');
}
head('Importar XML');
?><div class="card"><div class="card-body"><h2 class="h5">Importar CFDI</h2><p>Suba uno o varios XML. Se registran comprobante, proveedor y conceptos. Los pagos se registran en el módulo Pagos.</p><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=csrf()?>"><input class="form-control" type="file" name="xml[]" accept=".xml,text/xml" multiple required><button class="btn btn-primary mt-3">Procesar XML</button></form></div></div><?php foot();
