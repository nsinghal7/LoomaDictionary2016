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
		
		//get definition(find api)
		$def = 

		//get translation(HARD)
		$np = 
		
		//get the rw
		$rw =

		//get the POS
		$POS = 
		
		//get the date and time
		date_default_timezone_set("America/Los_Angeles");
		$dateCreated = date('m-d-Y') . " at " . date('h:i:sa');
		
		//generate random
		$numDigits = 16;
		$multiplier = 10 ** $numDigits;
		$random = rand(0, $multiplier) / $multiplier;

		//put everything into a doc
		$doc = array(
		//do we need to specify object id??
		"ch_id" => "3EN06", //figure out what this is
		"en" => $word,
		"rw" => $rw,
		"np" => $np,
		"part" => $POS,
		"def" => $def,
		"rand" => $random
		"date_entered" => $dateCreated
			);

		// insert the doc into the database
		$connection->database_name->collection_name->save($doc);

		return true;
	}
	function readSimplified($args, $connection) {
		//returns the things that are modified
		return array('values' => 'simple');
	}
	function readAdvanced($args, $connection) {
		//returns all the definitions for the words
		return array('values' => 'advance');
	}
	function publish($login, $connection) {
		//transfer the data from the staging databse to the Looma database
		return true;
	}

	//for backend purposes, this method is equivalent to the createEntry method
	function updateStaging($new, $connection) {
		return createEntry($new, $connection);
	}

?>