<?PHP
require 'TumblPHP.php';

$T = new TumblPHP();
$T->loadData(file_get_contents('data.json'));
$T->loadTemplate('template.html');
$T->render(isset($_GET['source']));