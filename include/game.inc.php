<?php
require_once "include/adodb5/adodb.inc.php";
require_once "include/functions.inc.php";

function ProcessActions($gameid, $uid, $actions) {
  global $CONF;
  $prefix = $CONF[GENERAL]['prefix'];
  $returnvalue = array();
  $manip = new GameManipulator($gameid);
  if (!($manip->valid)) {
    foreach ($actions as $key => $action) {
      $actions[$key]['applied'] = false;
      $actions[$key]['gameid'] = $gameid;
    }
    return $actions;
  }
  
  //At this point we know we have a valid game
  $syncage_str = 'game' . $gameid . '_syncage';
  if (!isset($_SESSION[$syncage_str])) {
    session_register($syncage_str);
    $_SESSION[$syncage_str] = 0;
  }
  $syncage = $_SESSION[$syncage_str];
  
  $carddb = dbConnect("CARDDB");
  $numactions = count($actions);
  
  for ($i = 0; $i < $numactions; $i++) {
    $returnvalue[$i]['command'] = $actions[$i]['command'];
    switch ($actions[$i]['command']) {
      case "drawCard":
        $cardrow = $manip->TopCards("library_" . $uid, 1);
        if ($cardrow) {
          $returnvalue[$i]['applied'] = $manip->MoveCard($cardrow[0]['card_id'], "hand_" . $uid);
          $returnvalue[$i]['dbrow'] = $cardrow[0];
          $returnvalue[$i]['dbrow']['location'] = "hand_" . $uid;
          $returnvalue[$i]['cardinfo'] = $carddb->GetRow("select * from `" . $prefix . "_cards` where refhash = ? limit 1", $cardrow[0]['refhash']);
          
        } else {
          $returnvalue[$i]['applied'] = false;
          $returnvalue[$i]['dbrow'] = false;
          $returnvalue[$i]['cardinfo'] = false;
        }
        break;
      case "moveCard":
        $params = $actions[$i]['params'];
        $cardrow = $manip->getCard($params['card_id']);
        if ($cardrow) {
          if ($params['to'] != 'table') //Comment these two lines out to enable card movement into non-owned zones  
            $params['to'] = ereg_replace('[_0-9]*', '', $params['to']) . "_" . $cardrow['owner'];
          $returnvalue[$i]['applied'] = $manip->MoveCard($cardrow['card_id'], $params['to'], $params['x'], $params['y'], $params['morphed'], $params['stackorder']);
        } else {
          $returnvalue[$i]['applied'] = false;
        }
        break;
      case "cardAttributes":
        $params = $actions[$i]['params'];
        $cardrow = $manip->getCard($params['card_id']);
        if ($cardrow) {
          $returnvalue[$i]['applied'] = $manip->cardAttributes($cardrow['card_id'], $params['attributes']);
        } else {
          $returnvalue[$i]['applied'] = false;
        }
        break;
      case "syncGame":
        $returnvalue[$i]['gamestate'] = $manip->buildSyncMessage($uid, $actions[$i]['params']);
        break;
      default:
        $returnvalue[$i]['applied'] = true;
        break;
    }
  }
  
  return $returnvalue;
}

class GameManipulator {
  const STACKSTART = 536870912;
  const STACKINCREMENT = 1048576; //2^20

  function __construct($gameid = NULL, $debug = false) {
    global $CONF;
    $this->dblan = dbConnect('', $debug);
    $this->carddb = dbConnect('CARDDB', $debug);
    if ($gameid != NULL) {
      $game_record = $this->dblan->Execute("select * from `" . $CONF[GENERAL]['prefix'] . "_games` where game_id = ?", array($gameid));
      if ($game_record) {
        if ($game_record->RecordCount() > 0) {
          $this->game_info = $game_record->FetchRow();
          $this->gameid = $gameid;
          $this->valid = true;
        } else { //No game with that gameid
          $this->valid = false;
        }
      } else {
        $this->valid = false;
      }
    } else {
      $this->valid = false;
    }
  }
  
