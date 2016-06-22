<?php

	function createConnectionToStaging($login){
		if(verifyLogin($login))
		{
			//default is localhost, insert parameters to specify address of database
			return new MongoClient();
		}
		return null;
	}

	function createConnectionToLooma($login){
		if(verifyLogin($login))
		{
			//default is localhost, insert parameters to specify address of database
			return new MongoClient();
		}
		return null;
	}

	function createEntry($word, $connection) {
		
		//get definition
		//get translation
		//get the rw
		//get the POS
		//get the date and time

		//put everything into a doc

		// insert the doc into the database


		return true;
	}
	function readSimplified($args, $connection) {
		//
		return array('values' => 'simple');
	}
	function readAdvanced($args, $connection) {
		return array('values' => 'advance');
	}
	function publish($login, $connection) {
		//transfer the data from the staging databse to the Looma database
		return true;
	}
	function updateStaging($new, $connection) {
		return true;
	}

?>