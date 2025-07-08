<?PHP
  //////////////////////// Tratar todos os GET aqui para eviter injecao de codigo
  ///////////////////////////////////////////////////////////////// Tratando POST
  /////////////////////////////////////////////// GET passado para links (action)
  ///////////////////////////////////////////////////////////////////// Cabecalho
$useSessions = 1; $ehXML = 0;
$headerTitle = "Developer > Controle do Gerenciador de filas";
include "iniset.php";
include "page_header.inc";
unset($clearGroups);
$clearGroups[] = 12; // LABCOMP
$clearGroups[] = 49; // Desenvolvedores
checkClearence($conn, $clearGroups);

echo "<BR>\n";
echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
echo "  <button id=start>START</button>\n";
echo "  <button id=stop>STOP</button>\n";

//echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
//echo "<A style=\"font-size: 6em; color:green;\" HREF=\"" . basename( $_SERVER['PHP_SELF']) . "?action=restart&toggle[]=developer\">[RESTART]</A>";

//echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
//echo "<A style=\"font-size: 6em; color:red;\" HREF=\"" . basename( $_SERVER['PHP_SELF']) . "?action=stop&toggle[]=developer\">[STOP]</A>";

echo "<BR>";

if (isset($_GET['action']))
  $action = trim($_GET['action']);
 else
   $action = 0;

if ($action){
  botConfSave($action);
 }

$log = carrega("../scripts/demandasBotOutput.log", 0);
//$log = "..." . substr( $log, strlen($log-20000), 2000);
echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
echo "<B>Log de execução do bot de demandas:</B>";
echo "<BR>\n";
echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
echo "    <TEXTAREA NAME=\"demandasBotOutputlog.php\" id='console_log' ROWS=\"15\" COLS=\"100\">";
echo $log;
echo "</TEXTAREA>";


// REVISAR ESTE ARRAY, jah tem um array com o mesmo nome no menu.inc
// Quando se utilizava frames nao tinha problema, mas agora eh include
// O runningBots daqui foi renomeado para currentRunningbots, mas
// isso deve ser revisto por questoes de performance.
$processes = trim(`ps auxw | grep php | grep Bot | grep -v ps`);
$processes = tiraQuebrasDeLinha($processes, "<LINHA>");
$processes = explode("<LINHA>", $processes);

//echo "<PRE>"; var_dump($currentRunningBots); echo "</PRE>\n";

$key = 0;
foreach ($processes as $process){
  $processArray = explode(" ", preg_replace("/(\s+)/", ' ', $process));

  if (stripos('_' . $processArray[10], 'php') && stripos('_' . $processArray[11], 'bot') ){
    $currentRunningBots[$key]['user']     = $processArray[0];
    $currentRunningBots[$key]['pid']      =  $processArray[1];
    $currentRunningBots[$key]['cpu']      =  $processArray[2];
    $currentRunningBots[$key]['mem']      =  $processArray[3];
    $currentRunningBots[$key]['vtz']      =  $processArray[4];
    $currentRunningBots[$key]['rss']      =  $processArray[5];
    $currentRunningBots[$key]['tty']      =  $processArray[6];
    $currentRunningBots[$key]['stat']     =  $processArray[7];
    $currentRunningBots[$key]['start']    =  $processArray[8];
    $currentRunningBots[$key]['time']     =  $processArray[9];
    $currentRunningBots[$key]['command']  =  $processArray[10];
    $currentRunningBots[$key]['argument'] =  $processArray[11];
    $key++;
    //echo "PASSEI";
    //echo $key;
    //echo $currentRunningBots[$key]['user'];
  }
}
//echo "<PRE>"; var_dump($processArray); echo "</PRE>\n";
//echo "<PRE>"; var_dump($currentRunningBots); echo "</PRE>\n";


$processes = trim(`ps auwx | grep wget | grep -v grep | grep -v "cd demandas"`);
$processes = tiraQuebrasDeLinha($processes, "<LINHA>");
$processes = explode("<LINHA>", $processes);

//echo "<PRE>"; var_dump($currentRunningBots); echo "</PRE>\n";

