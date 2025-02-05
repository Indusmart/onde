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
    if (isset($peca['raw'])){
			$fileArray = formsDecodeFile(base64_decode($peca['raw']));

			if (!file_exists( $workPath . "steps")) mkdir("./" . $workPath . "steps", 0777);  

			$step_filename = "./" . $workPath . "steps/" . fixField($fileArray['name']);
			$step_file = fopen($step_filename, "w");
			echo "nome do arquivo: " . $fileArray['name'] . "\n";
			echo "Sha1 do PHP : " . sha1($fileArray['contents']) . "\n";
			fputs($step_file, $fileArray['contents']);
			fclose($step_file);
			$updateSha1 = "UPDATE \"Peças\" set sha1_hash = '" . sha1($fileArray['contents']) . "' where codigo = " . $peca['codigo'];
      $result = pg_exec($conn, $updateSha1); 
      //$command = "sha1sum './steps/" . fixField($fileArray['name']) . "'";
      //echo "Sha1 do linux: " . `$command` . "\n";

			$copia =  `cp -vf $step_filename /home/indusmart/faceShot/uploaded`;
			//echo "</PRE>";

			$command = "echo 'Qw121314!' | su - indusmart -c 'export DISPLAY=:0.0; cd /home/indusmart/faceShot/; ./allViews.sh ./uploaded/" . fixField($fileArray['name']) . "'  2>&1";
			//$command = "echo 'Qw121314!' | su - indusmart -c 'export DISPLAY=:0.0; cd /home/indusmart/igie_builds_and_3rdParties/igie-install/bin/; ./partView.sh ./uploaded/" . fixField($fileArray['name']) . "' 2>/dev/null";
			$resultado = `$command`;
      //echo $resultado;
			if ($fileArray['contents']){
				$isometric_file_name = "/home/indusmart/faceShot/uploaded/"  . fixField($fileArray['name']) . "_isometric.png";
				$isometric['name'] = $fileArray['name'] . "_isometric.png";
				$isometric['type'] = "image/png";
				$isometric['contents'] = file_get_contents($isometric_file_name);
				$isometricData = formsEncodeFile($isometric);
        unset($isometric);
				
				$wireframe_file_name = "/home/indusmart/faceShot/uploaded/"  . fixField($fileArray['name']) . "_wireframe.png";
				$wireframe['name'] = $fileArray['name'] . "_wireframe.png";
				$wireframe['type'] = "image/png";
				$wireframe['contents'] = file_get_contents($wireframe_file_name);
				$wireframeData = formsEncodeFile($wireframe);
        unset($wireframe);
				
        echo "PASSEI\n";
				$gray_file_name = "/home/indusmart/faceShot/uploaded/"  . fixField($fileArray['name']) . "_gray.png";
				$gray['name'] = $fileArray['name'] . "_gray.png";
				$gray['type'] = "image/png";
				$gray['contents'] = file_get_contents($gray_file_name);
				$grayData = formsEncodeFile($gray);
        unset($gray);
        
  			$update_isometric = "UPDATE \"Peças\" set preview = '" . $isometricData . "' where codigo = " . $peca['codigo'];
        $result = pg_exec($conn, $update_isometric); 
  			$update_gray = "UPDATE \"Peças\" set preview_transparent = '" . $grayData . "' where codigo = " . $peca['codigo'];
        $result = pg_exec($conn, $update_gray); 
	  		$update_wireframe = "UPDATE \"Peças\" set preview_wireframe = '" . $wireframeData . "' where codigo = " . $peca['codigo'];
        $result = pg_exec($conn, $update_wireframe);
      }
			
		}
	}
}
echo "Terminei!!!\n";
include "page_footer.inc";
?>