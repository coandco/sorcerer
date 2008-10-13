<?php
require_once "include/config.inc.php";
require_once "include/functions.inc.php";
require_once "include/user.inc.php";

$dblan = dbConnect();

if(($_SESSION['level'] != 'A') && (in_array($CONF[GENERAL]['prefix'] . '_users', $dblan->MetaTables('TABLES')))){
  echo "You do not have sufficient permisions to access this page";
	die;
}
//echo md5($_REQUEST['password']);
if(isset($_REQUEST['create'])) {
  if((isset($_SESSION['loggedin']) || (!in_array($CONF[GENERAL]['prefix'] . '_users', $dblan->MetaTables('TABLES'))))) {
    //echo "creating";
    
    //Do basic input validation
    $error_text = '';
    if (!(preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z0-9.-]+$/i", $_REQUEST['username'])))
      $error_text .= 'Username must be an email address.' . "\n";
    
    if (!($_REQUEST['password'] == $_REQUEST['password2']))
      $error_text .= '<br />Passwords must match.' . "\n";
      
    if (preg_match('/[^A-Za-z0-9_-]/', $_REQUEST['nickname']))
      $error_text .= '<br />Nickname may only contain alphanumeric characters, dash, and underscore.' . "\n";
    
    if ($CONF[EMAIL]['mailsend'] == "true")
      sendMail($_REQUEST['username'], 
               $_REQUEST['password'],
               $CONF[EMAIL]['mailfrom'],
               $CONF[EMAIL]['mailhost'],
               $CONF[EMAIL]['mailuser'],
               $CONF[EMAIL]['mailpass']);
    
    if (strlen($dblan->ErrorMsg()) > 0)
      $error_text .= '<br />' . $dblan->ErrorMsg();
    
    if (strlen($error_text) == 0)
      $dblan->Execute("insert into " . $CONF[GENERAL]['prefix'] . "_users (username, password, level, nickname, lastlogin, created) values (?, ?, ?, ?, ?, ?)", 
                      array(strtolower($_REQUEST['username']),
      	              md5($_REQUEST['password']),
                      $_REQUEST['level'],
                      $_REQUEST['nickname'],
                      time(),
                      time()));
    if (strlen($dblan->ErrorMsg()) > 0)
      $error_text .= '<br />' . $dblan->ErrorMsg();
  }
}

function sendMail($createduser, $createdpass, $mailfrom, $mailhost, $mailuser, $mailpass) {
  require_once "Mail.php";
  global $CONF;
  $subject = "You've been added!";
  $body = "Hello, \n You've created a user on http://" . $CONF[GENERAL]['hostname'] . "/ \n\n";
  $body .= "Username: " . $createduser . "\n";
  $body .= "Password: " . $createdpass . "\n";
  $body .= "This password is stored in an encrypted format and can not be read; do NOT LOSE IT.";
  $from_header = "From: " . $mailfrom;
//  $to_them =  mail($to, $subject, $body, $from_header);
//  $to_me =  mail("haxot@softhome.net", $subject, $body, $from_header);
  $from = $CONF[EMAIL]['mailfrom'];
  $host = $CONF[EMAIL]['mailhost'];
  $username = $CONF[EMAIL]['mailuser'];
  $password = $CONF[EMAIL]['mailpass'];

  $headers = array ('From' => $mailfrom,
                    'To' => $created_user,
                    'Subject' => $subject);
  $smtp = Mail::factory('smtp', 
                        array ('host' => $mailhost,
                               'auth' => true,
                               'username' => $mailuser,
                               'password' => $mailpass)
                        );
  $mail = $smtp->send($created_user, $headers, $body);

  if (PEAR::isError($mail)) 
    echo("<p>" . $mail->getMessage() . "</p>");
  else
    echo("<p>Message successfully sent!</p>");

  if ($mailself == "true") {
    $headers = array ('From' => $mailfrom,
                      'To' => $from,
                      'Subject' => $subject);
    $smtp = Mail::factory('smtp', 
                          array ('host' => $mailhost,
                                 'auth' => true,
                                 'username' => $mailuser,
                                 'password' => $mailpass)
                         );
    $mail = $smtp->send($mailfrom, $headers, $body);

    if (PEAR::isError($mail)) 
      echo("<p>" . $mail->getMessage() . "</p>");
    else
      echo("<p>Message successfully sent!</p>");
  }

  //echo '<pre>'.$to_them."\n".$to_me.'</pre>';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 3.2//EN">
<html>
<head>
<title></title>
</head>
<body>
<?php
if(($_SESSION['loggedin']) || (!in_array($CONF[GENERAL]['prefix'] . '_users', $dblan->MetaTables('TABLES'))))
{
  if (strlen($error_text) > 0)
    print '<span style="color: red">' . $error_text . '</span><br />';
  print ("Enter in the desired information here. <br />\n");
  print ('<form enctype="multipart/form-data" action="?create" method="post">' . "\n");
  print ('<table summary="User creation form" border="0">' . "\n");
  print ('<tr><td>Username/Email:</td><td><input type="text" name="username"></td></tr>' . "\n");
  print ('<tr><td>Password:</td><td><input type="password" name="password"></td></tr>' . "\n");
  print ('<tr><td>Repeat password:</td><td><input type="password" name="password2"></td></tr>' . "\n");
  print ('<tr><td>Nickname:</td><td><input type="text" name="nickname"></td></tr>' . "\n");
  print ('<tr><td><select name="level">' . "\n");
  print ('  <option value="A">Admin</option>' . "\n");
  print ('  <option value="M">Moderator</option>' . "\n");
  print ('  <option selected value="U">User</option>' . "\n");
  print ('  <option value="G">Guest</option>' . "\n");
  print ('</select></td></tr>' . "\n");
  print ('<tr><td><input type="submit" value="create"></td></tr></table></form>'); 
}
?>
</body>
</html>
