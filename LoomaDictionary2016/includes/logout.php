<?php
if(!isset($_COOKIE['user_id']))
{
	//Need the function
	require ('includes/login_functions.php');
	redirect_user();
}
else
{
	//Delete the cookies
	setcookie ('user_id', ", time()-3600, '/', ", o, o);
}

//Set page title
$page_title = 'Logged Out!';
// include ('includes/header.html');

//Customized message 
echo "<h1>Logged Out!</h1>
<p>You are now logged out, {$COOKIE['user_id']}!</p>";

//include ('includes/footer.html');

?>
