#!/usr/bin/php7.2
<?PHP
$useSessions = 0; $ehXML = 1;
$myPATH = ini_get('include_path') . ':./include:../include:../../include';
ini_set('include_path', $myPATH);
include "page_header.inc";

$strings[] = "tarugo";
#$strings[] = "fresa metal duro";
#$strings[] = "aço rápido";
#$strings[] = "pastilha";
#$strings[] = "suporte torneamento externo";

$t['tipos']['table name']   =  "Tipos de ferramentas";
$t['tipos']['column'] =  "nome";
$t['fabricantes']['table name']   =  "Fabricantes de ferramentas";
$t['fabricantes']['column'] =  "brand";

foreach ($t as $indice => $table){
  $query = "SELECT * from \"" . $table['table name'] . "\"";
  $result = pg_exec ($conn, $query);
  if ($result){
    $t[$indice]['conteudo'] = pg_fetch_all($result);
    foreach($t[$indice]['conteudo'] as $conteudo){  
      $t[$indice]['codigo'][$conteudo[$t[$indice]['column']]] = $conteudo['codigo'];
    }
  }
}

$page = 1;

$produto = "tarugo";
while(1){
  $command = "curl 'https://api.linximpulse.com/engage/search/v3/search?apiKey=ferramentasgerais&page=" . intval($page) . "&re7ultsPerPage=100&terms=" . $produto . "&sortBy=relevance' -H 'User-Agent: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:104.0) Gecko/20100101 Firefox/104.0' -H 'Accept: */*' -H 'Accept-Language: en-US,en;q=0.5' -H 'Accept-Encoding: gzip, deflate, br' -H 'Origin: https://www.fg.com.br' -H 'Connection: keep-alive' -H 'Sec-Fetch-Dest: empty' -H 'Sec-Fetch-Mode: no-cors' -H 'Sec-Fetch-Site: cross-site' -H 'TE: trailers' -H 'If-None-Match: W/\"36798-Iov9Zuw/5RlsJ5JTAMf7a7POXPw\"' -H 'Referer: https://www.fg.com.br/' -H 'Pragma: no-cache' -H 'Cache-Control: no-cache'   2>/dev/null | gunzip 2>/dev/null";
  $produtos = json_decode(`$command`, true);
  echo $page . "\n";
  $page++;
  //echo count($produtos) . "\n";
  if (!count($produtos)) break;
  
  foreach($produtos["products"] as $product){
    //echo "---------------------------------------------------------------------------------\n";
    echo $product["name"] . "\n";
    echo $product["price"] . "\n";
    ////echo $product["oldPrice"] . "\n";
    //echo $product["status"] . "\n";
    ///echo $product["categories"][1]["name"] . "\n";
    echo $product["categories"][2]["name"] . "\n";
    echo "codigo tipo: " . $t['tipo']['codigo'][$product["categories"][2]["name"]] . "\n";
    ////echo $product["skus"][0]["properties"]["details"]["measurement"]["multiplier"] . "\n";
    ////echo $product["skus"][0]["properties"]["details"]["measurement"]["unit"] . "\n";
    echo $product["details"]["brand"][0] . "\n";
    
    //echo $product["details"]["Descricao Mercado Livre"][0] . "\n";
    
    $descricao = tiraQuebrasDeLinha($product["details"]["Descricao Mercado Livre"][0], "|");
    
    $diametro_pol = substr($descricao, strpos($descricao, "-Diâmetro (pol): "), -1);
    $diametro_pol = substr($diametro_pol, 0, strpos($diametro_pol, "|"));
    $diametro_pol  = str_replace("-Diâmetro (pol): ", "", $diametro_pol);
    //echo $diametro_pol . "\n"; 

    $diametro_mm = substr($descricao, strpos($descricao, "-Diâmetro (mm): "), -1);
    $diametro_mm = substr($diametro_mm, 0, strpos($diametro_mm, "|"));
    $diametro_mm  = str_replace("-Diâmetro (mm): ", "", $diametro_mm);
    //echo $diametro_mm . "\n"; 

    $diametro_da_fresa_mm = substr($descricao, strpos($descricao, "-Diâmetro da fresa (mm): "), -1);
    $diametro_da_fresa_mm = substr($diametro_mm, 0, strpos($diametro_da_fresa_mm, "|"));
    $diametro_da_fresa_mm  = str_replace("-Diâmetro da fresa (mm): ", "", $diametro_da_fresa_mm);
    //echo $diametro_da_fresa_mm . "\n"; 

    $material = substr($descricao, strpos($descricao, "-Material (tipo/composição): "), -1);
    $material = substr($material, 0, strpos($material . "|", "|"));
    $material = str_replace("-Material (tipo/composição): ", "", $material);
    //echo $material . "\n"; 

    $cortes = substr($descricao, strpos($descricao, "Nº de cortes: "), -1);
    $cortes = substr($cortes, 0, strpos($cortes, "|"));
    $cortes = str_replace("Nº de cortes: ", "", $cortes);
    //echo $cortes . "\n"; 
  }
} 

include "page_footer.inc";
?>