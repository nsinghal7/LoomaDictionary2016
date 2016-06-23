<?php

	function createConnectionToStaging($login){
		if(checkLogin($login))
		{
			//default is localhost, insert parameters to specify address of database
			return new MongoClient();
		}
		return null;
	}

	function createConnectionToLooma($login){
		if(checkLogin($login))
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
		$dateCreated = getDateAndTime("America/Los_Angeles");
		
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
		"stagingData" => array(
				'added' => true, 'modified' => false, 'accepted' => false,
				'deleted' => false
				)
			);

		// insert the doc into the database
		$stagingConnection->database_name->collection_name->save($doc);

		return true;
	}
	function readSimplified($args, $connection) {
		//returns the things that are modified
		return array('values' => 'simple');
	}

	//
	function readAdvanced($args, $connection) {
		//returns all the definitions for the words

		$finalArray = array('format' => 'advanced', 'page' => 1, 'maxPage' => 1,);



		return $finalArray;
	}

	//transfer the data from the staging databse to the Looma database
	function publish($stagingConnection, $loomaConnection) {

		$stagingCursor = $stagingConnection->database_name->collection_name->find();

		foreach($stagingCursor as $doc){
			//check to make sure the object has not been deleted and has been accepted
			if(!$doc['stagingData']['deleted'] and $doc['stagingData']['accepted'])
			{
				//convert to correct format
				$newDoc = convert($doc);

				//remove from staging
				$stagingConnection->database_name->collection_name->remove($doc);

				//adjust database and collection name!!!
				$loomaConnection->database_name->collection_name->save($newDoc);

			}
			//if it has been deleted, remove it
			else if ($doc['stagingData']['deleted'])
			{
				//remove from database
				$stagingConnection->database_name->collection_name->remove($doc);
			}
		}

		return true;
	}

	//converts a doc from the staging version to the version entered into the looma database
	//edit this fuction if you would like to adapt this function for somethign other than dictionary words
	function convert($doc)
	{
		$dateEntered = getDateAndTime("America/Los_Angeles");

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

	function getDateAndTime($timezone) {
		date_default_timezone_set($timezone);
		return $dateEntered = date('m-d-Y') . " at " . date('h:i:sa');
	}
 
?>