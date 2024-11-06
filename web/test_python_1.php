<?PHP

$python_path = "/home/indusmart/anaconda3/bin/python";
$command = $python_path . " --version";

$result = `pwd`;
echo "<PRE>";
var_dump($result);
echo "</PRE>";

$command = $python_path . " /var/www/onde-indusmart/occ/Step2STL.py /var/www/onde-indusmart/occ/flangenova.stp /tmp/flange.stl";

$result = `$command`;
echo "<PRE>";
var_dump($result);
echo "</PRE>";

$result = `ls -l /tmp`;
echo "<PRE>";
var_dump($result);
echo "</PRE>";
?>
