<?php
require_once "include/config.inc.php";
require_once "include/functions.inc.php";
require_once "include/user.inc.php";
require_once "include/game.inc.php";

if (!isset($_GET['gameid'])) {
  header("Location: http://" . $_SERVER['SERVER_NAME'] . "/games.php");
  die();
}
  
$thisgame = new GameManipulator($_GET['gameid']);
if (!$thisgame->valid)
  die("Invalid gameid");
  
//Do the standard redirect mojo

switch($thisgame->game_info['game_stage']) {
  case "proposed":
    if (!($thisgame->startGame())) {
      header("Location: http://" . $_SERVER['SERVER_NAME'] . "/newgame.php?gameid=" . $thisgame->gameid);
      die();
    }
    break;
  case "active":
    break;
  case "finished":
    header("Location: http://" . $_SERVER['SERVER_NAME'] . "/oldgame.php?gameid=" . $thisgame->gameid);
    die();
  default: //Something's screwy...
    print("Game stage isn't recognized.");
    die();
}
  
if (!$thisgame->isParticipant($_SESSION['username'])) {
  print("<html>\n");
  print("<head>\n");
  print("  <title>You shouldn't be here!</title>\n");
  print("</head>\n");
  print("<body>\n");
  print($_SESSION['username'] . " is not a participant in game " . $thisgame->gameid . "!");
  print("</body>\n");
  print("</html>\n");
  die();
}

//At this point we know we have a valid game in the active stage that has the user as a participant

include("include/client.html");
  
?>