$key = 0;
foreach ($processes as $process){
  $processArray = explode(" ", preg_replace("/(\s+)/", ' ', $process));

  if (stripos('_' . $processArray[10], 'wget') ){//&& stripos('_' . $processArray[20], 'demandas') ){
    $currentWgets[$key]['user']     = $processArray[0];
    $currentWgets[$key]['pid']      =  $processArray[1];
    $currentWgets[$key]['cpu']      =  $processArray[2];
    $currentWgets[$key]['mem']      =  $processArray[3];
    $currentWgets[$key]['vtz']      =  $processArray[4];
    $currentWgets[$key]['rss']      =  $processArray[5];
    $currentWgets[$key]['tty']      =  $processArray[6];
    $currentWgets[$key]['stat']     =  $processArray[7];
    $currentWgets[$key]['start']    =  $processArray[8];
    $currentWgets[$key]['time']     =  $processArray[9];
    $currentWgets[$key]['command']  =  $processArray[10];
    $currentWgets[$key]['argument'] =  $processArray[11];
    $currentWgets[$key]['GETTING'] =   $processArray[20];
    // if (isset($processArray[12])) $currentWgets[$key]['argument'] +=  " " . $processArray[12];
    // if (isset($processArray[13])) $currentWgets[$key]['argument'] +=  " " . $processArray[13];
    // if (isset($processArray[14])) $currentWgets[$key]['argument'] +=  " " . $processArray[14];
    // if (isset($processArray[15])) $currentWgets[$key]['argument'] +=  " " . $processArray[15];
    // if (isset($processArray[16])) $currentWgets[$key]['argument'] +=  " " . $processArray[16];
    // if (isset($processArray[17])) $currentWgets[$key]['argument'] +=  " " . $processArray[17];
    // if (isset($processArray[18])) $currentWgets[$key]['argument'] +=  " " . $processArray[18];
    // if (isset($processArray[19])) $currentWgets[$key]['argument'] +=  " " . $processArray[19];
    // if (isset($processArray[20])) $currentWgets[$key]['argument'] +=  " " . $processArray[20];
    foreach($processArray as $indice => $valor){
      if ($indice > 11){
	//echo "-----------------------------------------------------------\n";
        //echo "\$processArray[" . $indice . "] = " . $valor . ";\n";
        //echo "\$currentWgets[\$key]['argument'] = " . $currentWgets[$key]['argument'] . "\n";
	//$currentWgets[$key]['argument'] .=  " " .  $valor;
	if (stripos($valor, "html")) $currentWgets[$key]['getting'] = $valor;
      }
    }
    $key++;
    //echo "PASSEI";
    //echo $key;
    //echo $currentWgets[$key]['user'];
  }
}
//echo "<PRE>"; var_dump($wgets); echo "</PRE>\n";

echo "<TABLE width=\"100%\">";
echo "<TR>";
echo "<TD>";
echo "<div id=\"processos\">\n";
foreach ($currentRunningBots as $bot){
  echo "<div class=\"subtitulo\">" . $bot['pid'] . "</div>\n";
  foreach ($bot as $key => $value){
    echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
    echo "<B>" . $key . ":</B> " . $value . "<BR>\n";
  }
}
echo "</div>";
echo "</TD>";
echo "<TD>";
echo "<div id=\"wgets\">\n";
foreach ($currentWgets as $wgets){
  echo "<div class=\"subtitulo\">" . $wgets['pid'] . "</div>\n";
  foreach ($wgets as $key => $value){
    echo "    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
    echo "<B>" . $key . ":</B> " . $value . "<BR>\n";
  }
}
echo "</div>";
echo "</TD>";
echo "</TR>";
echo "</TABLE>";
?>
  <script>
var c = 0;
var t;
var timer_is_on = 0;
<?PHP echo "var tamanho = " . intval(filesize("../scripts/demandasBotOutput.log")) . "\n";?>
  //var tamanho = 48617;

var dados = new Object();
dados['tamanho'] = 0;
dados['tail'] = '';
//dados['runningBots'] = ''

$(function() {
    $( "button" )
      .button()
      .click(function( event ) {
	  event.preventDefault();
	});
  });

var textarea = document.getElementById('console_log');
 textarea.scrollTop = textarea.scrollHeight;

