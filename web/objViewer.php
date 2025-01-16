<?PHP
//////////////////////// Tratar todos os GET aqui para eviter injecao de codigo
///////////////////////////////////////////////////////////////// Tratando POST
/////////////////////////////////////////////// GET passado para links (action)
if (isset($_GET['keyField']))
  $keyField    = pg_escape_string(trim($_GET['keyField']));
else
  $keyField    = 'codigo';

if (isset($_GET['table']))
  $table       = pg_escape_string(trim($_GET['table']));  
else
  $table       = 'Modelos 3D';

if (isset($_GET['keyIsQuoted']))
  $keyIsQuoted = intval(trim($_GET['keyIsQuoted']));
else
  $keyIsQuoted = false; 

if ($keyIsQuoted)
  $keyValue  = pg_escape_string(trim($_GET['keyValue']));
else
  $keyValue  = intval(trim($_GET['keyValue']));

///////////////////////////////////////////////////////////////////// Cabecalho
$useSessions = 1; $ehXML = 1;
//$headerTitle = "Página de gabarito";
$myPATH = ini_get('include_path') . ':./include:../include:../../include';
ini_set('include_path', $myPATH);
include "page_header.inc";
//////////////////////////////////////////////////////////////// Funcoes locais
///////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////// Finaliza solicitacao
//////////////////////////////////////////////////////////// remove solicitacao
////////////////////////////////////////////////// Carrega solicitacao desejada
////////////////////////////////////////////////////////////// Monta formulario
?>

<!DOCTYPE html>
<html>
<head>

    <title>WebGL 3D Model Viewer Using three.js</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script src="dependencies/webgl-3d-model-viewer-using-three.js/three.js"></script>
    <script src="dependencies/webgl-3d-model-viewer-using-three.js/Detector.js"></script>
    <script src="dependencies/webgl-3d-model-viewer-using-three.js/OrbitControls.js"></script>
    <script src="dependencies/webgl-3d-model-viewer-using-three.js/OBJLoader.js"></script>
    <script src="dependencies/webgl-3d-model-viewer-using-three.js/MTLLoader.js"></script>

    <style>
        body {
            overflow: hidden;
            margin: 0;
            padding: 0;
            background: hsl(0, 100%, 100%);
        }

        p {
            margin: 0;
            padding: 0;
        }

        .left,
        .right {
            position: absolute;
            color: #fff;
            font-family: Geneva, sans-serif;
        }

        .left {
            bottom: 1em;
            left: 1em;
            text-align: left;
        }

        .right {
            top: 0;
            right: 0;
            text-align: right;
        }

        a {
            color: #f58231;
        }
    </style>

</head>
<body>
<!--
    <div class="left">
        <p>Low-Poly Croupière<p>
        <p><a href="https://manu.ninja/" target="_top">manu.ninja</a></p>
    </div>

    <a class="right" href="https://github.com/Lorti/webgl-3d-model-viewer-using-three.js" target="_top">
        <img src="https://camo.githubusercontent.com/652c5b9acfaddf3a9c326fa6bde407b87f7be0f4/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f6f72616e67655f6666373630302e706e67">
    </a>
-->
    <script>

        if (!Detector.webgl) {
            Detector.addGetWebGLMessage();
        }

        var container;

        var camera, controls, scene, renderer;
        var lighting, ambient, keyLight, fillLight, backLight;
        var lowerKeyLight, lowerFillLight, lowerBackLight;

        init();
        animate();

        function init() {

            container = document.createElement('div');
            document.body.appendChild(container);

            /* Camera */

	    camera = new THREE.PerspectiveCamera(10, window.innerWidth / window.innerHeight, 1, 8000);
	    //camera = new THREE.PerspectiveCamera(0.1,                      window.innerWidth / window.innerHeight,                                  1, 8000);
	    //camera = new THREE.PerspectiveCamera(window.innerWidth / - 2, window.innerWidth / 2, window.innerHeight / 2, window.innerHeight / - 2, 1, 1000 );

        camera.position.z = 1100;
	camera.position.x = 1200;
	camera.position.y = 1200;

  	    //camera.fov = 150;
            //camera.fov = 0.1; // simulations
	    //camera.fov = 60; // 3d models
            camera.updateProjectionMatrix();
	
            /* Scene */

            scene = new THREE.Scene();
            lighting = true;

            ambient = new THREE.AmbientLight(0xffffff, 1.0);
            scene.add(ambient);

            //keyLight = new THREE.DirectionalLight(new THREE.Color('hsl(30, 100%, 75%)'), 1.0);
            keyLight = new THREE.DirectionalLight(0xffffff, 0.25);
            keyLight.position.set(-100, 100, 100);
						
            lowerKeyLight = new THREE.DirectionalLight(0xffffff, 0.25);
            lowerKeyLight.position.set(-100, -100, 100);

            //fillLight = new THREE.DirectionalLight(new THREE.Color('hsl(250, 100%, 75%)'), 0.75);
            fillLight = new THREE.DirectionalLight(0xffffff, 0.25);
            fillLight.position.set(100, 100, 100);

            lowerFillLight = new THREE.DirectionalLight(0xffffff, 0.25);
            lowerFillLight.position.set(100, -100, 100);

            backLight = new THREE.DirectionalLight(0xffffff, 0.25);
            backLight.position.set(100, 100, -100).normalize();

            lowerBackLight = new THREE.DirectionalLight(0xffffff, 0.25);
            lowerBackLight.position.set(100, -100, -100).normalize();

            /* Model */

            var mtlLoader = new THREE.MTLLoader();
            //mtlLoader.setBaseUrl('dependencies/webgl-3d-model-viewer-using-three.js/assets/');
            //mtlLoader.setPath('dependencies/webgl-3d-model-viewer-using-three.js/assets/');
            mtlLoader.setBaseUrl('./');
            mtlLoader.setPath('./');
	    
            //mtlLoader.load('prt_corpo_impressora.mtl', function (materials) {
	    <?PHP
echo "            mtlLoader.load('formFieldDownload.php?table=" . $table . "&keyField=" . $keyField . "&keyValue=" . $keyValue . "&field=Arquivo MTL', function (materials) {\n";xs
?>
	        console.log('passei 1');
                materials.preload();
	        console.log('passei 2');

                //materials.materials.default.map.magFilter = THREE.NearestFilter;
                //materials.materials.default.map.minFilter = THREE.LinearFilter;
					console.log(materials.materials);
                var objLoader = new THREE.OBJLoader();
                objLoader.setMaterials(materials);
                objLoader.setPath('./');
	              console.log('passei 3');
	
                //objLoader.load('prt_corpo_impressora.obj', function (object) {
		<?PHP
		echo "                objLoader.load('formFieldDownload.php?table=" . $table . "&keyField=" . $keyField . "&keyValue=" . $keyValue . "&field=Arquivo OBJ', function (object) {\n";
?>
				console.log(object);
       object.traverse(function (child) {
					 console.log("tranversing...", child.type);
					 if (child.type === 'Mesh') console.log("material: ", child.material.name);
           if (child.isMesh) {
               console.log('Mesh found:', child.name);
               console.log('Material:', child.material);
           }
       });
				scene.add(object);

                });

            });

            /* Renderer */

            renderer = new THREE.WebGLRenderer();
            renderer.setPixelRatio(window.devicePixelRatio);
            renderer.setSize(window.innerWidth, window.innerHeight);
