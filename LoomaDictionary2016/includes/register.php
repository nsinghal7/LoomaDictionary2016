<?php

$page_title = 'Register';
// include ('includes/header.html');

//Check for form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	//require ('../mysqli_connect.php'); conect to db
	
	$errors = array(); //Initialize error array
	
	//check for a first name
	if (empty($_POST['first_name']))
	{
		$errors[] = 'You forgot to enter your first name.';
	}
	else
	{
		//$fn = mysqli_real_escape_string($dbc, trim($_POST['first_name']));
	}
	
	//Check for a last name
	if (empty($_POST['last_name']))
	{
		$errors[] = 'You forgot to enter your last name.';
	}
	else
	{
		//$ln = mysqli_real_escape_string($dbc, trim($_POST['last_name']));
	}
	
	//Check for an email address
	if (empty($_POST['email']))
	{
		$errors[] = 'You forgot to enter your email address.';
	}
	else
	{
		//$e = mysqli_real_escape_string($dbc, trim($_POST['email']));
	}
	
	//Check for a password and match against the confirmed password
	if (!empty($_POST['pass1']))
	{
		if ($_POST['pass1'] != $_POST['pass2'])
		{
			$errors[] = 'Your password did not match the confirmed password.';
		}
		else
		{
			$p = mysqli_real_escape_string($dbc, trim($_POST['pass1']));
		}
	}
	else
	{
		$errors[] = 'You forgot to enter your password';
	}
	
	if(empty($errors))
	{
		// Register user in the database..
		
		//Make the querry
		//insert into databas
	}
}