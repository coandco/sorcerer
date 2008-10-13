<?php
require_once "include/config.inc.php";
require_once "include/functions.inc.php";
require_once "include/user.inc.php";
require_once "include/game.inc.php";

if (isset($_GET['gameid'])) {
  $manip = new GameManipulator($_GET['gameid']);
  if (!$manip->valid) {
    header("Location: http://" . $_SERVER['SERVER_NAME'] . "/game.php?gameid=" . $_GET['gameid']);
    die(); 
  }
  
  switch($manip->game_info['game_stage']) {
    case "proposed":
      header("Location: http://" . $_SERVER['SERVER_NAME'] . "/newgame.php?gameid=" . $manip->gameid);
      die();
    case "active":
      header("Location: http://" . $_SERVER['SERVER_NAME'] . "/game.php?gameid=" . $manip->gameid);
      die();
    case "finished":
      header("Location: http://" . $_SERVER['SERVER_NAME'] . "/oldgame.php?gameid=" . $manip->gameid);
      die();
    default:
      header("Location: http://" . $_SERVER['SERVER_NAME'] . "/game.php?gameid=" . $manip->gameid);
      die();
  }
}

print("<html>\n");
print("<head>\n");
print("  <title>Your Games</title>\n");
print("</head>\n");
print("<body>\n");
print('<form name="creategame" action="newgame.php?create" method="post">');
print('<input type="submit" value="Create game">');
print('</form>');
print("Eventually, accessing games.php will show you your existing games.  For now, it just lets you create a new one.");
?>