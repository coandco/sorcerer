<?php
  require_once "include/game.inc.php";
  session_start();
  $actions = json_decode($_REQUEST['actions'], true);
  //$file = fopen("logfile.txt", a);
  //fwrite($file, $actions . "\n");
  $toreturn = ProcessActions($_REQUEST['gameid'], $_SESSION['uid'], $actions);
  //print_r($_REQUEST['gameid']);
  $output = json_encode($toreturn);
  echo $output;
?>
