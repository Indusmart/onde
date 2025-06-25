<?PHP
$deps = array("wget", "inkscape", "convert", "gnuplot", "octave", "tex",
              "dvipng", "temp_dir", "javac", "java", "enscript", "rsvg-convert",
              "montage", "sed", "mv", "cp", "ssh", "ypcat", "pngquant", "python");
sort($deps);

 function escapeConfVars($conn){
   global $banco, $banco_cadastro, $usuario_banco, $senha_banco, 
     $email, $fone, $developer, $_debug, $_singleQueue, $_remoteAssets, $_theme,
     $verificaEmail, $encoding, $mem_limit, $max_execution_time, $deps;
     
  foreach($deps as $keydep => $valdep){
    $dep[$keydep] = trim(str_replace("-", "_", $valdep));
    $variable_name = "path_to_" . $valdep;
    global $$variable_name;
  }

  $email =  pg_escape_string($conn, trim($email));
  $fone =  pg_escape_string($conn, trim($fone));

  foreach($developer as $key => $val){
    $developer[$key] = pg_escape_string($conn, trim($val));
  }
  
  $_debug = intval(trim($_debug));
  $_singleQueue = intval(trim($_singleQueue));
  $_remoteAssets = intval(trim($_remoteAssets));
  $_theme =   pg_escape_string($conn, trim($_theme));
  $verificaEmail = intval(trim($verificaEmail));
  $encoding =  pg_escape_string($conn, trim($encoding));
  $mem_limit =  intval(trim($mem_limit));
  $max_execution_time =  intval(trim($max_execution_time));

  foreach($deps as $keydep => $valdep){
    $dep[$keydep] = trim(str_replace("-", "_", $valdep));
    $variable_name = "path_to_" . $valdep;
		if ($$variable_name <> null)
      $$variable_name = pg_escape_string($conn, trim($$variable_name));
  }
 }
?>