  function isParticipant($email) {
    global $CONF;
    $prefix = $CONF[GENERAL]['prefix'];
    if (!$this->valid)
      return false;
    
    $player_record = $this->dblan->Execute("select * from `" . $prefix . "_game" . $this->gameid . "_players` where email = ?", array($email));
    if ($player_record) {
      if ($player_record->RecordCount() > 0) {
        return true;
      }
    }
    return false;
  }
  
  function isConfirmed($email) {
    global $CONF;
    $prefix = $CONF[GENERAL]['prefix'];
    if (!$this->valid)
      return false;
      
    if($this->dblan->GetOne("select confirmed from `" . $prefix . "_game" . $this->gameid . "_players` where email = ?", array($email)) == 1)
      return true;
    return false;
  }
  
  function allConfirmed() { //returns the player table if everyone is confirmed, false otherwise
    global $CONF;
    $prefix = $CONF[GENERAL]['prefix'];
    if (!$this->valid)
      return false;
    
    $playertable = $this->dblan->GetArray("select * from `" . $prefix . "_game" . $this->gameid . "_players`");
    
    if ($playertable == false) {
      //debug($this->dblan->ErrorMsg());
      return false;
    }
    
    foreach ($playertable as $playerentry) {
      if ($playerentry['confirmed'] == 0)
        return false;
    }
    
    return $playertable;
  }
  
  function playerConfirm($email) {
    global $CONF;
    $prefix = $CONF[GENERAL]['prefix'];
    if (!$this->valid)
      return false;
      
    //Should only be used from authenticated pages, so $email should always exist in the user database.  We'll return false if that's not the case.
    $uid = $this->dblan->GetOne("select uid from `" . $prefix . "_users` where username = ?", array($email));
    
    if ($uid == null)
      return false;
    
    $tablesarray = $dblan->MetaTables('TABLES');
      
    if (!in_array($prefix . '_game' . $gameid . '_player' . $uid . '_update', $tablesarray)) {
      $sql = table_def('playerupdate', $gameid, $uid);
      $this->dblan->Execute($sql);
    }
    
    if (!in_array($prefix . '_game' . $gameid . '_player' . $uid . '_messages', $tablesarray)) {
      $sql = table_def('playermessages', $gameid, $uid);
      $this->dblan->Execute($sql);
    }

    if (!$this->dblan->Execute("update `" . $prefix . "_game" . $this->gameid . "_players` set uid = ?, confirmed = ? where email = ?", array($uid, 1, $email)))
      return false;
    
    return true;
  }
  
  //Returns true if the game was started, false if not (due to something like a nonconfirmed player)
  function StartGame($turnorder = '', $currentturn = '', $currentphase = 'untap') { 
    global $CONF;
    $prefix = $CONF[GENERAL]['prefix'];
    
    if (!$this->valid)
      return false;
      
    $playertable = $this->allConfirmed();
    if ($playertable == false)
      return false;
      
    if ($currentturn != '')
      $currentturn = $playertable[0]['uid'];
      
    if ($turnorder != '')
      foreach ($playertable as $playerentry)
        $turnorder .= $playerentry['uid'] . " ";
      
    $this->dblan->Execute('update `' . $prefix . '_games` set game_stage = ?, turn_order = ?, current_turn = ?, current_phase = ? where game_id = ?', array('active', $turnorder, $currentturn, $currentphase, $this->gameid));    return true;
  }
  
