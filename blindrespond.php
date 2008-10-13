<?php
  require_once "include/game.inc.php";
  $actions = json_decode($_REQUEST['actions'], true);
  //$file = fopen("logfile.txt", a);
  //fwrite($file, $actions . "\n");
  foreach ($actions as $key => $action) {
    $actions[$key]['applied'] = true;
  }
  //print_r($actions);
  $output = json_encode($actions);
  echo $output;
?>
