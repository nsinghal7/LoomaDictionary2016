<?php


//Determines an absolute url and redirects user there
function redirect_user($page = 'index.php')
{
	$url = 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);
	$url = rtrim($url, "/\\");
	
	$url .= '/'.$page;
	
	header("Location: $url");
	exit();
	
}

//Checks if user + password combo exists
function check_login($coll, $id = '', $pass = '')
{
	$errors = array();
	
	//Validate id
	if (empty($id))
	{
		$errors[] = 'You forgot to enter your username.';
	}
	else
	{
		$i = addslashes($id);
	}
	
	//Validate the password
	if (empty($pass))
	{
		$errors[] = 'You forgot to enter your password.';
	}
	else
	{
		$p = addslashes($pass);
	}
	
	//Checks in username and password match the database
	if (empty($errors))
	{
		
       	$r  = $coll->findOne(array('ID' => $i, 'pass' => SHA1('$p')));
       	
        if($r != null)
       	{
       		return true;	
		}
       	else
       	{
       		$errors[] = "The username and password entered do not match those on file.";	
		}
       	
	}
    return array(false, $errors);
}
 
