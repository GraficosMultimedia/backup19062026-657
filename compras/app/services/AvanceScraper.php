<?php
class AvanceScraper {
 public function fetch(string $url): array {
  $ctx=stream_context_create(['http'=>['method'=>'GET','timeout'=>30,'header'=>"User-Agent: Mozilla/5.0 ColibriCompras/1.0\r\n"],'ssl'=>['verify_peer'=>true,'verify_peer_name'=>true]]);
  $html=@file_get_contents($url,false,$ctx); if($html===false) throw new Exception('No se pudo consultar '.$url);
  $out=[]; if(preg_match_all('/(?:SKU|Código|Codigo)\s*[:#]?\s*([A-Z0-9_-]+).*?(?:\$\s*([0-9,]+(?:\.[0-9]+)?))/is',$html,$m,PREG_SET_ORDER)) foreach($m as $x)$out[]=['sku'=>$x[1],'precio'=>(float)str_replace(',','',$x[2]),'url'=>$url];
  return $out;
 }
}
