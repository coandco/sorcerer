<?php
require_once "include/adodb5/adodb.inc.php";
require_once "include/config.inc.php";
require_once "include/functions.inc.php";

//
// CHECK FOR EXISTING COOKIE LOGIN OR CREATE SESSION FOR LOGIN
//
if($_COOKIE[$CONF[GENERAL]['prefix']]){
  validate_user($_COOKIE[$CONF[GENERAL]['prefix'] . '_name'],$_COOKIE[$CONF[GENERAL]['prefix'] . '_pass'], true);
}else if ($_SESSION['loggedin']==FALSE){
  session_register('loggedin');
  session_register('username');
  session_register('level');
  session_register('uid');
}

$gamestring = '';
$question = '';
if (isset($_GET['gameid'])) {
  $question = '?';
  $gamestring = 'gameid=' . $_GET['gameid'];
}

if(isset($_REQUEST['login'])) {
  if((isset($_REQUEST['username'])) && (isset($_REQUEST['password']))) {
    validate_user($_REQUEST['username'], md5($_REQUEST['password']), false);
  }
  header("Location: http://" . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'] . $question . $gamestring);
}

if(isset($_REQUEST['logout'])) {
  $_SESSION = array();
  setcookie('PHPSESSID','',time() - 1337);
  setcookie($CONF[GENERAL]['prefix'], false, time() - 1337);
  setcookie($CONF[GENERAL]['prefix'] . "_pass", '', time() - 1337);
  setcookie($CONF[GENERAL]['prefix'] . "_user", '', time() - 1337);
  header("Location: http://" . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'] . $question . $gamestring);
  //echo '<head>' . "\n";
  //echo '<meta http-equiv="refresh" content="1;url=logout.php">' . "\n";
  //echo '</head> Logging Out!!!!';
}

$gamestring = '&' . $gamestring;

$dblan = dbConnect();
if((!(isset($_SESSION['loggedin']))) && (in_array($CONF[GENERAL]['prefix'] . '_users', $dblan->MetaTables('TABLES')))) {
  echo '<html> <body> You need to log in first<br/><form enctype="multipart/form-data" action="?login' . $gamestring . '" method="POST">' . "\n";
  echo 'Username:<input type="text" name="username"/>' . "\n";
  echo '<br/>Password:<input type="password" name="password">' . "\n";
  echo '<br/><input type="submit" value="Login"></form>' . "\n";
/*  echo '<pre>';
  echo $sql;
  echo "mysql_errno() = ".mysql_errno()."\n";
  echo "mysql_error() = ".mysql_error()."\n";
  print_r($_REQUEST);
  echo '</pre>';*/
  echo '</body> </html>';
	die;
}

?>