  function CreateGame($participants) { //$participants should be an array containing the email addresses of the participants
    global $CONF;
    $prefix = $CONF[GENERAL]['prefix'];
    $dbparticipants = '';
    
    if (count($participants) < 2)
      return false;
    
    $this->dblan->StartTrans();
    
    if (!($gamecount = $this->dblan->GetOne("select count(*) from " . $prefix . "_games")))
      $gamecount = 0;
    $gameid = $gamecount + 1;

    $this->dblan->Execute("insert into `" . $prefix . "_games` (game_id, creation_time) values (?, ?)", array($gameid, time()));
    
    $sql = table_def('players', $gameid);
    $this->dblan->Execute($sql);
    
    $sql = table_def('cards', $gameid);
    $this->dblan->Execute($sql);
    
    $sql = table_def('gamerecord', $gameid);
    $this->dblan->Execute($sql);
    
    foreach ($participants as $email) {
      $dbparticipants .= '{' . $email . '} ';
      $this->dblan->Execute("insert into `" . $prefix . "_game" . $gameid . "_players` (email) values (?)", array($email));
    }
    
    $this->dblan->Execute("update `" . $prefix . "_games` set participants = ? where game_id = ?", array(trim($dbparticipants), $gameid));
    $this->dblan->CompleteTrans();
    
    //Did it successfully commit?
    $game_record = $this->dblan->Execute("select * from " . $prefix . "_games where game_id = ?", array($gameid));
    
    //If the game_id record we tried to create exists, all is well
    if ($game_record) {
      if ($game_record->RecordCount() > 0) {
        $this->game_info = $game_record->FetchRow();
        $this->gameid = $gameid;
        $this->valid = true;
      } else { //No game with that gameid
        $this->valid = false;
      }
    } else {
      $this->valid = false;
    }
  }
  
  function LoadDeck ($playerid, $deckstring = '') { //For now, this is a placeholder for the real LoadDeck function
    global $CONF;
    $prefix = $CONF[GENERAL]['prefix'];
    
    if (!$this->valid) //Do nothing if we're not connected to a valid game
      return false;
      
    //GetOne returns false if no records were found, so this just checks for the existence of cards in the player's library
    if ($this->dblan->GetOne("select count(*) from `" . $prefix . "_game" . $this->gameid . "_cards` where location = ?", "library_$playerid"))
      return false;
    
    $sampledeck = array(
      array("VHJvbGwgQXNjZXRpYy4xMzA0OTg=", $playerid, $playerid, "library_$playerid", 536870912), //Troll Ascetic
      array("V29yc2hpcC4yNTU1Mw==", $playerid, $playerid, "library_$playerid", 536870912 + pow(2,20)), //Worship
      array("QmVsb3ZlZCBDaGFwbGFpbi4yOTc5OA==", $playerid, $playerid, "library_$playerid", 536870912 + 2*(pow(2,20))), //Beloved Chaplain
      array("U3R1ZmZ5IERvbGwuMTE2NzI0", $playerid, $playerid, "library_$playerid", 536870912 + 3*(pow(2,20))), //Stuffy Doll
      array("RGF5YnJlYWsgQ29yb25ldC4xMzA2MzU=", $playerid, $playerid, "library_$playerid", 536870912 + 4*(pow(2,20))), //Daybreak Coronet
      array("VHJvbGwgQXNjZXRpYy4xMzA0OTg=", $playerid, $playerid, "library_$playerid", 536870912 + 5*(pow(2,20))), //Troll Ascetic
      array("VHJvbGwgQXNjZXRpYy4xMzA0OTg=", $playerid, $playerid, "library_$playerid", 536870912 + 6*(pow(2,20))), //Troll Ascetic
      array("VHJvbGwgQXNjZXRpYy4xMzA0OTg=", $playerid, $playerid, "library_$playerid", 536870912 + 7*(pow(2,20))), //Troll Ascetic
      array("VHJvbGwgQXNjZXRpYy4xMzA0OTg=", $playerid, $playerid, "library_$playerid", 536870912 + 8*(pow(2,20))), //Troll Ascetic
      array("VHJvbGwgQXNjZXRpYy4xMzA0OTg=", $playerid, $playerid, "library_$playerid", 536870912 + 9*(pow(2,20))), //Troll Ascetic
    );
    
    $this->dblan->Execute("insert into `" . $prefix . "_game" . $this->gameid . "_cards` (refhash, owner, controller, location, stackorder) values (?, ?, ?, ?, ?)", $sampledeck);
    return true;
  }
  
  //Returns an array containing the card information for the $numcards top cards of a zone, or false if there are none
  function TopCards($zone, $numcards) {
    global $CONF;
    $prefix = $CONF[GENERAL]['prefix'];
    
    if (!$this->valid) //Do nothing if we're not connected to a valid game
      return false;
    
    if ($zone == "table")
      return array(-1);
    
    $results = $this->dblan->Execute("select card_id, refhash, location, stackorder from `" . $prefix . "_game" . $this->gameid . "_cards` where location = ? order by stackorder limit ?", array($zone, $numcards));
    
    if (!($results))
      return false;
      
    return $results->GetArray();
  }
  
