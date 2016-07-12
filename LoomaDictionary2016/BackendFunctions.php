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
	$wordsPerPage = 3;

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
	
	// enter address of app's database connection
	$appAddress = '';
	
	// change to reflect app database name
	$appDB = 'fakeLooma';
	
	//change to reflect the collection you would like to use within the app database
	$appCollection = 'app';

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
		if ($bool === true or $bool == 'true'){
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

			return new MongoClient($loomaAddress);
		}
		return null;
	}
	
	/**
	 *Returns a connection to the app database.  the address still needs to be specified
	 */
	function createConnectionToApp($login){
		if(checkLogin($login))
		{
			global $appAddress;

			return new MongoClient($appAddress);
		}
		return null;
	}
	
	
	
	
	/**
	 * Creates an uploadProgressSession by storing the length and current position (0) in the
	 * 'app' collection of the database.
	 * @param integer $length The length to store
	 * @param unknown $appConnection The connection to the app database
	 * @param unknown $user The user who owns the session
	 */
	function createUploadProgressSession($length, $appConnection, $user) {
		global $appDB;
		global $appCollection;
		$session = array("position" => 0, "length" => $length, "user" => $user);
		$appConnection->selectDB($appDB)->selectCollection($appCollection)->insert($session);
	}
	
	
	/**
	 * Updates the current position of the progress session
	 * @param unknown $position The new position
	 * @param unknown $appConnection The connection to the app database
	 * @param unknown $user The user
	 */
	function updateUploadProgressSession($position, $appConnection, $user) {
		global $appDB;
		global$appCollection;
		$search = array("user" => $user);
		$change = array('$set' => array("position" => $position));
		
		$appConnection->selectDB($appDB)->selectCollection($appCollection)->update($search, $change);
	}
	
	
	/**
	 * Gets the progress of the upload from the session database entry referenced by the user
	 * @param unknown $appConnection The connection to the app database
	 * @param unknown $user The user
	 * @return session object in the form: {"position": (int), "length": (int)}
	 * or null if it didn't exist
	 */
	function getUploadProgress($appConnection, $user) {
		global $appDB;
		global $appCollection;
		$query = array("user" => $user);
		return $appConnection->selectDB($appDB)->selectCollection($appCollection)->findOne($query);
	}
	
	
	/**
	 * Closes the upload session referenced by the user by removing it from the database
	 * @param unknown $appConnection The connection to the app database
	 * @param unknown $user The user
	 */
	function closeUploadProgress($appConnection, $user) {
		global $appDB;
		global $appCollection;
		$query = array("user" => $user);
		$appConnection->selectDB($appDB)->selectCollection($appCollection)->remove($query);
	}
	
	
	/**
	 * Looks up the word in the pearson longman's wordwise dictionary and returns it formatted
	 * @param unknown $word The word to look up
	 * @return a list of objects with the following properties: def, rw, pos
	 */
	function lookUpWord($word) {
		$url = "http://api.pearson.com/v2/dictionaries/wordwise/entries?limit=100&headword=" . rawurlencode($word);
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
		$obj = $obj =json_decode($response, true); //true converts stdClass to associative array.
		$messyList = $obj['results'];
		$ans = array();
		foreach($messyList as $messy) {
			if($messy["headword"] == $word) {
				$senses = isset($messy["senses"]) ? $messy["senses"] : array();
				foreach($senses as $sense) {
					$def = array();
					$def['def'] = isset($sense['definition']) ? $sense['definition'] : "";
					$def['pos'] = $messy["part_of_speech"];
					$def['rw'] = ""; // this dictionary doesn't have root words
					$ans[] = $def;
				}
			}
		}
		return $ans;
	}
	

	//this method should be replaced depending on the format and type of data being entered
	/**
	 * Creates definitions for the given word and adds them all to the staging dictionary
	 * @param unknown $word the word to create entries for
	 * @param unknown $officialConnection The official database connection
	 * @param unknown $stagingConnection the staging database connection
	 * @param unknown $user the user
	 * @return boolean True if all definitions were successfully added, false if ANY failed
	 */
	function createEntry($word, $officialConnection, $stagingConnection, $user) {
		
		$dictionaryData = lookUpWord($word["word"]);
		
		$fullSuccess = true;

		//variable to make sure for loop has been entered
		$didRunForLoop = false;
		
		foreach($dictionaryData as $definition) {
			$fullSuccess &= createIndividualDefinition($word, $definition, $officialConnection, $stagingConnection, $user);
			$didRunForLoop = true;
		}
		if($didRunForLoop){
			return $fullSuccess;
		}
		return $didRunForLoop;
	}
	
	/**
	 * Creates a definition (one document in the database) and adds it to the staging database
	 * @param unknown $word the word to define
	 * @param unknown $definition The definition object to be put into the database
	 * @param unknown $officialConnection The connection to the official database
	 * @param unknown $stagingConnection The connection to the staging database
	 * @param unknown $user the user responsible
	 * @return boolean true if successful, false if failed
	 */
	function createIndividualDefinition($word, $definition, $officialConnection, $stagingConnection, $user) {
		//get definition(find api)
		$def = $definition['def'];
		
		//get translation
		$np = translateToNepali($word["word"]);
		
		//get the rw (hopefully this will be included in the dictionary api)
		$rw = $definition['rw'];
		
		//get the POS (hopefully this is included in the dictionary api)
		$POS = $definition['pos'];
		
		//get the date and time
		$dateCreated = getDateAndTime("America/Los_Angeles");
		
		//generate random number
		$random = generateRandomNumber(16);
		
		//put everything into a doc
		$doc = array( "wordData" => array(
				"en" => $word["word"],
				"ch_id" => $word["ch_id"],
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
		if(!checkForSimilarDefinition()){
			global $stagingDB;
			global $stagingCollection;
			// insert the doc into the database
			$stagingConnection->selectDB($stagingDB)->selectCollection($stagingCollection)->save(moveWordDataUpLevel($doc));
				
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
		$stagingDefinition = $stagingConnection->selectDB($stagingDB)->selectCollection($stagingCollection)->findOne(array('_id' => new MongoId($_id['$id'])));

		if ($stagingDefinition != null){
			return compileSingleSimpleWord($stagingDefinition);
		}

		//since the entry wasn't in the staging database, check the Looma database
		//fix database and collection names
		$loomaDefinition = $loomaConnection->selectDB($loomaDB)->selectCollection($loomaCollection)->findOne(array('_id' => new MongoId($_id['$id'])));

		if ($loomaDefinition != null){
			return compileSingleLoomaWord($loomaDefinition);
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

			//get all elements that match the criteria
			$collection = $stagingConnection->selectDB($stagingDB)->selectCollection($stagingCollection);
			$wordsArray = $collection->distinct("en", stagingCriteriaToMongoQuery($args));

			//figure out how many total pages
			$numTotalWords = count($wordsArray);
			$numPages = intval(($numTotalWords + $wordsPerPage - 1) / $wordsPerPage);

			if($numPages < 1){
				$numPages = 1;
			}
			
			$page = intval($args['page']);
			if($page < 1) {
				$page = 1;
			} elseif ($page > $numPages) {
				$page = $numPages;
			}

			$wordsArray = array_slice($wordsArray, ($page - 1) * $wordsPerPage, $wordsPerPage);
			
			$stagingCursor = $collection->find(array("en" => array('$in' => $wordsArray)));
			
			// put the words in an array. This time these are word objects. Should not be
			// limited by wordsPerPage, since that has already been taken into account
			$wordsArray = compileStagingWordsArray($stagingCursor);
			usort($wordsArray, "compareWords");
			//create array with appropriate metadata in the beginning
			$finalArray = array( "page"=> $page, "maxPage" => $numPages, "words" => $wordsArray);
			

			return $finalArray;
	}
	
	function compareWords($a, $b) {
		return strcmp($a["wordData"]["en"], $b["wordData"]["en"]);
	}


	/**
	 *
	 */
	function getDefinitionsFromStaging ($args, $connection) {
		global $stagingDB;
		global $stagingCollection;

		//get all elements that match the criteria
		$stagingCursor = $connection->selectDB($stagingDB)->selectCollection($stagingCollection)->find(stagingCriteriaToMongoQuery($args));

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
	 *Also takes $overwritten, which, if true, specifies that overwritten entries should be shown
	 *
	 *	Returns an array with all the definitions found
	 */
	function findDefinitonsForSingleWordLooma ($word, $loomaConnection, $stagingConnection, $overwritten) {
		global $loomaDB;
		global $loomaCollection;
		//get all elements that match the criteria
		//FIX COLLECTION AND DATABASE NAMES
		$loomaCursor = $loomaConnection->selectDB($loomaDB)->selectCollection($loomaCollection)->find(array('en' => $word));
		//put the words in an array
		$loomaWordsArray = compileLoomaWordsArray($loomaCursor);
		

		if(checkTrue($overwritten)) {
			$loomaArray = $loomaWordsArray;
		} else {
			//find all entries in the staging database
			$stagingArray = getDefinitionsFromStaging(array("text" => $word), $stagingConnection);
			
			//remove overwritten definitions
			$loomaArray = removeOverwrittenEntries($loomaWordsArray, $stagingArray);
		}
		

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
	 			if ($betaArray[$indexBeta]['wordData']['_id']->{'$id'} == $dominantArray[$indexDominant]['wordData']['_id']->{'$id'}) {
	 				unset($betaArray[$indexBeta]);
	 			}
	 		}
		}
		return array_merge($betaArray);
	}
	
	
	/**
	 * Creates a query array representing the advanced search options given in the string
	 * @param unknown $text The string of advanced search options
	 */
	function createAdvancedTextQuery($text) {
		$ans = createAdvancedAndOrQuery($text, false);
		return $ans;
	}
	
	/**
	 * Creates a query assuming that the next lowest priority operator to parse is & or |
	 * @param unknown $text The text to parse
	 * @param unknown $and True if & is the next lowest priority, false if | is
	 * @return unknown[] a mongodb style query
	 */
	function createAdvancedAndOrQuery($text, $and) {
		$list = explode($and ? "&" : "|", $text);
		foreach($list as $index => $val) {
			if($and) {
				$list[$index] = createAdvancedBaseQuery($val);
			} else {
				$list[$index] = createAdvancedAndOrQuery($val, true);
			}
		}
		return array(($and ? '$and' : '$or') => $list);
	}
	
	/**
	 * Creates a query assuming that the next lowest priority operator to parse is key:value
	 * @param unknown $text The text to parse
	 * @return unknown[] a mongodb style query
	 */
	function createAdvancedBaseQuery($text) {
		$new = explode(":", $text);
		if(count($new) != 2) {
			error_log("incorrect syntax in search: extra colon and value. Ignoring extras");
		}
		return array(trim($new[0]) => array('$regex' => new MongoRegex("/.*" . trim($new[1]) . ".*/s")));
	}
	
	/**
	 * Creates a MongoDB query that can be used to search for the given arguments. Should be
	 * replaced if the front end sends different arguments than for the Dictionary Editor
	 * @param unknown $args The arguments to parse. expects the following fields:
	 * text: (string)
	 * added: (boolean or null)
	 * modified: (boolean or null)
	 * accepted: (boolean or null) (when true, should search for accepted OR deleted, since both are
	 * publishable)
	 */
	function stagingCriteriaToMongoQuery($args) {
		if(strpos($args["text"], ":") === false) {
			// regular search
			$condition = array("en" => array('$regex' => new MongoRegex("/.*" . $args["text"] . ".*/s")));
		} else {
			// advanced search
			$condition = createAdvancedTextQuery($args["text"]);
		}
		$added = checkTrue($args['added']);
		$modified = checkTrue($args['modified']);
		$accepted = checkTrue($args['accepted']);
		if($added or $modified or $accepted) {
			// need to add another condition
			$list = array();
			if($added) {
				$list[] = array("stagingData.added" => true);
			}
			if($modified) {
				$list[] = array("stagingData.modified" => true);
			}
			if($accepted) {
				$list[] = array("stagingData.accepted" => true);
				$list[] = array("stagingData.deleted" => true);
			}
			$condition = array('$and' => array($condition, array('$or' => $list)));
		}
		return $condition;
	}


	/**
	*  creates an array with all the words and their data for 
	*  entry in the final array of data for simplified view
	*  takes a cursor to elements in the staging database
	*	returns the array of all the words and their data
	*/
	function compileStagingWordsArray ($stagingCursor){
		$wordsArray = array();
		while($stagingCursor->hasNext()){
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
		$singleWord = array('wordData' => compileSimpleWordData($allWordData), 'stagingData' => generateBlankStagingData());

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
				'ch_id' => $allWordData['ch_id'],
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
		$doc = $loomaConnection->selectDB($loomaDB)->selectCollection($loomaCollection)->findOne(array('_id' => new MongoId($_id['$id'])));

		$stagingConnection->selectDB($stagingDB)->selectCollection($stagingCollection)->save(moveWordDataUpLevel(compileSingleLoomaWord($doc)));

		//shouldn't remove the entry, since it will be replaced upon publishing. this way,
		// if the user reverts the change, the old version will still exist
		//$loomaConnection->selectDB($loomaDB)->selectCollection($loomaCollection)->remove($doc);

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

		$stagingCursor = $stagingConnection->selectDB($stagingDB)->selectCollection($stagingCollection)->find(stagingCriteriaToMongoQuery(array("text" => "", "added" => false, "modified" => false, "accepted" => true, "deleted" => true)));

		foreach($stagingCursor as $doc){
			//check to make sure the object has not been deleted and has been accepted
			if(!checkTrue($doc['stagingData']['deleted']) and checkTrue($doc['stagingData']['accepted']))
			{
				//convert to correct format
				$newDoc = convertFromStagingToLooma(compileSingleSimpleWord($doc), $user);

				//remove from staging
				$stagingConnection->selectDB($stagingDB)->selectCollection($stagingCollection)->remove($doc);

				//adjust database and collection name!!!
				$loomaConnection->selectDB($loomaDB)->selectCollection($loomaCollection)->save($newDoc);

			}
			//if it has been deleted, remove it
			else if (checkTrue($doc['stagingData']['deleted']))
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

		return array (
				'_id' => $doc['_id'],
				"ch_id" => $doc['ch_id'],
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
	*Returns the modified word
	*/
	function updateStaging($new, $connection, $user, $isStagingChange) {
		global $stagingDB;
		global $stagingCollection;
		$collection = $connection->selectDB($stagingDB)->selectCollection($stagingCollection);

		//update user and modified status
		$new['wordData']['mod'] = $user;
		if(!$isStagingChange) {
			$new['stagingData']['modified'] = true;
			$new['stagingData']['accepted'] = false;
		}
		$new['wordData']['date_entered'] = getDateAndTime('America/Los_Angeles');

		//save it to the collection
		$collection->save(moveWordDataUpLevel($new));

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
		$stagingConnection->selectDB($stagingDB)->selectCollection($stagingCollection)->remove(array("_id" => new MongoId($_id['$id'])));
	}

//work on this
	function checkForSimilarDefinition () {
		return false;
	}
	
	/**
	 * Moves the word data to the same level as the staging data. (aka turn it from backend
	 * version to database version)
	 * @param unknown $word The word to modify
	 */
	function moveWordDataUpLevel($word) {
		$ans = array();
		foreach($word['wordData'] as $key => $val) {
			$ans[$key] = $val;
		}
		$ans['stagingData'] = array();
		foreach($word['stagingData'] as $key => $val) {
			$ans['stagingData'][$key] = $val;
		}
		return $ans;
	}


 
?>