//renderer.setClearColor(new THREE.Color("hsl(0, 0%, 10%)"));
<?PHP
if (strpos("_" . strtoupper($_theme), "TRON"))
  echo "        renderer.setClearColor(new THREE.Color(\"hsl(0, 0%, 10%)\"));\n";
else
  echo "        renderer.setClearColor(new THREE.Color(\"hsl(0, 100%, 100%)\"));\n";
?>

            container.appendChild(renderer.domElement);

            /* Controls */

            controls = new THREE.OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.25;
        controls.enableZoom = true;

	// This option actually enables dollying in and out; left as "zoom" for
	// backwards compatibility
	controls.zoomSpeed = 1.0;

	// Limits to how far you can dolly in and out ( PerspectiveCamera only )
	controls.minDistance = 0;
	controls.maxDistance = Infinity;

	// Limits to how far you can zoom in and out ( OrthographicCamera only )
	controls.minZoom = 0;
	controls.maxZoom = Infinity;

	// Set to true to disable this control
	controls.enableRotate = true;
	controls.rotateSpeed = 1.0;

	// Set to true to disable this control
	controls.enablePan = true;
	controls.keyPanSpeed = 7.0;	// pixels moved per arrow key push

	// Set to true to automatically rotate around the target
	controls.autoRotate = false;
	controls.autoRotateSpeed = 2.0; // 30 seconds per round when fps is 60

	// How far you can orbit vertically, upper and lower limits.
	// Range is 0 to Math.PI radians.
	controls.minPolarAngle = 0; // radians
	controls.maxPolarAngle = Math.PI; // radians

	// How far you can orbit horizontally, upper and lower limits.
	// If set, must be a sub-interval of the interval [ - Math.PI, Math.PI ].
	controls.minAzimuthAngle = - Infinity; // radians
	controls.maxAzimuthAngle = Infinity; // radians

	// Set to true to disable use of the keys
	controls.enableKeys = true;

	// The four arrow keys
	controls.keys = { LEFT: 37, UP: 38, RIGHT: 39, BOTTOM: 40 };

	// Mouse buttons
	//controls.mouseButtons = { ORBIT: THREE.MOUSE.RIGHT, PAN: THREE.MOUSE.MIDDLE };
	controls.mouseButtons = { ORBIT: THREE.MOUSE.LEFT, ZOOM: THREE.MOUSE.MIDDLE, PAN: THREE.MOUSE.RIGHT };
	////////////
	// internals

	

            /* Events */

            window.addEventListener('resize', onWindowResize, false);
            window.addEventListener('keydown', onKeyboardEvent, false);

                    ambient.intensity = 0.25;
                    scene.add(keyLight);
                    scene.add(fillLight);
                    scene.add(backLight);

                    scene.add(lowerKeyLight);
                    scene.add(lowerFillLight);
                    scene.add(lowerBackLight);
}

        function onWindowResize() {

            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();

            renderer.setSize(window.innerWidth, window.innerHeight);

        }

        function onKeyboardEvent(e) {

            if (e.code === 'KeyL') {

                lighting = !lighting;

                if (lighting) {

                    ambient.intensity = 0.25;
                    scene.add(keyLight);
                    scene.add(fillLight);
                    scene.add(backLight);

                } else {

                    ambient.intensity = 1.0;
                    scene.remove(keyLight);
                    scene.remove(fillLight);
                    scene.remove(backLight);

                }

            }

        }

        function animate() {

            requestAnimationFrame(animate);

            controls.update();

            render();

        }

        function render() {

            renderer.render(scene, camera);

        }

    </script>

</body>
</html>


<?PHP
include "page_footer.inc";
?>
