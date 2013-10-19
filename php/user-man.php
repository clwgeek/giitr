<?php

require '../PasswordHash.php';
//require 'pwqcheck.php';

// In a real application, these should be in a config file instead
$db_host = 'clwgeekMyapp.db.11910712.hostedresource.com';
$db_port = 3306;
$db_user = 'clwgeekMyapp';
$db_pass = 'cl8!2000#Mom';
$db_name = 'clwgeekMyapp';


// Do we have the pwqcheck(1) program from the passwdqc package?
$use_pwqcheck = FALSE;
// We can override the default password policy
$pwqcheck_args = '';
#$pwqcheck_args = 'config=/etc/passwdqc.conf';

// Base-2 logarithm of the iteration count used for password stretching
$hash_cost_log2 = 8;
// Do we require the hashes to be portable to older systems (less secure)?
$hash_portable = FALSE;

/* Dummy salt to waste CPU time on when a non-existent username is requested.
 * This should use the same hash type and cost parameter as we're using for
 * real/new hashes.  The intent is to mitigate timing attacks (probing for
 * valid usernames).  This is optional - the line may be commented out if you
 * don't care about timing attacks enough to spend CPU time on mitigating them
 * or if you can't easily determine what salt string would be appropriate. */
$dummy_salt = '$2a$08$1234567890123456789012';

// Are we debugging this code?  If enabled, OK to leak server setup details.
$debug = TRUE;

function fail($pub, $pvt = '')
{
	global $debug;
	$msg = $pub;
	if ($debug && $pvt !== '')
		$msg .= ": $pvt";
/* The $pvt debugging messages may contain characters that would need to be
 * quoted if we were producing HTML output, like we would be in a real app,
 * but we're using text/plain here.  Also, $debug is meant to be disabled on
 * a "production install" to avoid leaking server setup details. */
	exit("An error occurred ($msg).\n");
}
function checkPassword($pwd) 
{
	//$strength = array("Blank","Very Weak","Weak","Medium","Strong","Very Strong");
	$score = 1;

	if (strlen($pwd) < 1)
	{
		return $strength[0]; 
	}
	if (strlen($pwd) < 4)
	{
		return $strength[1]; 
	}
	if (strlen($pwd) >= 8)
	{
		$score++; 
	}
	if (strlen($pwd) >= 10)
	{
		$score++; 
	}
	if (preg_match("/[a-z]/", $pwd) && preg_match("/[A-Z]/", $pwd)) 
	{
		$score++; 
	}
	if (preg_match("/[0-9]/", $pwd)) 
	{
		$score++; 
	}
	if (preg_match("/.[!,@,#,$,%,^,&,*,?,_,~,-,£,(,)]/", $pwd)) 
	{
		$score++; 
	}
	return $score; 
}

function my_pwqcheck($newpass, $oldpass = '', $user = '')
{
	global $use_pwqcheck, $pwqcheck_args;
	if ($use_pwqcheck)
		return pwqcheck($newpass, $oldpass, $user, '', $pwqcheck_args);

/* Some really trivial and obviously-insufficient password strength checks -
 * we ought to use the pwqcheck(1) program instead. */
	$check = '';
	if (strlen($newpass) < 7)
		$check = 'way too short';
	else if (stristr($oldpass, $newpass) ||
	    (strlen($oldpass) >= 4 && stristr($newpass, $oldpass)))
		$check = 'is based on the old one';
	else if (stristr($user, $newpass) ||
	    (strlen($user) >= 4 && stristr($newpass, $user)))
		$check = 'is based on the username';
	if ($check)
		return "Bad password ($check)";
	if (checkPassword($newpass)<5)
		$check = 'Password is too weak';
	return 'OK';
}

function get_post_var($var)
{
	$val = $_POST[$var];
	if (get_magic_quotes_gpc())
		$val = stripslashes($val);
	return $val;
}

header('Content-Type: text/plain');

$op = $_POST['op'];
if ($op !== 'new' && $op !== 'login' && $op !== 'change')
	fail('Unknown request');

$user = get_post_var('user');
/* Sanity-check the username, don't rely on our use of prepared statements
 * alone to prevent attacks on the SQL server via malicious usernames. */
if (!preg_match('/^[a-zA-Z0-9_]{1,60}$/', $user))
	fail('Invalid username');

$pass = get_post_var('pass');
/* Don't let them spend more of our CPU time than we were willing to.
 * Besides, bcrypt happens to use the first 72 characters only anyway. */
if (strlen($pass) > 72)
	fail('The supplied password is too long');

$db = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if (mysqli_connect_errno())
	fail('MySQL connect', mysqli_connect_error());

$hasher = new PasswordHash($hash_cost_log2, $hash_portable);

