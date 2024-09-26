#!/usr/bin/php7.2
<?PHP
$useSessions = 0; $ehXML = 1;
$myPATH = ini_get('include_path') . ':./include:../include:../../include';
ini_set('include_path', $myPATH);
include "page_header.inc";

//$produtos = json_decode(carrega("ferramental/fg_hss.json", 0), true);
//$produtos = json_decode(carrega("ferramental/fg_hss_100.json", 0), true);
$produtos = json_decode(carrega("ferramental/fg_fresa.json", 0), true);
//$produtos = json_decode(carrega("ferramental/fg_metal_duro.json", 0), true);
//$produtos = json_decode(carrega("ferramental/fg_pastilha.json", 0), true);
//$produtos = json_decode(carrega("ferramental/fg_pastilha_100.json", 0), true);
//$produtos = json_decode(carrega("ferramental/fg_suporte_torneamento_externo.json", 0), true);

//var_dump($hss["products"]);

foreach($produtos["products"] as $product){
    echo "---------------------------------------------------------------------------------\n";
    echo $product["name"] . "\n";
    echo $product["price"] . "\n";
    //echo $product["oldPrice"] . "\n";
    echo $product["status"] . "\n";
    //echo $product["categories"][1]["name"] . "\n";
    echo $product["categories"][2]["name"] . "\n";
    //echo $product["skus"][0]["properties"]["details"]["measurement"]["multiplier"] . "\n";
    //echo $product["skus"][0]["properties"]["details"]["measurement"]["unit"] . "\n";
    echo $product["details"]["brand"][0] . "\n";
    
    //echo $product["details"]["Descricao Mercado Livre"][0] . "\n";
    
    $descricao = tiraQuebrasDeLinha($product["details"]["Descricao Mercado Livre"][0], "|");
    
    $diametro_pol = substr($descricao, strpos($descricao, "-Diâmetro (pol): "), -1);
    $diametro_pol = substr($diametro_pol, 0, strpos($diametro_pol, "|"));
    $diametro_pol  = str_replace("-Diâmetro (pol): ", "", $diametro_pol);
    echo $diametro_pol . "\n"; 

    $diametro_mm = substr($descricao, strpos($descricao, "-Diâmetro (mm): "), -1);
    $diametro_mm = substr($diametro_mm, 0, strpos($diametro_mm, "|"));
    $diametro_mm  = str_replace("-Diâmetro (mm): ", "", $diametro_mm);
    echo $diametro_mm . "\n"; 

    $material = substr($descricao, strpos($descricao, "-Material (tipo/composição): "), -1);
    $material = substr($material, 0, strpos($material . "|", "|"));
    $material = str_replace("-Material (tipo/composição): ", "", $material);
    echo $material . "\n"; 

    $cortes = substr($descricao, strpos($descricao, "Nº de cortes: "), -1);
    $cortes = substr($cortes, 0, strpos($cortes, "|"));
    $cortes = str_replace("Nº de cortes: ", "", $cortes);
    echo $cortes . "\n"; 
}


include "page_footer.inc";
?>