  //Returns the database row of a given card
  function getCard($card_id, $ontable = true) {
    global $CONF;
    $prefix = $CONF[GENERAL]['prefix'];
    
    if ($ontable) {
      $retval = $this->dblan->GetRow("select card_id, refhash, owner, controller, istapped, isattacking, doesntuntap, isphased, ismorphed, counters, location, x, y from `" . $prefix . "_game" . $this->gameid . "_cards` where (card_id = ?)", $card_id);
    } else {
      $retval = $this->dblan->GetRow("select card_id, refhash, location, stackorder from `" . $prefix . "_game" . $this->gameid . "_cards` where (card_id = ?)", $card_id);
    }
    
    return $retval ? $retval : $this->dblan->ErrorMsg();
  }
  
  //Builds a game-state message from the perspective of user $uid, using refhash array $knowncards to determine what's new
  //This message consists of the following parts:
  //1. 'uid': an integer specifying the uid of the requesting player
  //2. 'playerlist': an array containing each player ID, life total, and nickname
  //3. 'cardlist': an array containing status info and location for each card
  //4. 'cardinfo': an array containing card data not found in $knowncards
  //5. 'current_phase': 'untap', 'upkeep', 'draw', 'main', 'attack', or 'discard'
  //6. 'current_turn': integer representing the current turn
  function buildSyncMessage($uid, $knowncards) {
    global $CONF;
    $prefix = $CONF[GENERAL]['prefix'];
    $gamecardstable = $prefix . "_game" . $this->gameid . "_cards";
    $gameplayerstable = $prefix . "_game" . $this->gameid . "_players";
    $userstable = $prefix . "_users";
    
    if (!$this->valid) //Do nothing if we're not connected to a valid game
      return false;

    $message = array();  //Initialize the return value to a blank array
    
    $message['uid'] = $uid;
    
    $sql = "select $gameplayerstable.uid, $userstable.nickname, $gameplayerstable.lifetotal from `$gameplayerstable`, `$userstable` where $gameplayerstable.uid = $userstable.uid";
    $message['playerlist'] = $this->dblan->GetAll($sql);
    
    foreach ($knowncards as $knowncard) { //Initialize the seen-cards array
      $seencards[$knowncard] = true;
    }
    
    $sql = "select card_id, refhash, location, stackorder from `" . $prefix . "_game" . $this->gameid . "_cards` where (location = ?) or (location like ?) or (location like ?)";
    $offtable = $this->dblan->GetAll($sql, array("hand_" . $uid, "grave%", "rfg%"));
    $message['cardlist'] = $offtable ? $offtable : array();
    
    $sql = "select card_id, refhash, owner, controller, istapped, isattacking, doesntuntap, isphased, ismorphed, counters, location, x, y from `" . $prefix . "_game" . $this->gameid . "_cards` where (location = ?)";
    $ontable = $this->dblan->GetAll($sql, "table");
    $message['cardlist'] = array_merge($message['cardlist'], $ontable ? $ontable : array());
    
    foreach ($message['cardlist'] as $card_id => $card_status) { //Build list of cards the client doesn't know about
      if (!isset($seencards[$card_status['refhash']])) {
        $seencards[$card_status['refhash']] = true;
        $newhashes[$card_status['refhash']] = $card_status['refhash'];
      }
    }
    
    if (count($newhashes) > 0) {
      $sql = "select * from `" . $prefix . "_cards` where (refhash = '" . implode("') or (refhash = '", $newhashes) . "')";
      $message['cardinfo'] = $this->carddb->GetAll($sql);
    } else {
      $message['cardinfo'] = array();
    }
    
    $sql = "select current_phase from `" . $prefix . "_games` where game_id = ?";
    $message['current_phase'] = $this->dblan->GetOne($sql, $this->gameid);
    
    $sql = "select current_turn from `" . $prefix . "_games` where game_id = ?";
    $message['current_turn'] = $this->dblan->GetOne($sql, $this->gameid);
    
    return $message;
  }
  
