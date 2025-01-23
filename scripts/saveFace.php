#!/usr/bin/php
<?PHP
//////////////////////// Tratar todos os GET aqui para eviter injecao de codigo
///////////////////////////////////////////////////////////////// Tratando POST
/////////////////////////////////////////////// GET passado para links (action)
///////////////////////////////////////////////////////////////////// Cabecalho
$useSessions = 0; $ehXML = 1;
$myPATH = ini_get('include_path') . ':./include:../include:../../include';
ini_set('include_path', $myPATH);
include "page_header.inc";
//////////////////////////////////////////////////////////////// Funcoes locais
///////////////////////////////////////////////////////////////////////////////

    error_reporting(E_ALL);
    ini_set('display_errors','On');

$workPath = "";
$query = "select codigo, encode(\"Modelo CAD (STEP)\", 'base64') as raw from \"Peças\"";
$result = pg_exec($conn, $query);
if ($result){
	$pecas = pg_fetch_all($result);
	foreach($pecas as $peca){
		echo "Peça " . $peca['codigo'] . "\n";
    $fileArray = formsDecodeFile(base64_decode($peca['raw']));

		if (!file_exists( $workPath . "models")) mkdir("./" . $workPath . "models", 0777);  

		$step_filename = "./" . $workPath . "models/" . fixField($fileArray['name']);
    $step_file = fopen($step_filename, "w");
    fputs($step_file, $fileArray['contents']);
    fclose($step_file);

		$queryFaces = "select distinct \"Observações\" from processos_peca where usuario = 'igie'";
    $resultFaces = pg_exec($conn, $queryFaces);
		$processos = pg_fetch_all($resultFaces);
		foreach ($processos as $processo){
			$face = trim(substr($processo['Observações'], strpos($processo['Observações'], ":")+1, -1)) . "\n";
			
			if (strpos(",", $face)){
				$subfaces = explode(",", $face);
				foreach($subfaces as $subface){
					$peca['faces'][] = intval(trim($subface));
				  echo intval(trim($subface)) . "\n";
          if (intval(trim($subface)) == 0) { echo "sai\n"; exit();}
				}
			}
			else{
			  if (intval(trim($face))) $peca['faces'][] = intval(trim($face));
      }
		}
		foreach($peca['faces'] as $face){
      //echo $face . "\n";
      $command  = $path_to_python . " step2obj.py ";
      $command .= $step_filename . " " . $face . " " . $step_filename . "_" . $face .	".obj" . " 2>&1" . "\n";
      $result = `$command`;
      //echo $command;
      if (file_exists($step_filename . "_" . $face . ".obj")){
				$obj_file = fopen($step_filename . "_" . $face . ".obj", "r");
				$mtl_file = fopen($step_filename . "_" . $face . ".mtl", "r");
				if ($obj_file){
					$obj = "";
					while (!feof($obj_file))
						$obj .= fgets($obj_file);
					fclose($obj_file);
				}
				if ($mtl_file){
					$mtl = "";
					while (!feof($mtl_file))
						$mtl .= fgets($mtl_file);
					fclose($mtl_file);
				}
				if ($obj_file){
					$objFileArray['name'] = $step_filename . "_" . $face . ".obj";
					$objFileArray['type'] = "text/plain";
					$objFileArray['contents'] = $obj;
					$objFileData = formsEncodeFile($objFileArray);
					$mtlFileArray['name'] = $step_filename . "_" . $face . ".mtl";
					$mtlFileArray['type'] = "text/plain";
					$mtlFileArray['contents'] = $mtl;
					$mtlFileData = formsEncodeFile($mtlFileArray);

					$query_insert  = "INSERT INTO \"Modelos 3D\" (nome, \"Arquivo OBJ\", \"Arquivo MTL\", \"Peça\", face) \n";
					$query_insert .= "  VALUES (\n";
					$query_insert .= "    '" . $step_filename . " face " . $face . "',\n";
					$query_insert .= "    '" . $objFileData . "',\n";
					$query_insert .= "    '" . $mtlFileData . "',\n";
					$query_insert .= "    " . $peca['codigo'] . ",\n";
					$query_insert .= "    " . $face . "\n";
					$query_insert .= "  )\n";

					//echo $query_insert . "\n";
					$result = pg_exec($conn, $query_insert);
					if (!$result) echo pg_last_error() . "\n";
				}
			}
		}
	}
}
echo "Terminei!!!\n";
include "page_footer.inc";
?>