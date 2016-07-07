<?php

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	require ('includes/login_functions.php');
	
	// use colton's method
	//check login
	list ($check, $data) = check_login($dbc,$_POST['id'], $_POST['pass']);
	
	if ($check)
	{
		//set the cookies
		setcookie ('user_id', $data['user_id']);
		//setcookie ('first_name', $data['first_name']);
		
		
		//Redirect:
		redirect_user('loggedin.php');
	}
	else
	{
		$errors = $data;
	}
	
	//close the database
}

include ('includes/login_page.php');
?>