  function cardAttributes ($cardid, $attributes) {
    global $CONF;
    $prefix = $CONF[GENERAL]['prefix'];
    $validattributes = array('istapped', 'isattacking', 'doesntuntap', 'isphased', 'ismorphed', 'counters');
    
    if (!$this->valid) //Do nothing if we're not connected to a valid game
      return false;
      
    $cardrecord = $this->dblan->Execute("select * from `" . $prefix . "_game" . $this->gameid . "_cards` where card_id = ? limit 1", array($cardid));
    $card = $cardrecord->FetchRow();
    
    if (!isset($card['card_id']))
      return false;
      
    foreach ($attributes as $attribute => $value) {
      if (in_array($attribute, $validattributes)) //We only want to set valid attributes
        $card[$attribute] = $value;
    }
    
    $sql = array($this->dblan->GetUpdateSQL($cardrecord, $card), false);
    $success = ($this->dblan->Execute($sql) != false) ? true : false; //The ? : construct is needed because it only returns a boolean if it fails.
    if ($success) {
      //Notify players about the change
      //Commit the change to the game record
    }
    return $success;
  }
  
  function sqlTableMove ($card, $x, $y, $morphed) {
    global $CONF;
    $prefix = $CONF[GENERAL]['prefix'];
    $table = $prefix . "_game" . $this->gameid . "_cards";
    $card['x'] = $x;
    $card['y'] = $y;
    $card['ismorphed'] = ($morphed ? 1 : 0);
    $card['location'] = 'table';
    return array($this->dblan->GetInsertSQL($table, $card), false); //There aren't any bind parameters here, so the second argument has to be false
  }
  
  function sqlGenericMove ($card, $destination, $stackorder = false) {
    global $CONF;
    $prefix = $CONF[GENERAL]['prefix'];
    $table = $prefix . "_game" . $this->gameid . "_cards";
    $card['istapped'] = $card['isattacking'] = $card['doesnotuntap'] = $card['isphased'] = $card['ismorphed'] = $card['counters'] = 0;
    $card['controller'] = $card['owner'];
    $card['location'] = $destination;
    $card['stackorder'] = ($stackorder ? $stackorder : $card['stackorder']); //Leave the stackorder as is if we weren't passed it as an argument
    return array($this->dblan->GetInsertSQL($table, $card), false); //There aren't any bind parameters here, so the second argument has to be false
  }
  
  function moveCalculateStackOrder($stackplace, $location, &$SQL_executed) {
    global $CONF;
    $prefix = $CONF[GENERAL]['prefix'];
    
    if ($stackplace == "TOP") {
      $tempstack = $this->dblan->GetOne("select stackorder from `" . $prefix . "_game" . $this->gameid . "_cards` where location = ? order by stackorder limit 1", array($location));
      if (!$tempstack) //GetOne returns false if no rows were found
        $stackorder = GameManipulator::STACKSTART;
      else
        $stackorder = $tempstack - GameManipulator::STACKINCREMENT;
    } else if ($stackplace == "BOTTOM") {
      $tempstack = $this->dblan->GetOne("select stackorder from `" . $prefix . "_game" . $this->gameid . "_cards` where location = ? order by stackorder desc limit 1", array($location));
      if (!$tempstack) //GetOne returns false if no rows were found
        $stackorder = GameManipulator::STACKSTART;
      else
        $stackorder = $tempstack + GameManipulator::STACKINCREMENT;
    } else if (is_array($stackplace)) {
      $before = $this->dblan->GetOne("select stackorder from `" . $prefix . "_game" . $this->gameid . "_cards` where card_id = ? and location = ?", array($stackplace[0], $location));
      $after = $this->dblan->GetOne("select stackorder from `" . $prefix . "_game" . $this->gameid . "_cards` where card_id = ? and location = ?", array($stackplace[1], $location));
      if ($before && $after) { //Both cards have to be valid cards in the correct location
        if ((abs($after - $before) % 2) == 0) { //We still have room to put another card in
          $stackorder = abs($after - $before) / 2;
        } else {//Bad news.  We've run out of room in the stacking system, and have to do a reorder.
          $this->reorderStack($location, $SQL_executed);
          $stackorder = $this->moveCalculateStackOrder($stackplace, $location, $SQL_executed);
        }
      }
    } else { //bad stackorder
      $stackorder = false;
    }
    return $stackorder;
  }
  