function timedCountConsoleLog(){

  if (c==120) c = 0;
  c = c + 1;
  //document.getElementById("console_log").innerHTML = c;//'DENTRO\nId:' + teste + '\nID:' + id;
  //document.getElementById("console_log").innerHTML = document.getElementById("console_log").innerHTML + c + '\n';
  //textarea.scrollTop = textarea.scrollHeight;
  //alsert("passei");
                    
  $.getJSON("json_tail.php?", 
	    {tamanho: tamanho}, function(retorno){				
	      $.each(retorno, function(i, val){				  
		  dados[i] = val;
		});
				
	      //console.log(typeof dados['runningBots']);				

	      if (dados['runningBots'] != null && dados['runningBots']){
			  console.log('runningBots', dados['runningBots'][0]['pid']);
		      document.getElementById("onde_menu_link_demandasBotDisplay").innerHTML = 'demandasBot OK[' + dados['runningBots'][0]['pid'] + ']<BR>\n';
		  }
	      else{
		     document.getElementById("onde_menu_link_demandasBotDisplay").innerHTML = '<FONT COLOR=\"#FF0000\">demandasBot parado</FONT><BR>\n';
		  }
	      
	      document.getElementById("processos").innerHTML = '';      
	      if (dados['runningBots']){                                                                
		document.getElementById("processos").innerHTML = '<div class=\"subtitulo\">' + dados['runningBots'][0]['pid'] + '</div>\n';		
		$.each(dados['runningBots'], function(i, val){
		    $.each(val, function(indice, valor){
			document.getElementById("processos").innerHTML += '    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>' + indice + '</B>:' + valor +  '<BR>\n';				      
		      });
		  });		
		dados['runningBots'] = null;
	      }
	      
	      document.getElementById("wgets").innerHTML = '';      
	      if (dados['wgets']){                                                                
		document.getElementById("wgets").innerHTML = '<div class=\"subtitulo\">' + dados['wgets'][0]['pid'] + '</div>\n';		
		$.each(dados['wgets'], function(i, val){
		    $.each(val, function(indice, valor){
			document.getElementById("wgets").innerHTML += '    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B>' + indice + '</B>:' + valor +  '<BR>\n';				      
		      });
		  });
		
		dados['wgets'] = null;
	      }		
				
	      if (dados['tamanho'] > tamanho) {
		document.getElementById("console_log").innerHTML += dados['tail'];
		//console.log('dados[\'tamanho\'\]: ' + dados['tamanho']);
		//console.log('tamanho: ' + tamanho);
		tamanho = dados['tamanho'];
		textarea.scrollTop = textarea.scrollHeight;
	      }
              //console.log('passei' + dados['tail']);

				
	    });

  if (timer_is_on){
    t = setTimeout("timedCountConsoleLog()",500);
    //timer_is_on = 0; 
     }
  //else
  //   clearTimeout();
}

function doTimer() {
  if (!timer_is_on)  {
    timer_is_on = 1;
    timedCountConsoleLog();
  }
}

doTimer();

  $("#start").bind('click', function(){
      //alert('passei');
      //timer_is_on = 1;
      //timedCountConsoleLog();
      document.getElementById("console_log").innerHTML += '\n\naguarde enquanto o bot é inicializado (isso demora em torno de 2 min)...\n';
      textarea.scrollTop = textarea.scrollHeight;
      $.getJSON("json_tail.php?",  {action: 'restart'} );
      
    });
  $("#stop").bind('click', function(){
      //alert('passei');
      //timer_is_on = 0;
      //timedCountConsoleLog();
      document.getElementById("console_log").innerHTML += '\n\naguarde enquanto o bot é finalizado...\n';
      textarea.scrollTop = textarea.scrollHeight;
      $.getJSON("json_tail.php?",  {action: 'stop'} );
    });



</SCRIPT>
<?PHP
include "page_footer.inc";
/*
 USER = user owning the process
 PID = process ID of the process
 %CPU = It is the CPU time used divided by the time the process has been running.
 %MEM = ratio of the process's resident set size to the physical memory on the machine
 VSZ = virtual memory usage of entire process
 RSS = resident set size, the non-swapped physical memory that a task has used
 TTY = controlling tty (terminal)
 STAT = multi-character process state
 START = starting time or date of the process
 TIME = cumulative CPU time
 COMMAND = command with all its arguments

 Here are the different values that the s, stat and state output
 specifiers (header "STAT" or "S") will display to describe the state of
 a process.
 D    Uninterruptible sleep (usually IO)
 R    Running or runnable (on run queue)
 S    Interruptible sleep (waiting for an event to complete)
 T    Stopped, either by a job control signal or because it is being traced.
 W    paging (not valid since the 2.6.xx kernel)
 X    dead (should never be seen)
 Z    Defunct ("zombie") process, terminated but not reaped by its
 parent.

 For BSD formats and when the stat keyword is used, additional
 characters may be displayed:
 <    high-priority (not nice to other users)
 N    low-priority (nice to other users)
 L    has pages locked into memory (for real-time and custom IO)
 s    is a session leader
 l    is multi-threaded (using CLONE_THREAD, like NPTL pthreads do)
 +    is in the foreground process group

*/
?>