if ($op === 'new') {
	if (($check = my_pwqcheck($pass, '', $user)) !== 'OK')
		fail($check);

	$hash = $hasher->HashPassword($pass);
	if (strlen($hash) < 20)
		fail('Failed to hash new password');
	unset($hasher);

	($stmt = $db->prepare('insert into users (user, pass) values (?, ?)'))
		|| fail('MySQL prepare', $db->error);
	$stmt->bind_param('ss', $user, $hash)
		|| fail('MySQL bind_param', $db->error);
	if (!$stmt->execute()) {
/* Figure out why this failed - maybe the username is already taken?
 * It could be more reliable/portable to issue a SELECT query here.  We would
 * definitely need to do that (or at least include code to do it) if we were
 * supporting multiple kinds of database backends, not just MySQL.  However,
 * the prepared statements interface we're using is MySQL-specific anyway. */
		if ($db->errno === 1062 /* ER_DUP_ENTRY */)
			fail('This username is already taken');
		else
			fail('MySQL execute', $db->error);
	}

	$what = 'User created';
} else {
	$hash = '*'; // In case the user is not found
	($stmt = $db->prepare('select pass from users where user=?'))
		|| fail('MySQL prepare', $db->error);
	$stmt->bind_param('s', $user)
		|| fail('MySQL bind_param', $db->error);
	$stmt->execute()
		|| fail('MySQL execute', $db->error);
	$stmt->bind_result($hash)
		|| fail('MySQL bind_result', $db->error);
	if (!$stmt->fetch() && $db->errno)
		fail('MySQL fetch', $db->error);

// Mitigate timing attacks (probing for valid usernames)
	if (isset($dummy_salt) && strlen($hash) < 20)
		$hash = $dummy_salt;

	if ($hasher->CheckPassword($pass, $hash)) {
		$what = 'Authentication succeeded';
	} else {
		$what = 'Authentication failed';
		$op = 'fail'; // Definitely not 'change'
	}

	if ($op === 'change') {
		$stmt->close();

		$newpass = get_post_var('newpass');
		if (strlen($newpass) > 72)
			fail('The new password is too long');
		if (($check = my_pwqcheck($newpass, $pass, $user)) !== 'OK')
			fail($check);
		$hash = $hasher->HashPassword($newpass);
		if (strlen($hash) < 20)
			fail('Failed to hash new password');
		unset($hasher);

		($stmt = $db->prepare('update users set pass=? where user=?'))
			|| fail('MySQL prepare', $db->error);
		$stmt->bind_param('ss', $hash, $user)
			|| fail('MySQL bind_param', $db->error);
		$stmt->execute()
			|| fail('MySQL execute', $db->error);

		$what = 'Password changed';
	}

	unset($hasher);
}

$stmt->close();
$db->close();

echo "$what\n";


?>


<html>
<head>
	
	<title>User</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link href="css/bootstrap.min.css" rel="stylesheet" type="text/css" />
	<link href="css/bootstrap-responsive.min.css" rel="stylesheet" type="text/css" />
	<link href="css/custom.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript"> </script>
</head>	
<body>
	

      <div class="navbar-inner">
        <div class="container">
          <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="brand" href="index.html">GTO</a>
          <div class="dropdown">
            <!-- Link or button to toggle dropdown -->  
            <ul class="dropdown-menu pull-left" role="menu" aria-labelledby="dLabel">    
             <li><a tabindex="-1" href="#">Action</a></li>    
             <li><a tabindex="-1" href="#">Another action</a></li>    
             <li><a tabindex="-1" href="#">Something else here</a></li>    
             <li class="divider"></li>    
             <li><a tabindex="-1" href="#">Separated link</a></li>  
            </ul>
          </div>
          <div class="nav-collapse collapse">
            <ul class="nav">
              <li class="active"><a href="index.html">Home</a></li>
              <li><a href="activities.html">Activities</a></li>
              <li><a href="products.html">Products</a></li>
              <li><a href="about.html">About</a></li>
            <ul class="nav pull-right"><!--THE PULL RIGHT MOVES THIS TO THE RIGHT OF THE BLACK HEADER-->
               <li class="dropdown">
                 <a href="#" class="dropdown-toggle" data-toggle="dropdown">Your Account <b class="caret"></b></a>
                  <ul class="dropdown-menu">
                    <li><a href="login.html">Login</a></li>
                    <li><a href="profile.html">Profile</a></li>
                    <li><a href="shopping_cart.html">Cart</a></li>
                </ul> 
               </li>
            </ul>
              <li class="dropdown"><!--THIS ONE WORKS-->
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Categories <b class="caret"></b></a>
                <ul class="dropdown-menu">
                  <li><a href="#">Rock Climbing</a></li>
                  <li><a href="#">Mountaineering</a></li>
                  <li><a href="#">Backpacking</a></li>
				  <li><a href="#">Hunting</a></li>
				  <li><a href="#">Hiking</a></li>
                  <li class="divider"></li>
                  <li class="nav-header">Zone</li>
                  <li><a href="#">Eastern</a></li>
                  <li><a href="#">Western</a></li>
				  <li><a href="#">Northern</a></li>
				  <li><a href="#">Southern</a></li>
                </ul>
              </li>
            </ul>
            <form class="navbar-form pull-right">
              <input class="span2" placeholder="Email" type="text">
              <input class="span2" placeholder="Password" type="password">
              <button type="submit" class="btn">Sign in</button>
            </form>
          </div><!--/.nav-collapse -->
        </div>
      </div>
<div class="container">
    <div class="page-header" >
					<h1 align="right"> God is in the room</h1>
						<h3>This page is under construction, please check back frequently to observe the progress. </h3>					
					</div>			
					</div>
</div>
	<div class="row-fluid" color = "white">	
	<?php echo "$what\n";?>
</div> 
	<div class="span12">
	<p>
		This site is created by Christy Watson in cooperation with ..... . 
	</p>
</div>
</body>
</html>
