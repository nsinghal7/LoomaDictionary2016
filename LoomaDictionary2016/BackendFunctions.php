<?php

	require 'translator.php';

	//edit this value to determine how many words will be assigned to each page
	$wordsPerPage = 10;

	/**
	*Returns a connection to the staging database.  the address still needs to be specified
	*/
	function createConnectionToStaging($login){
		if(checkLogin($login))
		{
			//default is localhost, insert parameters to specify address of database
			return new MongoClient();
		}
		return null;
	}

	/**
	*Returns a connection to the looma database.  the address still needs to be specified
	*/
	function createConnectionToLooma($login){
		if(checkLogin($login))
		{
			//default is localhost, insert parameters to specify address of database
			return new MongoClient(192.161.1.128/Looma);
		}
		return null;
	}

	/**
	 * Closes the given connection. After this is called, the variable should be unset
	 * @param unknown $connection The connection to disconnect
	 */
	function closeConnection($connection) {
		
		//I don't think you actually want to do this..... discuss
	}

	/**
	*creates an entry in the stagin database
	*takes the word that the entry will be created around, 
	*the connection to the staging database, and the user name
	*returns true
	*/
	function createEntry($word, $stagingConnection, $user) {
		
		//get definition(find api)
		$def = 

		//get translation
		$np = translateToNepali($word);
		
		//get the rw (hopefully this will be included in the dictionary api)
		$rw =

		//get the POS (hopefully this is included in the dictionary api)
		$POS = 
		
		//get the date and time
		$dateCreated = getDateAndTime("America/Los_Angeles");
		
		//generate random number
		$numDigits = 16;
		$multiplier = 10 ** $numDigits;
		$random = rand(0, $multiplier) / $multiplier;


		//put everything into a doc
		$doc = array(
		"_id" => ObjectId();
		"en" => $word,
		"rw" => $rw,
		"np" => $np,
		"part" => $POS,
		"def" => $def,
		"rand" => $random,
		"date_entered" => $dateCreated,
		"mod" => $user,
		"stagingData" => array(
				'added' => true, 'modified' => false, 'accepted' => false,
				'deleted' => false
				)
			);

		// insert the doc into the database
		$stagingConnection->database_name->collection_name->save($doc);

		return true;
	}








































	function readStagingDatabase ($args, $stagingConnection){
			global $wordsPerPage;


			//encode criteria as js function
			$js = stagingCriteriaToJavascript($args);

			//get all elements that match the criteria
			$stagingCursor = $stagingConnection->database_name->collection_name->find(array('$where' => $js));

			//figure out how many total pages
			$numTotalWords = $stagingCursor->count(true)
			$numPages = $numTotalWords / $wordsPerPage;

			//skip to the correct page (if above the max, just skip to last page)
			skipToAppropriateLocation($stagingCursor, $args, $numPages, $numTotalWords);

			//put the words in an array
			$wordsArray = compileStagingWordsArray($stagingCursor);

			return $wordsArray;
	}


	/**
		only use this to search for a single word and get back all definitions.  may be obsolete now
	*/
	function findAllDefinitionsSingleWordStaging($args, $stagingConnection, $loomaConnection) {

		//find all entries in staging database

		$stagingArray = getDefinitionsFromStaging($args, $stagingConnection);

		return $stagingArray;
	}

	function getDefintionsFromStaging ($args, $connection) {
			
		//encode criteria as js function
		$js = stagingCriteriaToJavascript($args);

		//get all elements that match the criteria
		$stagingCursor = $connection->database_name->collection_name->find(array('$where' => $js));

		//put the words in an array
		//remember to add in staging parameters
		$stagingWordsArray = compileStagingWordsArray($stagingCursor);

		return stagingWordsArray();
	}


	function findDefinitonsForSingleWordLooma ($word, $loomaConnection) {
		
		//get all elements that match the criteria
		//FIX COLLECTION AND DATABASE NAMES
		$loomaCursor = $connection->database_name->collection_name->find(array('word' => $args['word']));

		//put the words in an array
		$loomaWordsArray = compileLoomaWordsArray($loomaCursor);

		return $loomaWordsArray;
	}

	//return a string with the function
	/**
	*  Generates a javascript function in string form to perform the search query
	*  takes all the necessary search arguments to be incorperated
	*	returns a string with the javascript function
	*/
	function stagingCriteriaToJavascript($args){
		$finalFunction = "function() {return this.word.equals(" . $args['text'] . ") && (";
		if($args['added'] == 'true'){
			$finalFunction = $finalFunction . "this.added == true ||";
		}
		if($args['modified'] == 'true'){
						$finalFunction = $finalFunction . "this.modified == true ||";
		}
		if($args['accepted'] == 'true'){
						$finalFunction = $finalFunction . "this.accepted == true ||";
		}
		//append the necessary ending to the javascript function
		$finalFunction = substr($finalFunction, 0, -2) . ") ; } ";

		return $finalFunction;
	}


	/**
	*  creates an array with all the words and their data for 
	*  entry in the final array of data for simplified view
	*  takes a cursor to elements in the staging database
	*	returns the array of all the words snd their data
	*/
	function compileStagingWordsArray ($stagingCursor){
		$wordsArray = array();
		for ($i = 0; $i < 10; $i = $i + 1){
			if($stagingCursor->hasNext() == 'true')
			array_push ($wordsArray, compileSingleSimpleWord($stagingCursor->getNext()));
		}

		return $wordsArray;
	}


	/**
	*  creates an array with all the words and their data for 
	*  entry in the final array of data for simplified view
	*  takes a cursor to elements in the staging database
	*	returns the array of all the words snd their data
	*/
	function compileLoomaWordsArray ($loomaCursor){
		$wordsArray = array();
		for ($i = 0; $i < $loomaCursor->count(); $i = $i + 1){
			if($loomaCursor->hasNext() == 'true')
			array_push ($wordsArray, compileSingleLoomaWord($loomaCursor->getNext()));
		}

		return $wordsArray;
	}

	/**
	*  compiles all the data necessary for a single word in simplified view in preparation for entry in the word array
	*  takes all the word's data (from the database)
	*  returns the array for that word
	*/
	function compileSingleSimpleWord($allWordData){
		$singleWord = array('wordData' => array(), 'stagingData' => $allWordData['stagingData']);
		array_push($singleWord['wordData'], compileSimpleWordData($allWordData));
		return $singleWord;
	}

	/**
	*  compiles all the data necessary for a single word in simplified view in *preparation for entry in the word array
	*  takes all the word's data (from the database)
	*  returns the array for that word
	*/
	function compileSingleLoomaWord($allWordData){
		$singleWord = array('wordData' => array(), 'stagingData' => array());
		array_push($singleWord['wordData'], compileSimpleWordData($allWordData));
		array_push($singleWord;['stagingData'], compileDefaultStagingData());
		return $singleWord;
	}

	//make sure all the necessary fields are included
	/**
	*  creates an array with all the word data required for the simplified view
	*  takes an array with all the data needed
	*  returns the completed array
	*/
	function compileSimpleWordData ($allWordData){
		return array(
				'_id' : ObjectId(),
				'word' => $allWordData['word'], 
				'pos' => $allWordData['pos'], 
				'nep' => $allWordData['nep'],
				'def' => $allWordData['def'], 
				'mod' => $allWordData['mod'], 
				'date' => $allWordData['date_entered'],
			);
	}


	/**
	*  takes a cursor for the staging database, the search arguments, the max
	*  number of pages, and the total number of words the cursor can iterate through
	*
	*  skips the cursor over the appropriate number of entries.  
	*/
	function skipToAppropriateLocation ($stagingCursor, $args, $numPages, $numTotalWords){
		global $wordsPerPage;

		if($numPages == 1){
			//do nothing
		}
		else if ($args['pages'] <= $numPages){
			$stagingCursor->skip(($args['pages'] - 1 ) * $wordsPerPage);
		}
		//this means it is above the max
		else{
			$stagingCursor->skip(($numPages - 1) * $wordsPerPage);
		}
	}




















































	function moveEntryToStaging ($stagingConnection, $loomaConnection, $_id){
		$doc = $loomaConnection->database_name->collection_name->findOne(array('_id' => $_id));

		$stagingConnection->database_name->collection_name->save($doc);

		$loomaConnection->database_name->collection_name->remove($doc);

		return true;
	}



	//transfer the data from the staging databse to the Looma database
	/**
	* transefers all the accepted changes from the staging database to the looma database
	* also removes all deleted items from the staging database
	* takes a connection to the staging database and a connection to the looma database
	* returns true
	*/
	function publish($stagingConnection, $loomaConnection) {

		$stagingCursor = $stagingConnection->database_name->collection_name->find();

		foreach($stagingCursor as $doc){
			//check to make sure the object has not been deleted and has been accepted
			if($doc['stagingData']['deleted'] == 'false' and $doc['stagingData']['accepted'] == 'true')
			{
				//convert to correct format
				$newDoc = convert($doc);

				//remove from staging
				$stagingConnection->database_name->collection_name->remove($doc);

				//adjust database and collection name!!!
				$loomaConnection->database_name->collection_name->save($newDoc);

			}
			//if it has been deleted, remove it
			else if ($doc['stagingData']['deleted'] == 'true')
			{
				//remove from database
				$stagingConnection->database_name->collection_name->remove($doc);
			}
		}

		return true;
	}


	//edit this fuction if you would like to adapt this function for somethign other than dictionary words
	/**
	*converts a doc from the staging version to the version entered into the looma database
	*takes the doc in staging database form
	*returns that doc with the information required for entry into the Looma database
	*/
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
				"mod" => $doc["mod"],
				"date_entered" => $dateEntered
				);
	}

	/**
	*Takes the new documet to be incorporated into the staging 
	*database and a connection to that database
	*Returns true
	*/
	function updateStaging($new, $connection) {
		$connection->database_name->collection_name->save($new);
		return true;
	}

	/**
	* Takes the timezone to be used in the generation of the timestamp
	* returns a string with the date and time in the specified format
	*/
	function getDateAndTime($timezone) {
		date_default_timezone_set($timezone);
		return $dateEntered = date('m-d-Y') . " at " . date('h:i:sa');
	}
 
?>