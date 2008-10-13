<?php
require_once "include/config.inc.php";
require_once "include/functions.inc.php";
require_once "include/user.inc.php";
require_once "include/game.inc.php";

if (!isset($_GET['gameid'])) {
  if (!isset($_GET['create'])) {
    print("<html>\n");
    print("<head>\n");
    print("  <title>Create a Game</title>\n");
    print("</head>\n");
    print("<body>\n");
    print('<form name="creategame" action="newgame.php?create" method="post">');
    print('<input type="submit" value="Create game">');
    print("</form>\n");
    print("</body>\n");
    print("</html>\n");
    die();
  } else {
    print("<html>\n");
    print("<head>\n");
    print("  <title>Creating game...</title>\n");
    //if ($manip->valid) 
      //print('  <meta http-equiv="refresh" content="3;url=game.php?gameid=' . $manip->gameid . '" />' . "\n");
    print("</head>\n");
    print("<body>\n");
    $manip = new GameManipulator();
    $manip->CreateGame(array("coandco@gmail.com", "haxotpi@gmail.com"));
    print("The game " . ($manip->valid ? "was" : "wasn't") . " created successfully.<br />\n");
    if ($manip->valid)
      print('Click <a href="newgame.php?gameid=' . $manip->gameid . '">here</a> to visit the new game, or wait for the redirect.<br />');
    die();
  }
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
    break;
  case "active":
    header("Location: http://" . $_SERVER['SERVER_NAME'] . "/game.php?gameid=" . $manip->gameid);
    die();
  case "finished":
    header("Location: http://" . $_SERVER['SERVER_NAME'] . "/oldgame.php?gameid=" . $manip->gameid);
    die();
  default: //Something's screwy...
    header("Location: http://" . $_SERVER['SERVER_NAME'] . "/game.php?gameid=" . $manip->gameid);
    die();
}

//At this point we know that we have a valid game in the proposed stage
if (!($manip->isParticipant($_SESSION['username']))) {
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

if (!$manip->isConfirmed($_SESSION['username'])) {
  if (isset($_GET['confirm'])) {
    print("<html>\n");
    print("<head>\n");
    print("  <title>Confirming participation...</title>\n");
    $manip->LoadDeck($_SESSION['uid']);
    $confirm_success = $manip->playerConfirm($_SESSION['username']);
    if( $confirm_success) {
      //print('  <meta http-equiv="refresh" content="3;url=game.php?gameid=' . $manip->gameid . '" />' . "\n");
    }
    print("</head>\n");
    print("<body>\n");
    print("Confirmation " . ($confirm_success ? "was" : "wasn't") . " successful<br />\n");
    if ($confirm_success)
      print('Redirecting to the game... (click <a href="newgame.php?gameid=' . $manip->gameid . '">here</a> to visit it manually)<br />');
    die();
  }
  print("<html>\n");
  print("<head>\n");
  print("  <title>Please confirm your participation</title>\n");
  print("</head>\n");
  print("<body>\n");
  print('<form name="confirmgame" action="newgame.php?gameid=' . $manip->gameid . '&confirm" method="post">');
  print('<input type="submit" value="Confirm game and load deck">');
  print('</form>');
  die();
}

//At this point we know that we have a valid, proposed game, and that the logged-in user has confirmed
if (!($manip->allConfirmed())) {
  print("<html>\n");
  print("<head>\n");
  print("  <title>Waiting for other players...</title>\n");
  print("</head>\n");
  print("<body>\n");
  print("Waiting for the other players to confirm...");
  print("</body>\n");
  print("</html>\n");
  die();
} else {
  if (!isset($_GET['startgame'])) {
    print("<html>\n");
    print("<head>\n");
    print("  <title>Ready to start the game!</title>\n");
    print("</head>\n");
    print("<body>\n");
    print('Click <a href="newgame.php?gameid=' . $manip->gameid . '&startgame">here</a> to start the game.');
  }
}














?>