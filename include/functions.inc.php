<?php
require_once "include/config.inc.php";
require_once "include/adodb5/adodb.inc.php";

//
// TRANSLATES false TO ZERO, RETURNS ALL OTHER INPUT VALUES UNTOUCHED
//
function dbcount_sanitize($input) {
  if ($input == false)
    return 0;
  else
    return $input;
}

//
// ESTABLISHES DB CONNECTION GIVEN CRITERIA FOUND IN CONFIG FILE
// RETURNS CONNECTION PTR
//
function dbConnect($whichdb = '', $debug = false){
  global $CONF;
  if ($whichdb == '')
    $whichdb = 'DATABASE';
  $dbtype = $CONF[$whichdb]['dbtype'];
  $dbhost = $CONF[$whichdb]['dbhost'];
  $dbuser = $CONF[$whichdb]['dbuser'];
  $dbpass = $CONF[$whichdb]['dbpass'];
  $site_db = $CONF[$whichdb]['site_db'];
  $prefix = $CONF[GENERAL]['prefix'];
  $connect = &ADONewConnection($dbtype);
  $connect->autoRollback = true;
  $connect->debug = $debug;
  $connect->SetFetchMode(ADODB_FETCH_ASSOC);
  if ($dbtype == "sqlite") {
  	$connect->PConnect($site_db);
  } else if ($dbtype == "mysqli") {
    if(!$connect->PConnect($dbhost, $dbuser, $dbpass, $site_db)) {
      die($connect->ErrorMsg());
    }
    return $connect;
  } else {
    die ("Bad database type: '$dbtype'");
  }
}

//
// VERIFY USERNAME/PASSWORD
//
function validate_user($username, $password, $set_cookie){
  global $CONF;
  $prefix = $CONF[GENERAL]['prefix'];
	//pulls user info from 'users' db
  $dblan = dbConnect();
  $result = $dblan->Execute("SELECT * FROM " . $prefix . "_users WHERE username = ?", $username);
  $db_info = $result->FetchRow();

	//compares md5 password of user login request to db md5 hash...
  if ($password == $db_info['password']){
    $_SESSION['loggedin'] = "TRUE";
    $_SESSION['username'] = $db_info['username'];
    $_SESSION['level'] = $db_info['level'];
	$_SESSION['uid'] = $db_info['uid'];
	if ($set_cookie) {
      setcookie("sorcerer",true,time()+2419200);
      setcookie("sorcerer_user",$username,time()+2419200); //one month expiration...
      setcookie("sorcerer_pass",$password,time()+2419200);
	}
    return true;
  }else{
    $_SESSION = array();
    //print("Bad password");
    return false;
  }
}