  //
  // reorderStack gets a list of all of the cards in a particular location and assigns new stackorder numbers to them while keeping them in order.
  // It records all of the SQL it executes in the array SQL_executed.
  //
  function reorderStack($location, &$SQL_executed) {
    global $CONF;
    $prefix = $CONF[GENERAL]['prefix'];
    
    $this->dblan->SetFetchMode(ADODB_FETCH_NUM);
    $cardlist = $this->dblan->GetAll("select (stackorder, card_id) from `" . $prefix . "_game" . $this->gameid . "_cards` where location = ? order by stackorder", array($location));
    $this->dblan->SetFetchMode(ADODB_FETCH_ASSOC);
    $listlength = count($cardlist);
    
    $currentorder = GameManipulator::STACKSTART;
    for ($i = 0; $i < $listlength; $i++) { // Iterate through the cards at $location
      $cardlist[$i][0] = $currentorder;
      $currentorder += GameManipulator::STACKINCREMENT;
    }
    
    $sql = "update `" . $prefix . "_game" . $this->gameid . "_cards` set stackorder=? where card_id=?";
    $this->dblan->Execute($sql, $cardlist);
    
    array_push($SQL_executed, array($sql, $cardlist));
  }
  
  //
  // MoveCard (predictably) moves a card from one location to another.
  // As arguments, it takes a numerical card id, a destination string, and a stack order.
  // The stack order should be one of the following:
  //   "TOP" to put it on the top of the stack
  //   "BOTTOM" to put it on the bottom of the stack
  //   An array containing two card IDs to place it at a specific place in the middle of the stack
  //
  function MoveCard ($cardid, $destination, $x = 0, $y = 0, $morphed = false, $stackorder = "TOP") { 
    global $CONF;
    $prefix = $CONF[GENERAL]['prefix'];
    $SQL_executed = array();
    $SQL_toexecute = array();
    $SQL_delete = array();
    $tempstack = '';
    
    if (!$this->valid) //Do nothing if we're not connected to a valid game
      return false;
      
    $card = $this->dblan->GetRow("select * from `" . $prefix . "_game" . $this->gameid . "_cards` where card_id = ?", array($cardid));
    
    if (!isset($card['card_id']))
      return false;
    
    $success = true; //Assume success unless we set this to false
    $this->dblan->Execute("delete from `" . $prefix . "_game" . $this->gameid . "_cards` where card_id = ?", array($cardid));
    $SQL_executed[0] = array("delete from `" . $prefix . "_game" . $this->gameid . "_cards` where card_id = ?", array($cardid));
    
    if ($destination == 'table') { //The table needs extra parameters and ignores stackorder
      $SQL_toexecute = $this->sqlTableMove($card, $x, $y, $morphed);
    } else if (strpos("hand", $destination)){ //The hand also ignores stackorder
      $SQL_toexecute = $this->sqlGenericMove($card, $destination);
    } else { //Everywhere else keeps stackorder
      $newstackorder = $this->moveCalculateStackOrder($stackorder, $destination, $SQL_executed);
      if ($newstackorder == false) { //Something was invalid in the arguments, so quietly re-insert the card as is
        $SQL_toexecute = $this->sqlGenericMove($card, $card['location'], $card['stackorder']);
        $success = false;
      } else {
        $SQL_toexecute = $this->sqlGenericMove($card, $destination, $newstackorder);
      }
    }
    
    $this->dblan->Execute($SQL_toexecute[0]);
    array_push($SQL_executed, $SQL_toexecute);
    
    //Notify players that there has been a move
    //Commit $SQL_executed to the game record
    
    return $success;
  }
}














