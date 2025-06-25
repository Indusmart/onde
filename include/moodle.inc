<?PHP

/**
 * Autentica no moodle e retorna
 * sucesso ou erro
 * Nome
 * token
 * email 
 * moodle id
 */
function authMoodle($workDir){
  global $path_to_wget, $URL_moodle, $_debug;

  $commandLine  = "cd " . $workDir . "; ";//Entra no diretorio
  $commandLine .= $path_to_wget . " " . ($_no_check_moodle_certificate ? "--no-check-certificate" : "");
  $commandLIne .= " --timeout=10 -t 3 -q " . $URL_moodle;
  $commandLine .= " -O - ";
  $result = `$commandLine`;
  
  return $result;
}

function moodleGetToken($workDir, $moodleData){
  global $path_to_wget, $URL_moodle, $_debug, $path_to_grep, $path_to_tr, $path_to_cut, $_no_check_moodle_certificate;

  //echo "work dir: " . $workDir . "<BR>";
  //echo "<PRE>"; var_dump($moodleData); echo "</PRE>";

  if (!fixField($moodleData['usuario'])) return 0;

  $moodleTokenCommand  = "cd " . $workDir . "; " . $path_to_wget . " ";
  $moodleTokenCommand .= ($_no_check_moodle_certificate ? "--no-check-certificate" : "") . " ";
  $moodleTokenCommand .= "--save-cookies cookies_moodle" . fixField($moodleData['usuario']) . ".txt --keep-session-cookies ";
  $moodleTokenCommand .= " " . $URL_moodle . " -O - -q ";
  //$moodleTokenCommand .= "--server-response";
  //$moodleTokenCommand .= " 2>&1 1>/dev/null ";
  $moodleTokenCommand .= " | ";
  $moodleTokenCommand .= $path_to_grep . " token | ";
  $moodleTokenCommand .= $path_to_tr . " -s \" \" | ";
  $moodleTokenCommand .= $path_to_cut . " -d \" \" -f 27 |";
  $moodleTokenCommand .= $path_to_cut . " -d '\"' -f 2";
  
  //echo "<PRE>" . $moodleTokenCommand . "</PRE>";

  $token = `$moodleTokenCommand`;
  $token = trim(tiraQuebrasDeLinha($token, " "));

  $moodleData['token'] = $token;
  return $moodleData;
}

function moodleLogin($workDir, $moodleData){
  global $path_to_wget, $URL_moodle, $_debug, $path_to_grep, $path_to_tr, $path_to_cut, $_no_check_moodle_certificate;

  $moodleIdCommand  = "cd " . $workDir . "; " . $path_to_wget . " --load-cookies cookies_moodle" . fixField($moodleData['usuario']) . ".txt ";
  $moodleIdCommand .= ($_no_check_moodle_certificate ? "--no-check-certificate" : "") . " ";
  $moodleIdCommand .= "--save-cookies cookies_moodle" . fixField($moodleData['usuario']) . ".txt --keep-session-cookies --post-data ";
  $moodleIdCommand .= "'anchor=&logintoken=" . $moodleData['token'] . "&username=" . $moodleData['usuario'] . "&password=";
  $moodleIdCommand .= $moodleData['senha'] . "' " . $URL_moodle . "/login/index.php  -O - -q ";
  $moodleIdCommand .= "--server-response";
  $moodleIdCommand .= " 2>&1 1>/dev/null ";
  $moodleIdCommand .= " | ";
  $moodleIdCommand .= $path_to_grep . " -i location |";
  $moodleIdCommand .= $path_to_grep . " -i testsession | ";
  $moodleIdCommand .= $path_to_tr . " -s \" \" | ";
  $moodleIdCommand .= $path_to_cut . " -d '=' -f 2";

  ////echo `$moodleIdCommand`;
  //echo "<PRE>" . $moodleIdCommand . "</PRE>";
  $moodleid=`$moodleIdCommand`;

  //echo "<PRE>" . htmlentities($moodleid) . "</PRE>";

  $moodleid = trim(tiraQuebrasDeLinha($moodleid, " "));
  $moodleData['id'] = $moodleid;
  return $moodleData;
}

//echo "username=" . $username . "\n";
//echo "password=" . $password . "\n";
//echo "logintoken=" . $token . "\n";
//echo "moodleid=" . $moodleid . "\n";

