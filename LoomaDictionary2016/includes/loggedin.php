<?php

//if cookie not present, redirect user
if(!isset($_COOKIE['user_id']))
{
	require ('includes/login_functions.php');
	redirect_user();
}

// Set page title
$page_title = 'Logged In!';
//include ('includes/header.html');

// Print customized message
echo "<h1>Logged In!</h1>
<p>You are now logged in, {$_COOKIE ['user_id']}!</p>
<p><a href=\"logout.php\">Logout</a></p>";

//include ('includes/footer.html');
?>