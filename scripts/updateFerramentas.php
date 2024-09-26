#!/usr/bin/php7.2
<?PHP
$useSessions = 0; $ehXML = 1;
$myPATH = ini_get('include_path') . ':./include:../include:../../include';
ini_set('include_path', $myPATH);
include "page_header.inc";

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
var_dump($t['fabricantes']['codigo']);

include "page_footer.inc";
?>