<?php

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
			return new MongoClient();
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

		//get translation(HARD, PROBLEMS USING URLs AND CONNECTING TO GOOGLE SERVER)
		$np = 
		
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
		//do we need to specify object id??
		"ch_id" => "3EN06", //figure out what this is
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

	function findAllDefinitionsSingleWord($args, $stagingConnection, $loomaConnection) {

		//find all entries in stagin database
		$js = stagingCriteriaToJavascript($args);

		//find all entries in looma database

		//find all entries with the same object id and overwrite

		//create array with all staging 

		//create array with all words from looma database

		//add stagind paramaters to words in looma array

		//return final array with both arrays combined
	}

	/**
	*takes an array of parameters to be used in the search query ($args),
	*a connection to the staging database, and a connection to the looma database
	*
	*returns an array with the kind of view (simplified), page number, max number of pages, 
	*word data (definitions, id, date entered, etc.), and staging data
	*(whether the word has been accepted, modified, deleted, etc.)
	*/
	function readSimplified($args, $stagingConnection, $loomaConnection) {
		//to do:
		//if there are no criteria, return everything
			//if there is just a word, return that word from all databases
			//else (only drawing from stagind database now)
				//return everything that satisfies the conditions

		global $wordsPerPage;

		//create array to return at the end
		$finalArray = array('format' => 'simple', 'page' => $args['page']);

		//boolean to see if all of the fields are false in array $args
		$bool = $args['added'] or $args['modified'] or $args['accepted'];

		if($bool == 'false'){
			//we are drawing from both databases.  do we return everything or specify a search query
			if($args['text'] == ''){

				//get cursors to all elements
				$stagingCursor = $stagingConnection->database_name->collection_name->find();
					//adjust database and collection names here to match looma 
				$loomaCursor = $loomaConnection->database_name->collection_name->find();

				//figure out how many total pages
				$numTotalWords = $stagingCursor->count(true) + $loomaCursor->count(true);
				$numPages = $numTotalWords / $wordsPerPage;

				//skip to the correct page (if above the max, just return last page)
				if ($args['pages'] <= $numPages){
					//here we need to figure out how much to skip in each cursor, or we just do things inefficiently
				}
				//this means it is above the max
				else{
					//here we need to figure out how much to skip
				}

				//put them into the correct format
				//return an array of everything

			}
			else{
				//return everything with the appropriate word (or portion of a word)
			}
		}

		//if this is executed, we will only be drawing fron the staging and must filter our results accordingly
		else{
			//encode criteria as js function
			$js = stagingCriteriaToJavascript($args);

			//get all elements that match the criteria
			$stagingCursor = $stagingConnection->database_name->collection_name->find(array('$where' => $js));

			//figure out how many total pages
			$numTotalWords = $stagingCursor->count(true)
			$numPages = $numTotalWords / $wordsPerPage;

			//add the maxPage info to the final array
			array_push($finalArray, 'maxPage' => $numPages);

			//skip to the correct page (if above the max, just skip to last last page)
			skipToAppropriateLocation($stagingCursor, $args, $numPages, $numTotalWords);

			//put the words in an array
			$wordsArray = compileSimpleWordsArray($stagingCursor);

			//add words array to final array
			array_push ($finalArray, 'words' => $wordsArray);

			//return an array of everything
			return $finalArray;

		}
			

		return array('values' => 'simple');
	}

	function getSingleWordFromLooma ($connection) {

	}


	//return a string with the function
	/**
	*  Generates a javascript function in string form to perform the search query
	*  takes all the necessary search arguments to be incorperated
	*	returns a string with the javascript function
	*/
	function stagingCriteriaToJavascript($args){
		$finalFunction = "function() {return this.word.includes(" . $args['text'] . ") && (";
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

	/**
	*  creates an array with all the words and their data for 
	*  entry in the final array of data for simplified view
	*  takes a cursor to elements in the staging database
	*	returns the array of all the words snd their data
	*/
	function compileSimpleWordsArray ($stagingCursor){
		$wordsArray = array();
		for ($i = 0; $i < 10; $i = $i + 1){
			if($stagingCursor->hasNext() == 'true')
			array_push ($wordsArray, compileSingleSimpleWord($stagingCursor->getNext()));
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

	//make sure all the necessary fields are included
	/**
	*  creates an array with all the word data required for the simplified view
	*  takes an array with all the data needed
	*  returns the completed array
	*/
	function compileSimpleWordData ($allWordData){
		return array(

				'word' => $allWordData['word'], 
				'pos' => $allWordData['pos'], 
				'nep' => $allWordData['nep'],
				'def' => $allWordData['def'], 
				'mod' => $allWordData['mod'], 
				'date' => $allWordData['date_entered'],
			);
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