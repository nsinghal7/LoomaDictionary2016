<?php


	$wordsPerPage = 10;


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

		//get translation(HARD, PROBLEMS USING URLs AND CONNECTING TO GOOGLE SERVER)
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

		//generate al the necessary stagindData


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
			$js = criteriaToJavascript($args);

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

	//fix this to reflect the search criteria in a javascript function
	//return a string with the function
	function criteriaToJavascript($args){
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

	function compileSimpleWordsArray ($stagingCursor){
		$wordsArray = array();

	}

	//
	function readAdvanced($args, $stagingConnection, $loomaConnection) {
		global $wordsPerPage;

		//returns all the definitions for the words

		//cases for args that are sent over
			//

		$finalArray = array('format' => 'advanced', 'page' => 1, 'maxPage' => 1,);



		return $finalArray;
	}

	//transfer the data from the staging databse to the Looma database
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