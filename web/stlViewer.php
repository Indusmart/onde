<?PHP
//////////////////////// Tratar todos os GET aqui para eviter injecao de codigo
///////////////////////////////////////////////////////////////// Tratando POST
///////////////////////////////////////////////////////////////////// Cabecalho
$useSessions = 1; $ehXML = 1;
//$headerTitle = "Página de gabarito";
include "iniset.php";
include "page_header.inc";
/////////////////////////////////////////////// GET passado para links (action)
if (isset($_GET['keyField']))
  $keyField    = pg_escape_string($conn, trim($_GET['keyField']));
else
  $keyField    = 'codigo';

if (isset($_GET['table']))
  $table       = pg_escape_string($conn, trim($_GET['table']));  
else
  $table       = 'Modelos 3D';

if (isset($_GET['field']))
  $field       = pg_escape_string($conn, trim($_GET['field']));  
else
  $field       = "Arquivo STL";

//echo"<script>console.log(' PASSEI ');\n</script>";
//echo"<script>console.log('field: " . pg_escape_string($conn, $_GET['field']) . "');\n</script>";
//echo"<script>console.log('field: " . $field . "');\n</script>";

if (isset($_GET['keyIsQuoted']))
  $keyIsQuoted = intval(trim($_GET['keyIsQuoted']));
else
  $keyIsQuoted = false; 

if ($keyIsQuoted)
  $keyValue  = pg_escape_string($conn, trim($_GET['keyValue']));
else
  $keyValue  = intval(trim($_GET['keyValue']));

//////////////////////////////////////////////////////////////// Funcoes locais
///////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////// Finaliza solicitacao
//////////////////////////////////////////////////////////// remove solicitacao
////////////////////////////////////////////////// Carrega solicitacao desejada
////////////////////////////////////////////////////////////// Monta formulario

echo "<script>\n";
echo "var model = 'formFieldDownload.php?table=" . $table . "&keyField=" . $keyField . "&keyValue=" . $keyValue . "&field=" . $field . "';\n";
//echo "var model = 'formFieldDownload.php?table=" . $table . "&keyField=" . $keyField . "&keyValue=" . $keyValue . "&field=Arquivo STL';\n";
//echo "var model = 'formFieldDownload.php?table=" . $table . "&keyField=" . $keyField . "&keyValue=" . $keyValue . "&field=Envie seu arquivo Aqui';\n";
//echo "var model = 'partenon.stl'\n;";

?>
</script>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>WebGL 3D Model Viewer Using three.js</title>  
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
  </head>
  <body>
    <script src="dependencies/Three.js-STL-Viewer/js/three.js"></script>
    <script src="dependencies/Three.js-STL-Viewer/js/STLLoader.js"></script>
    <script src="dependencies/Three.js-STL-Viewer/js/Detector.js"></script>
    <script src="dependencies/Three.js-STL-Viewer/js/OrbitControls.js"></script>
   
    <script src="dependencies/Three.js-STL-Viewer/js/handLoader.js"></script>
<script>
  var viewAngle = 22,
  near = 1,
  far = 500,
  radians = -Math.PI / 4;

  var fov = 90, aspect = 1;
  //var cameraPX = new THREE.PerspectiveCamera(fov,       aspect, near, far);
  //var camera =   new THREE.PerspectiveCamera(viewAngle, w / h,  near, far);
  //var camera =   new THREE.PerspectiveCamera(35, aspect,  near, far)
  //camera = new THREE.PerspectiveCamera( 35, window.innerWidth / window.innerHeight, 1, 500 );
//camera.rotation.order = 'YXZ';
//camera.rotation.y = radians;
//camera.rotation.x = Math.atan(-1 / Math.sqrt(2));
//camera.position.y = cameraY;
//camera.scale.addScalar(1);
//scene.add(camera);
</script>
  </body>
</html>
<?PHP
include "page_footer.inc";
?>
