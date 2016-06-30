<?php

//there's a lot of work left to do on this one
	/**
	*takes an array of parameters to be used in the search query ($args),
	*a connection to the staging database, and a connection to the looma database
	*
	*returns an array with the kind of view (advanced), page number, max number of pages, 
	*word data (definitions, id, date entered, etc.), and staging data
	*(whether the word has been accepted, modified, deleted, etc.)
	*/
	function readAdvanced($args, $stagingConnection, $loomaConnection) {
		global $wordsPerPage;

		//returns all the definitions for the words

		//cases for args that are sent over
			//

		$finalArray = array('format' => 'advanced', 'page' => 1, 'maxPage' => 1,);

		return $finalArray;
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












?>