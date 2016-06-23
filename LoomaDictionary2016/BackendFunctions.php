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

	function createEntry($word, $stagingConnection) {
		
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

		//generate al the necessary metadata


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
		"stagingData" => //put metadata object here
			);

		// insert the doc into the database
		$stagingConnection->database_name->collection_name->save($doc);

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

	//transfer the data from the staging databse to the Looma database
	function publish($stagingConnection, $loomaConnection) {

		//get the date entered
		date_default_timezone_set("America/Los_Angeles");
		$dateEntered = date('m-d-Y') . " at " . date('h:i:sa');

		$stagingCursor = $stagingConnection->database_name->collection_name->find();
		foreach($stagingCursor as $doc){
			//convert to correct format
			$newDoc = convert($doc);

			//adjust database and collection name!!!
			$loomaConnection->database_name->collection_name->save($newDoc);
		}

		return true;
	}

	//converts a doc from the staging version to the version entered into the looma database
	//edit this fuction if you would like to adapt this function for somethign other than dictionary words
	function convert($doc)
	{
		return $newDoc = array (
				//object id
				"ch_id" => "3EN06", //figure out what this is
				"en" => $doc["en"],
				"rw" => $doc["rw"],
				"np" => $doc["np"],
				"part" => $doc["part"],
				"def" => $doc["def"],
				"rand" => $doc["rand"],
				"date_entered" => $dateEntered
				);
	}

	//passes the doc to be entered into the staging database and a connection to that database
	function updateStaging($new, $connection) {
		$connection->database_name->collection_name->save($new);
		return true;
	}
 
?>