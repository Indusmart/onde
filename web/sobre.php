<?PHP
 /**
  * $Id: sobre.php,v 1.30 2018/06/05 15:34:26 filipi Exp $
  */
  //$headerTitle = "Sobre";
$useSessions = 1; $ehXML = 0;

if ($useSessions){
  ini_set('session.save_path',"../session_files");
  session_name('onde');
  session_start();
  if(!(isset($_SESSION['h_log']) && 
       isset($_SESSION['matricula']))){
    $useSessions = 0;
    session_destroy();
    $withoutMenu[] = "sobre.php";
  }
  else
    $useSessions = 1; 
}
$myPATH = ini_get('include_path') . ':./include:../include:../../include';
ini_set('include_path', $myPATH);
include "page_header.inc";
echo "<BR>\n";
?>
<CENTER>
<H1><?PHP echo $SYSTEM_NAME; ?> <?PHP echo $SYSTEM_VERSION; ?></H1>
<DIV class=coment>
  Desenvolvido por Filipi Vianna<BR>
  Junho de 2022<?PHP /*<img src = "images/cake.png">*/ ?>
</DIV>
<BR>
  O <?PHP echo $SYSTEM_NAME; ?> utiliza como base a <a href="https://github.com/filipi/onde" target="_blank"><I>fLameWork</I> - O.N.D.E.</a><BR>
<DIV class=coment>O.N.D.E.: ONDE Não foi Desenvolvida para Estética<BR>
<BR>
Versão da <I>fLameWork</I> - O.N.D.E. <B><?PHP echo $ONDE_VERSION; ?></B><BR>
de Janeiro de 2003 à Junho de 2018 (17 anos)<BR><BR>
Contribuiram para a criação desta <I>fLameWork</I><BR>
Eduardo da Silva Pereira (<I>testes extensivos, migração do menu estático para dinâmico</I>)<BR>
Bruno Henrique Bueno (<I>documentação e testes</I>)<BR>
Bruno Cortopassi Trindade (<I>documentação</I>)<BR>
Felipe Schiefferdecker Karpouzas (<I>documentação</I>)<BR>
Henrique Damasceno Vianna (<I>mecanismos para prevenir SQL injection</I>)<BR>
Guilherme Reschke (<I>mecanismo de login e geração de PDFs</I>)<BR>
Marcelo Rodrigues Schmitz<BR>
Gustavo Henrique Leal<BR>
Filipi Damasceno Vianna
<?PHP
if ($isdeveloper){
  echo "<BR><BR>\n";
  echo "Base de Dados: " . $DATABASE_VERSION . "<BR>\n";
  echo "PHP: " . str_replace(PHP_EXTRA_VERSION, "", phpversion()) . "<BR>\n";
  $pg_version = pg_fetch_row(pg_exec($conn, "show server_version"), 0);
  echo "PostgreSQL: " . $pg_version[0]  . "<BR>\n";
 }
echo "</DIV>\n";
echo "</CENTER>\n";
echo "<BR>\n";
//Versão do Banco de Dados - <B><?PHP echo $DATABASE_VERSION; </B>
include "page_footer.inc";
?>
