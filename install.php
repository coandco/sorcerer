<?php
require_once "include/adodb5/adodb.inc.php";
require_once "include/config.inc.php";
require_once "include/functions.inc.php";
require_once "include/game.inc.php";

ob_start();

echo "<b>INI configuration</b><br />\n";
echo "<b>General</b><br />\n";
echo "Hostname: " . $CONF[GENERAL]['hostname'] . "<br />\n";
echo "Installation prefix: " . $CONF[GENERAL]['prefix'] . "<br />\n";
echo "<br />\n";
echo "<b>Database</b><br />\n";
echo "Database type: " . $CONF[DATABASE]['dbtype'] . "<br />\n";
echo "Database username: " . $CONF[DATABASE]['dbuser'] . "<br />\n";
echo "Database password: Not displayed<br />\n";
echo "Database hostname: " . $CONF[DATABASE]['dbhost'] . "<br />\n";
echo "Database name/filename: " . $CONF[DATABASE]['site_db'] . "<br />\n";
echo "<b>Email</b><br />\n";
echo "SMTP username: " . $CONF[EMAIL]['mailuser'] . "<br />\n";
echo "SMTP password: Not displayed<br />\n";
echo "SMTP hostname: " . $CONF[EMAIL]['mailhost'] . "<br />\n";
echo "SMTP 'from' address: " . $CONF[EMAIL]['mailfrom'] . "<br />\n";
echo "<br />\n";

if (strlen($CONF[GENERAL]['prefix']) < 1)
  die("An installation prefix is required to continue.");

echo "Connecting to " . $CONF[DATABASE]['site_db'] . "...<br />\n";
@ob_flush();

$connect = &ADONewConnection($CONF[DATABASE]['dbtype']);
$connect->debug = true;
$connect->autoRollback = true;
if ($CONF[DATABASE]['dbtype'] == "sqlite") {
  $connect->PConnect($CONF[DATABASE]['site_db']);
} else if ($CONF[DATABASE]['dbtype'] == "mysqli") {
  if(!$connect->PConnect($CONF[DATABASE]['dbhost'], $CONF[DATABASE]['dbuser'], $CONF[DATABASE]['dbpass'], $CONF[DATABASE]['site_db']))
    die($connect->ErrorMsg());
}
echo "Successfully connected.<br />\n";
echo "Checking for table " . $CONF[GENERAL]['prefix'] . "_users<br />\n";
@ob_flush();

if(!in_array($CONF[GENERAL]['prefix'] . '_users', $connect->MetaTables('TABLES'))) {
  echo "Table does not exist.  Creating table...<br />\n";
  @ob_flush();
  $sql = table_def('users');
  if(!$connect->Execute($sql))
    die($connect->ErrorMsg());
  echo "Successfully created table.<br />\n";
} else {
  echo "Table exists.<br />\n";
}

echo "Checking for table " . $CONF[GENERAL]['prefix'] . "_games<br />\n";
@ob_flush();

if(!in_array($CONF[GENERAL]['prefix'] . '_games', $connect->MetaTables('TABLES'))) {
  echo "Table does not exist.  Creating table...<br />\n";
  @ob_flush();
  $sql = table_def('games');
  if(!$connect->Execute($sql))
    die($connect->ErrorMsg());
  echo "Successfully created table.<br />\n";
} else {
  echo "Table exists.<br />\n";
}

echo "Running test code...<br />\n";
@ob_flush();

$table = $CONF[GENERAL]['prefix'] . "_game1_cards";
$card = $connect->GetRow("select * from `" . $table . "` where card_id = ?", array(2));
$temp = $connect->GetInsertSQL($table, $card);
print $temp;


//$manip = new GameManipulator();
//$manip->CreateGame(array("coandco@gmail.com", "haxotpi@gmail.com"));
//echo "Valid: " . ($manip->valid ? "true" : "false") . ", Gameid: " . $manip->gameid . "<br />\n";
//print_r($manip->game_info);


echo 'Click <a href="admin.php">here</a> to add an admin user';
@ob_flush();

?>