//
// RETURN SQL TABLE DEFINITIONS APPROPRIATE FOR CURRENT CONFIGURATION
//
function table_def($whichtable, $gameid = '', $playerid = '') {
  global $CONF;
  $prefix = $CONF[GENERAL]['prefix'];
  $dbtype = $CONF[DATABASE]['dbtype'];
  $sql = '';
  switch ($dbtype) {
    case "mysqli":
      switch ($whichtable) {
        case 'users':
          $sql = "CREATE TABLE `" . $prefix . "_users` (";
          $sql .= " `uid` int(11) NOT NULL PRIMARY KEY auto_increment,";
          $sql .= " `username` varchar(255) NOT NULL UNIQUE KEY default '',";
          $sql .= " `password` char(32) NOT NULL default '',";
          $sql .= " `level` enum('G','U','M','A') NOT NULL default 'U',";
          $sql .= " `nickname` varchar(255) NOT NULL UNIQUE KEY,";
          $sql .= " `lastlogin` varchar(30) NOT NULL,";
          $sql .= " `created` varchar(30) NOT NULL";
          $sql .= ");";
          return $sql;
        case 'games':
          $sql  = "CREATE TABLE `" . $prefix . "_games` (";
          $sql .= " `game_id` int(11) NOT NULL PRIMARY KEY,"; //auto_increment was removed because Sorcerer needs have immediate knowledge of the new gameid
          $sql .= " `game_stage` enum('proposed', 'active', 'completed') NOT NULL default 'proposed',";
          $sql .= " `participants` varchar(1023) NOT NULL default '',";
          $sql .= " `creation_time` varchar(30) NOT NULL default '',";
          $sql .= " `turn_order` varchar(255) default '',";
          $sql .= " `current_turn` int(11),";
          $sql .= " `current_phase` enum('untap','upkeep','draw','main','attack','discard'),";
          $sql .= " `winner` int(11)";
          $sql .= ");";
          return $sql;
        case 'players':
          $sql  = "CREATE TABLE `" . $prefix . "_game" . $gameid . "_players` (";
          $sql .= " `email` varchar(255) NOT NULL PRIMARY KEY,";
          $sql .= " `uid` int(11),";
          $sql .= " `confirmed` tinyint(1) NOT NULL default 0,";
          $sql .= " `lifetotal` smallint(4) NOT NULL default 20";
          $sql .= ");";
          return $sql;
        case 'gamerecord':
          $sql  = "CREATE TABLE `" . $prefix . "_game" . $gameid . "_record` (";
          $sql .= " `index` int(11) NOT NULL PRIMARY KEY auto_increment,";
          $sql .= " `sql` varchar(65535) NOT NULL,";
          $sql .= " `arguments` varchar(65535) NOT NULL,";
          $sql .= " `timestamp` varchar(30) NOT NULL";
          $sql .= ");";
        case 'playerupdate':
          $sql  = "CREATE TABLE `" . $prefix . "_game" . $gameid . "_player" . $playerid . "_update` (";
          $sql .= " `syncage` int(11) NOT NULL PRIMARY KEY,";
          $sql .= " `event` varchar(65535) NOT NULL";
          $sql .= ");";
        case 'playermessages':
          $sql  = "CREATE TABLE `" . $prefix . "_game" . $gameid . "_player" . $playerid . "_messages` (";
          $sql .= " `index` int(11) NOT NULL PRIMARY KEY auto_increment,";
          $sql .= " `message` varchar(65535) NOT NULL,";
          $sql .= " `timestamp` varchar(30) NOT NULL";
          $sql .= ");";
        case 'cards':
          $sql  = "CREATE TABLE `" . $prefix . "_game" . $gameid . "_cards` (";
          $sql .= " `card_id` int(11) NOT NULL PRIMARY KEY auto_increment,";
          $sql .= " `refhash` varchar(255) NOT NULL default '',";
          $sql .= " `owner` int(11) NOT NULL,";
          $sql .= " `controller` int(11) NOT NULL,";
          $sql .= " `istapped` tinyint(1) NOT NULL default 0,";
          $sql .= " `isattacking` tinyint(1) NOT NULL default 0,";
          $sql .= " `doesntuntap` tinyint(1) NOT NULL default 0,";
          $sql .= " `isphased` tinyint(1) NOT NULL default 0,";
          $sql .= " `ismorphed` tinyint(1) NOT NULL default 0,";
          $sql .= " `counters` smallint(4) NOT NULL default 0,";
          $sql .= " `location` varchar(30) NOT NULL default 'table',";
          $sql .= " `stackorder` int(20) NOT NULL default 536870912,";
          $sql .= " `x` int(11) NOT NULL default 0,";
          $sql .= " `y` int(11) NOT NULL default 0";
          $sql .= ");";
          return $sql;
        default:
          return $sql; //If it's not a table we recognize, return a blank string
      }
    case "sqlite":
      switch ($whichtable) {
        case 'users':
          $sql = "CREATE TABLE `" . $prefix . "_users` (";
          $sql .= " uid INTEGER PRIMARY KEY AUTOINCREMENT,";
          $sql .= " username TEXT NOT NULL UNIQUE KEY default '',";
          $sql .= " password TEXT NOT NULL default '',";
          $sql .= " level TEXT NOT NULL default 'U',";
          $sql .= " nickname TEXT NOT NULL UNIQUE KEY,";
          $sql .= " lastlogin TEXT NOT NULL,";
          $sql .= " created TEXT NOT NULL";
          $sql .= ");";
          return $sql;
        case 'games':
          $sql  = "CREATE TABLE `" . $prefix . "_games` (";
          $sql .= " game_id INTEGER PRIMARY KEY,"; //AUTOINCREMENT was removed because Sorcerer needs have immediate knowledge of the new gameid
          $sql .= " game_stage TEXT NOT NULL default 'proposed',";
          $sql .= " participants TEXT NOT NULL default '',";
          $sql .= " creation_time TEXT NOT NULL default '',";
          $sql .= " turn_order TEXT,";
          $sql .= " current_turn INTEGER,";
          $sql .= " currentphase TEXT,";
          $sql .= " winner INTEGER";
          $sql .= ");";
          return $sql;
        case 'players':
          $sql  = "CREATE TABLE `" . $prefix . "_game" . $gameid . "_players` (";
          $sql .= " email TEXT PRIMARY KEY,";
          $sql .= " uid INTEGER,";
          $sql .= " confirmed INTEGER NOT NULL default 0,";
          $sql .= " lifetotal INTEGER NOT NULL default 20";
          $sql .= ");";
          return $sql;
        case 'gamerecord':
          $sql  = "CREATE TABLE `" . $prefix . "_game" . $gameid . "_record` (";
          $sql .= " `index` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,";
          $sql .= " `sql` TEXT NOT NULL,";
          $sql .= " `arguments` TEXT NOT NULL,";
          $sql .= " `timestamp` TEXT NOT NULL";
          $sql .= ");";
        case 'playerupdate':
          $sql  = "CREATE TABLE `" . $prefix . "_game" . $gameid . "_player" . $playerid . "_update` (";
          $sql .= " `syncage` INTEGER NOT NULL PRIMARY KEY,";
          $sql .= " `event` TEXT NOT NULL";
          $sql .= ");";
        case 'playermessages':
          $sql  = "CREATE TABLE `" . $prefix . "_game" . $gameid . "_player" . $playerid . "_messages` (";
          $sql .= " `index` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,";
          $sql .= " `message` TEXT NOT NULL,";
          $sql .= " `timestamp` TEXT NOT NULL";
          $sql .= ");";
        case 'cards':
          $sql  = "CREATE TABLE `" . $prefix . "_game" . $gameid . "_cards` (";
          $sql .= " `card_id` INTEGER PRIMARY KEY AUTOINCREMENT,";
          $sql .= " `refhash` TEXT NOT NULL default '',";
          $sql .= " `owner` INTEGER NOT NULL,";
          $sql .= " `controller` INTEGER NOT NULL,";
          $sql .= " `istapped` INTEGER NOT NULL default 0,";
          $sql .= " `isattacking` INTEGER NOT NULL default 0,";
          $sql .= " `doesntuntap` INTEGER NOT NULL default 0,";
          $sql .= " `isphased` INTEGER NOT NULL default 0,";
          $sql .= " `ismorphed` INTEGER NOT NULL default 0,";
          $sql .= " `counters` INTEGER NOT NULL default 0,";
          $sql .= " `location` TEXT NOT NULL default 'table',";
          $sql .= " `stackorder` INTEGER NOT NULL default 536870912,";
          $sql .= " `x` INTEGER NOT NULL default 0,";
          $sql .= " `y` INTEGER NOT NULL default 0";
          $sql .= ");";
          return $sql;
        default:
          return $sql; //If it's not a table we recognize, return a blank string
      }
    default:
      return $sql; //If it's not a table we recognize, return a blank string
  }
}














?>