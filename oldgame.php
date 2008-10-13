<?php
require_once "include/config.inc.php";
require_once "include/functions.inc.php";
require_once "include/user.inc.php";
require_once "include/game.inc.php";

if (!isset($_GET['gameid'])) {
  header("Location: http://" . $_SERVER['SERVER_NAME'] . "/games.php");
  die();
}

//At this point we know that gameid is set
$manip = new GameManipulator($_GET['gameid']);
if (!$manip->valid) {
  header("Location: http://" . $_SERVER['SERVER_NAME'] . "/game.php?gameid=" . $_GET['gameid']);
  die(); 
}

//Do the standard redirect mojo
switch($manip->game_info['game_stage']) {
  case "proposed":
    header("Location: http://" . $_SERVER['SERVER_NAME'] . "/newgame.php?gameid=" . $manip->gameid);
    die();
  case "active":
    header("Location: http://" . $_SERVER['SERVER_NAME'] . "/game.php?gameid=" . $manip->gameid);
    die();
  case "finished":
    break;
  default: //Something's screwy...
    header("Location: http://" . $_SERVER['SERVER_NAME'] . "/game.php?gameid=" . $manip->gameid);
    die();
}

//At this point we know that we have a valid game in the finished stage
if (!$manip->isParticipant($_SESSION['username'])) {
  print("<html>\n");
  print("<head>\n");
  print("  <title>You shouldn't be here!</title>\n");
  print("</head>\n");
  print("<body>\n");
  print($_SESSION['username'] . " is not a participant in game " . $manip->gameid . "!");
  print("</body>\n");
  print("</html>\n");
  die();
}

//At this point we know that we have a valid, finished game, and the logged-in user was a participant
print("<html>\n");
print("<head>\n");
print("  <title>Finished Game</title>\n");
print("</head>\n");
print("<body>\n");
print("This is a placeholder for the eventual finished-game display.");





?>