function moodleGetProfileData($workDir, $moodleData){
  global $path_to_wget, $URL_moodle, $_debug, $path_to_grep, $path_to_tr, $path_to_cut, $_no_check_moodle_certificate;

  $moodleProfileCommand  = "cd " . $workDir . "; " . $path_to_wget . " --load-cookies cookies_moodle" . fixField($moodleData['usuario']) . ".txt ";
  $moodleProfileCommand .= ($_no_check_moodle_certificate ? "--no-check-certificate" : "") . " ";
  $moodleProfileCommand .= "--save-cookies cookies_moodle" . fixField($moodleData['usuario']) . ".txt --keep-session-cookies ";
  $moodleProfileCommand .= $URL_moodle . "/user/profile.php?id=" . $moodleData['id'] . " -O - -q;";

  ////$moodleProfileCommand .= " | " . $path_to_grep . " -i list-group-item ";
  //$moodleProfileCommand .= " | " . $path_to_grep . " -i \"Endereço de e-mail\" ";
  ////$moodleProfileCommand .= $path_to_tr . " -s \" \" | ";
  ////$moodleProfileCommand .= $path_to_cut . " -d ">" -f 27";

  //echo "<PRE>" . $moodleProfileCommand . "</PRE>";
  //echo "<PRE>" . `$moodleProfileCommand` . "</PRE>";

  $profile=`$moodleProfileCommand`;
  $profile = trim(tiraQuebrasDeLinha($profile, " "));

  //echo "Posicao: " . strpos($profile, "Endereço") ."<BR>";

  $endereco_de_email = substr(preg_replace('/.*?(Endereço de e-mail).*?\">(.*?<).*/', "$2", $profile), 0, -1);
  $nome = substr(preg_replace('/.*?(profilepic).*?(<h3>)(.*?<).*/', "$3", $profile), 0, -1);
  $disciplinas_raw = preg_replace('/.*?(id="cursos")(.*?(details)).*/', "$2", $profile);
  $disciplinas_raw = preg_replace('/.*?(<h5.*?>)(.*?<)/', "$2", $disciplinas_raw);
  $disciplinas_raw = explode("<", $disciplinas_raw);

  $i = 0;   
  foreach($disciplinas_raw as $nomeDisciplina){
    if ($nomeDisciplina=='/h5>') break;

    $disciplinas[$i]['titulo'] = $nomeDisciplina;
    $disciplinaArray = explode(" - ", $nomeDisciplina);
    $disciplinas[$i]['semestre'] = preg_replace('/.*?([0-9][0-9][0-9][0-9]\/[0-9]).*/', "$1", $nomeDisciplina);
    if (strlen($disciplinas[$i]['semestre'])>6) $disciplinas[$i]['semestre'] = "";
    $disciplinas[$i]['codcred'] = preg_replace('/([0-9][0-9][0-9][A-Z0-9][A-Z0-9]-[0-9][0-9]).*/', "$1", $nomeDisciplina);
    if (strlen($disciplinas[$i]['codcred'])>9) $disciplinas[$i]['codcred']="";
    if ($disciplinas[$i]['codcred']) $disciplinas[$i]['nome'] = $disciplinaArray[1];
    $i++;
  }

    //echo "<PRE>" . $profile . "</PRE>";
  $moodleData['profile'] = $profile;
  $moodleData['email'] = $endereco_de_email;
  $moodleData['nome']=$nome;
  $moodleData['disciplinas']=$disciplinas;
  return $moodleData;
}


function moodleGetProfileExtendedData($workDir, $moodleData){
  global $path_to_wget, $URL_moodle, $_debug, $path_to_grep, $path_to_tr, $path_to_cut, $_no_check_moodle_certificate;

  $moodleProfileCommand  = "cd " . $workDir . "; " . $path_to_wget . " --load-cookies cookies_moodle" . fixField($moodleData['usuario']) . ".txt ";
  $moodleProfileCommand .= ($_no_check_moodle_certificate ? "--no-check-certificate" : "") . " ";
  $moodleProfileCommand .= "--save-cookies cookies_moodle" . fixField($moodleData['usuario']) . ".txt --keep-session-cookies '";
  $moodleProfileCommand .= $URL_moodle . "/user/edit.php?id=" . $moodleData['id'] . "&returnto=profile' -O - -q;";

  ////$moodleProfileCommand .= " | " . $path_to_grep . " -i list-group-item ";
  //$moodleProfileCommand .= " | " . $path_to_grep . " -i \"Endereço de e-mail\" ";
  ////$moodleProfileCommand .= $path_to_tr . " -s \" \" | ";
  ////$moodleProfileCommand .= $path_to_cut . " -d ">" -f 27";

  //echo "<PRE>" . $moodleProfileCommand . "</PRE>";
  //echo "<PRE>" . `$moodleProfileCommand` . "</PRE>";

  $profile=`$moodleProfileCommand`;
  $profile = trim(tiraQuebrasDeLinha($profile, " "));

  //echo "Posicao: " . strpos($profile, "id_idnumber") ."<BR>";

  $matricula = substr(preg_replace('/.*?(id_alternatename).*?value=\"(.*?\").*/', "$2", $profile), 0, -1);
  $matricula2 = substr(preg_replace('/.*?(id_idnumber).*?value=\"(.*?\").*/', "$2", $profile), 0, -1);
  $posicao = substr(preg_replace('/.*?(id_institution).*?value=\"(.*?\").*/', "$2", $profile), 0, -1);
  $cidade = substr(preg_replace('/.*?(id_city).*?value=\"(.*?\").*/', "$2", $profile), 0, -1);

  //echo "<textarea>";
  //echo $posicao;
  //echo "</textarea>";


    //echo "<PRE>" . $profile . "</PRE>";
  $moodleData['matricula'] = $matricula;
  $moodleData['matricula2'] = $matricula2;
  $moodleData['posicao']=$posicao;
  $moodleData['cidade']=$cidade;
  return $moodleData;
}

