<?php

//make sure searching for word data before accessing word or id, but take out word data in convert method before putting into dictionary.  dont assume word data when pulling from looma database

	/**
	 *	Author: Colton
	 *  Date: 7/1/16
	 *	Filename: BackendFunctions.php
	 *
	 *	Description:
	 *	This file contains the functions necessary to interact with the looma
	 *	database and staging database to retrieve and store dictionary entries.
	 *	It also interacts with the various apis necessary to retrieve translations 
	 *	and definitions to create new entries.  The main functions intended for use
	 *	by other files are as follows:
	 *
	 *	createEntry - this function creates a new entry in the staging database given
	 *	a word. 
	 *
	 *	readStagingDatabase - this function returns a page of results in the staging
	 *	database, as specified by the arguments passed to it
	 *
	 * 	Find Definition with ID - this function takes an ID and looks for a matching
	 *	entry first in the staging database, then in the looma database, and returns
	 *	false if it finds nothing.
	 *
	 * 	findDefinitonsForSingleWordLooma - this function finda all the definitions 
	 * 	for a given word in the Looma database, returning an array with all the 
	 * 	entries found
	 *	
	 * 	publish - this function publishes all the accepted changes to the looma 
	 *  database
	 * 	
	 *	updateStaging - this function saves a document to the staging database.  If 
	 * 	the document already exists, then it is overwritten by the new doc
	 *	
	 * 	There are also functions to create connections to each database, and a dummy 
	 * 	function to check login until the working one is finished
	 * 	
	 */



	require 'translator.php';

	//edit this value to determine how many words will be assigned to each page
	$wordsPerPage = 10;

	//enter address to staging database here
	$stagingAddress = '';

	//enter address to Looma database here
	$loomaAddress = '';

	//Change to reflect Looma database name
	$loomaDB = 'fakeLooma';

	//change to reflect the collection you would like to use within the staging database
	$loomaCollection = 'official';

	//Change to reflect Looma database name
	$stagingDB = 'fakeLooma';

	//change to reflect the collection you would like to use within the staging database
	$stagingCollection = 'staging';

	/**
	*	Dummy function to always return true
	*/
	function checkLogin ($login){
		return true;
	}

	/**
	*Returns a connection to the staging database.  the address still needs to be specified
	*/
	function createConnectionToStaging($login){
		if(checkLogin($login))
		{
			global $stagingAddress;
			//default is localhost, insert parameters to specify address of database
			return new MongoClient($stagingAddress);
		}
		return null;
	}

	/**
	* returns true when either a string 'true' or a boolean true is entered
	*/
	function checkTrue ($bool){
		if ($bool == true or $bool === 'true'){
			return true;
		}
		else {
			return false;
		}
	}

	/**
	*Returns a connection to the looma database.  the address still needs to be specified
	*/
	function createConnectionToLooma($login){
		if(checkLogin($login))
		{
			global $loomaAddress;
			//default is localhost, insert parameters to specify address of database
			return new MongoClient($loomaAddress);
		}
		return null;
	}

	//this method should be replaced depending on the format and type of data being entered
	/**
	*creates an entry in the stagin database
	*takes the word that the entry will be created around, 
	*the connection to the staging database and the official one, and the user name
	*returns true
	*/
	function createEntry($word, $officialConnection, $stagingConnection, $user) {
		
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
		$random = generateRandomNumber(16);

		//put everything into a doc
		$doc = array( "wordData" => array(
		"en" => $word,
		"rw" => $rw,
		"np" => $np,
		"part" => $POS,
		"def" => $def,
		"rand" => $random,
		"date_entered" => $dateCreated,
		"mod" => $user),
		"stagingData" => array(
				'added' => true, 'modified' => false, 'accepted' => false,
				'deleted' => false
				)
			);

		//check to see if a similar definition already exists
		if(checkForSimilarDefinition()){
			global $stagingDB;
			global $stagingCollection;
			// insert the doc into the database
			$stagingConnection->selectDB($stagingDB)->selectCollection($stagingCollection)->save($doc);
			
			return true;
		}
		else{
			return false;
		}
		
	}

	/**
	 *	Generates a random number given a certain number of digits
	 */
	function generateRandomNumber ($numDigits){
		$numDigits = 16;
		$multiplier = 10 ** $numDigits;
		$random = rand(0, $multiplier) / $multiplier;

		return $random;
	}


	//change database and connection names depending on database being used
	/**
	*   finds a definition with the specified object id.  If it is stagin, it
	*	returns that one.  If not, it looks for it in the looma database.  If no
	*	such object exists, it returns false.  Takes the object id as a string, 
	*	a connection to the looma database, and a connection to the staging database
	*/
	function findDefinitionWithID ($_id, $loomaConnection, $stagingConnection) {
		global $stagingDB;
		global $stagingCollection;
		global $loomaDB;
		global $loomaCollection;
		//first look for the entry in the staging databse
		$stagingDefinition = $stagingConnection->selectDB($stagingDB)->selectCollection($stagingCollection)->findOne(array('_id' => $_id));

		if ($stagingDefinition != null){
			return $stagingDefinition;
		}

		//since the entry wasn't in the staging database, check the Looma database
		//fix database and collection names
		$loomaDefinition = $loomaConnection->selectDB($loomaDB)->selectCollection($loomaCollection)->findOne(array('_id' => $_id));

		if ($loomaDefinition != null){
			return $loomaDefinition;
		}

		//this means an object with the specified id could not be found.
		return false;


	}





	//edit this to make sure the array contains all the necessary info
	/**
	 *	Creates an array of entries to be displayed on a single page of the website
	 *
	 *	takes an array of arguments specifying the page number and search query
	 *  Also takes a connection to the staging database
	 *
	 *	returns an array of all the words for that page
	 */
	function readStagingDatabase ($args, $stagingConnection){
			global $wordsPerPage;
			global $stagingDB;
			global $stagingCollection;


			//encode criteria as js function
			$js = stagingCriteriaToJavascript($args);

			//get all elements that match the criteria
			$stagingCursor = $stagingConnection->selectDB($stagingDB)->selectCollection($stagingCollection)->find();

			//figure out how many total pages
			$numTotalWords = $stagingCursor->count();
			error_log("num words: " . $numTotalWords);
			$numPages = ($numTotalWords + $wordsPerPage - 1) / $wordsPerPage;

			if($numPages < 1){
				$numPages = 1;
			}

			//skip to the correct page (if above the max, just skip to last page)
			$page = skipToAppropriateLocation($stagingCursor, $args, $numPages, $numTotalWords);

			//put the words in an array
			$wordsArray = compileStagingWordsArray($stagingCursor);
			
			error_log("thingy: " . count($wordsArray));

			//create array with appropriate metadata in the beginning
			$finalArray = array( "page"=> $page, "maxPage" => $numPages, "words" => $wordsArray);
			

			return $finalArray;
	}


	/**
	 *
	 */
	function getDefinitionsFromStaging ($args, $connection) {
		global $stagingDB;
		global $stagingCollection;
			
		//encode criteria as js function
		$js = stagingCriteriaToJavascript($args);

		//get all elements that match the criteria
		$stagingCursor = $connection->selectDB($stagingDB)->selectCollection($stagingCollection)->find();

		//put the words in an array
		//remember to add in staging parameters
		$stagingWordsArray = compileStagingWordsArray($stagingCursor);

		return $stagingWordsArray;
	}

	/**
	 *	Searches the Looma database and finds all the definitions it contains for 
	 *	a single word but removes all duplicates already in the staging database
	 *
	 *	Takes the desired word and a connection to the Looma database
	 *
	 *	Returns an array with all the definitions found
	 */
	function findDefinitonsForSingleWordLooma ($word, $loomaConnection, $stagingConnection) {
		global $loomaDB;
		global $loomaCollection;
		//get all elements that match the criteria
		//FIX COLLECTION AND DATABASE NAMES
		$loomaCursor = $loomaConnection->selectDB($loomaDB)->selectCollection($loomaCollection)->find(array('word' => $word));

		//put the words in an array
		$loomaWordsArray = compileLoomaWordsArray($loomaCursor);

		//find all entries in the staging database
		$stagingArray = getDefinitionsFromStaging($args, $stagingConnection);

		//remove overwritten definitions
		$loomaArray = removeOverwrittenEntries($loomaWordsArray, $stagingArray);

		//make sure indecies are consecutive
		return $loomaArray;
	}

	function removeOverwrittenEntries ($betaArray, $dominantArray){
		//nested for each loop, compare object ids and overwrite entires in the beta array
		$betaCount = count($betaArray);
		$dominantCount = count($dominantArray);
		for($indexDominant = 0; $indexDominant < $dominantCount; $indexDominant++) {
	 		for ($indexBeta=0; $indexBeta < $betaCount; $indexBeta++) { 
	 			
	 			//make sure the key for object id is correct
	 			if ($betaArray[$indexBeta]['_id'] == $dominantArray[$indexDominant]['_id']) {
	 				unset($betaArray[$indexBeta]);
	 			}
	 		}
		}
		return array_merge($betaArray);
	}

	//return a string with the function
	/**
	*  Generates a javascript function in string form to perform the search query
	*  takes all the necessary search arguments to be incorperated
	*	returns a string with the javascript function
	*/
	function stagingCriteriaToJavascript($args){
		$bool = false;
		
		$finalFunction = "function() {return this.en == '" . $args['text'] . "' && (";
		if($args['added'] == 'true'){
			$finalFunction = $finalFunction . "this.added == true ||";
			$bool = true;
		}
		if($args['modified'] == 'true'){
						$finalFunction = $finalFunction . "this.modified == true ||";
						$bool = true;
		}
		if($args['accepted'] == 'true'){
						$finalFunction = $finalFunction . "this.accepted == true ||";
						$bool = true;
		}
		//append the necessary ending to the javascript function
		
		if($bool){
			$finalFunction = substr($finalFunction, 0, -2) . ") ; } ";
		} else {
			$finalFunction = substr($finalFunction, 0, -4) . " ; } ";
		}

		return $finalFunction;
	}


	/**
	*  creates an array with all the words and their data for 
	*  entry in the final array of data for simplified view
	*  takes a cursor to elements in the staging database
	*	returns the array of all the words snd their data
	*/
	function compileStagingWordsArray ($stagingCursor){
		global $wordsPerPage;

		$wordsArray = array();
		for ($i = 0; $i < $wordsPerPage and $stagingCursor->hasNext(); $i = $i + 1){
			error_log("COPY");
			error_log("other: " . json_encode(debug_backtrace()));
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
			if($loomaCursor->hasNext())
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
		$singleWord = array('wordData' => compileSimpleWordData($allWordData), 'stagingData' => $allWordData['stagingData']);

		return $singleWord;
	}

	/**
	*  compiles all the data necessary for a single word in simplified view in *preparation for entry in the word array
	*  takes all the word's data (from the database)
	*  returns the array for that word
	*/
	function compileSingleLoomaWord($allWordData){
		$singleWord = array('wordData' => compileSimpleWordData($allWordData), 'stagingData' => compileDefaultStagingData());

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
				'_id' => $allWordData['_id'],
				'en' => $allWordData['en'], 
				'rw' => $allWordData['rw'],
				'part' => $allWordData['part'], 
				'np' => $allWordData['np'],
				'def' => $allWordData['def'], 
				'mod' => $allWordData['mod'],
				'rand' =>  $allWordData['rand'],
				'date_entered' => $allWordData['date_entered'],

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
		global $stagingDB;
		global $stagingCollection;

		if($numPages <= 1){
			//do nothing
			error_log("I DO NOTHING");
			return $args['page'];
		}
		else if ($args['page'] <= $numPages){
			$amount = ($args['page'] - 1 ) * $wordsPerPage;
			$stagingCursor->skip($amount);
			return $args['page'];
		}
		//this means it is above the max
		else{
			$stagingCursor->skip(($numPages - 1) * $wordsPerPage);
			return $numPages;
		}
	}
















	/**
	 * Moves an entry from the looma database to the staging database
	 * Takes a connection to each database, as well of the id of the object to be
	 * moved and the user requesting to move the object
	 * Returns true
	 */
	function moveEntryToStaging ($stagingConnection, $loomaConnection, $_id, $user){
		global $stagingDB;
		global $stagingCollection;
		global $loomaDB;
		global $loomaCollection;
		
		$doc = $loomaConnection->selectDB($loomaDB)->selectCollection($loomaCollection)->findOne(array('_id' => $_id));

		$doc = 
		//fix database and collection name
		$stagingConnection->selectDB($stagingDB)->selectCollection($stagingCollection)->save($doc);

		$loomaConnection->selectDB($loomaDB)->selectCollection($loomaCollection)->remove($doc);

		return true;
	}



	//transfer the data from the staging databse to the Looma database
	/**
	* transefers all the accepted changes from the staging database to the looma database
	* also removes all deleted items from the staging database
	* takes a connection to the staging database and a connection to the looma database
	* returns true
	*/
	function publish($stagingConnection, $loomaConnection, $user) {
		
		global $stagingDB;
		global $stagingCollection;
		global $loomaDB;
		global $loomaCollection;

		$stagingCursor = $stagingConnection->selectDB($stagingDB)->selectCollection($stagingCollection)->find();

		foreach($stagingCursor as $doc){
			//check to make sure the object has not been deleted and has been accepted
			if($doc['stagingData']['deleted'] == 'false' and $doc['stagingData']['accepted'] == 'true')
			{
				//convert to correct format
				$newDoc = convertFromStagingToLooma($doc, $user);

				//remove from staging
				$stagingConnection->selectDB($stagingDB)->selectCollection($stagingCollection)->remove($doc);

				//adjust database and collection name!!!
				$loomaConnection->selectDB($loomaDB)->selectCollection($loomaCollection)->save($newDoc);

			}
			//if it has been deleted, remove it
			else if ($doc['stagingData']['deleted'] == 'true')
			{
				//remove from database
				$stagingConnection->selectDB($stagingDB)->selectCollection($stagingCollection)->remove($doc);
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
	function convertFromStagingToLooma($doc, $user)
	{
		$dateEntered = getDateAndTime("America/Los_Angeles");

		$doc = $doc['wordData'];

		return $newDoc = array (
				'_id' => $doc['_id'],
				//"ch_id" => "3EN06",
				"en" => $doc["en"],
				"rw" => $doc["rw"],
				"np" => $doc["np"],
				"part" => $doc["part"],
				"def" => $doc["def"],
				"rand" => $doc["rand"],
				"mod" => $user,
				"date_entered" => $dateEntered
				);
	}

	/**
	*Takes the new documet to be incorporated into the staging 
	*database, a connection to that database, and a string with the user modifying
	*the entry
	*
	*Returns true
	*/
	function updateStaging($new, $connection, $user) {
		global $stagingDB;
		global $stagingCollection;
		$collection = $connection->selectDB($stagingDB)->selectCollection($stagingCollection);

		//update user and modified status
		$new['wordData']['mod'] = $user;
		$new['stagingData']['modified'] = true;

		//save it to the collection
		$collection->save($new);

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


	//convert from dictionary to staging style (add staging data)
	function convertFromLoomaToStaging ($doc){
		$finalArray = array('wordData' => $doc, 'stagingData' => generateBlankStagingData());

		return $finalArray;
	}

	function generateBlankStagingData () {
		return array(
				'added' => false, 'modified' => false, 'accepted' => false,
				'deleted' => false
			);
	}

	function removeStaging ($_id, $stagingConnection) {
		global $stagingDB;
		global $stagingCollection;
		//remove object with id


	}

//work on this
	function checkForSimilarDefintion () {
		return true;
	}

 
?>