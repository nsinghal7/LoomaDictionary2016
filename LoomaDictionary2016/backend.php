<?php
	/*
	 * File: backend.php
	 * Author: Nikhil Singhal
	 * Date: July 1, 2016
	 * 
	 * Provides the intermediate backend functionality for editor.html
	 * 
	 * This code relies on BackendFunctions.php, which in turn relies on other php files,
	 * however the methods in this file only depend upon the methods in BackendFunctions
	 * 
	 * This file was designed to communicate securely with js/editor.js, but will also
	 * work with anything else that sends correctly formatted data.
	 * 
	 * The user will soon be required to log in to use this service, functionality that
	 * will be provided through includes/login_page.php (unfinished), however for now
	 * the login information provided is assumed to be correct. The connections to the
	 * database are secure even from other php files attempting to require it because
	 * it generates the connections using the user's login information and closes and unsets
	 * the connections at the end of the script body, so they can't be reused.
	 * 
	 * Sets the default time zone to PST, so any timestamps generated by this file will
	 * be in that time zone.
	 * 
	 * 
	 * requires the user to input data in the following way for each of the following requests.
	 * Only one type of the following may be made per request.
	 * 
	 * EXPECTED ARGUMENTS:
	 * 
	 * all requests:
	 * post/get {
	 * 		loginInfo: {
	 * 						user: username (string),
	 * 						[other information not yet determined. will be defined by
	 * 							includes/login_page.php]
	 * 					}
	 * }
	 * 
	 * add list of words:
	 * 
	 * post {
	 * 		wordList: JSON encoded string representing an array of words (strings)
	 * }
	 * 
	 * search staging:
	 * 
	 * get {
	 * 		staging: true or "true"
	 * 		searchArgs: {
	 * 						text: text to search for (string)
	 * 						added: search for added words (boolean/string rep of boolean)
	 * 						modified: search for modified words (boolean/string rep of boolean)
	 * 						accepted: search for accepted words (boolean/string rep of boolean)
	 * 						page: the page of data to search on (int)
	 * 					}
	 * }
	 * 
	 * search official:
	 * 
	 * get {
	 * 		staging: false or "false"
	 * 		searchArgs: {
	 * 						word: the word to search for (string)
	 * 					}
	 * }
	 * 
	 * 
	 * publish:
	 * 
	 * get {
	 * 		publish: any value that will return as true from isset()
	 * }
	 * 
	 * modify:
	 * 
	 * get {
	 * 		mod: {
	 * 				wordId: id string of the word to modify (string)
	 * 				field: the name of the field to modify (will use front end names:
	 * 						word, stat, delete, cancel, root, pos, nep, def) (string)
	 * 				new: the new value in the edited field. may be empty if the field
	 * 						was a toggle rather than text (string)
	 * 				deleteToggled: if true, negates delete and ignores other mod data (boolean)
	 * 			}
	 * }
	 * 
	 * 
	 * move from official to staging database:
	 * get {
	 * 		moveId: id of the word to move (string)
	 * }
	 * 
	 * 
	 * Passes back the data in the following format for varying requests and outcomes. All
	 * data returned is in JSON format.
	 * 
	 * RETURNED DATA:
	 * 
	 * error:
	 * {
	 * 		status: {
	 * 					type: 'error',
	 * 					value: 'Not logged in' or 'publishing failed' or 'modifying failed'
	 * 							or 'moving failed' or 'invalid request'
	 * 					[request: the request sent (string). only present for 'invalid request']
	 * 				}
	 * }
	 * 
	 * add word list:
	 * {
	 * 		status: {
	 * 					type: 'success',
	 * 					value: 'added words, skipped [# of skipped words] words'
	 * 				},
	 * 		skipped: array of words skipped as string[]
	 * }
	 * 
	 * search staging:
	 * {
	 * 		data: {
	 * 				page: page number (int)
	 * 				maxPage: the highest page number for this search (int)
	 * 				words: list of word objects in staging format (defined in BackendFunctions)
	 * 			}
	 * }
	 * 
	 * search official:
	 * {
	 * 		data: list of word objects in staging format (defined in BackendFunctions.php)
	 * }
	 * 
	 * 
	 * publishing or modifying or moving:
	 * {
	 * 		status: {
	 * 				type: 'success'
	 * 		}
	 * }
	 * 
	 * 
	 * 
	 */
	 
	require "BackendFunctions.php";
	
	$wordDataConversions = array(array("_id", "id"), array("en", "word"), array("rw", "root"),
								 array("np", "nep"), array("part", "pos"), array("def", "def"),
								 array("rand", "rand"), array("date_entered", "date"),
								 array("mod", "mod"));
	
	
	/**
	 * Converts the word either to or from front/back end versions
	 * @param unknown $word The word to convert
	 * @param unknown $toBackend True if should be converted to backend, false if to front end
	 * @return the converted word
	 */
	function convertWord($word, $toBackend) {
		global $wordDataConversions;
		// in case $toBackend's boolean value as an int isn't 1/0
		$from = $toBackend ? 1 : 0;
		$to = $toBackend ? 0 : 1;
		
		$new = array("wordData" => array(), "stagingData" => $word["stagingData"]);
		foreach ($wordDataConversions as $conversion) {
			$new["wordData"][$conversion[$to]] = $word["wordData"][$conversion[$from]];
		}
		return $new;
	}
	
	/**
	 * Converts all words in the list using the convertWord() function. The original list WILL
	 * be modified
	 * @param unknown $list The list to convert
	 * @param unknown $toBackend True if should be converted to backend, false if to front end
	 * @return the converted list
	 */
	function convertWordList($list, $toBackend) {
		foreach ($list as $key => $word) {
			$list[$key] = convertWord($word, $toBackend);
		}
		return $list;
	}
	
	
	
	/*
	 * The following wrapper methods are designed such that any formatting of front end
	 * data can be separated out from the response code as well as the BackendFunctions.php
	 * general code. Wrappers that currently seem useless should be kept in case changes are
	 * necessary and for consistency with the others that require wrappers.
	 */
	
	
	
	
	
	
	/**
	 * Creates new dictionary entries with the given word
	 * @param string $word The word to create entries fors
	 * @param connection $officialConnection the connection to the official database
	 * @param connection $stagingConnection the connection to the staging database
	 * @param string $user the name of the user
	 * @return boolean true if the entry was created successfully, false otherwise
	 */
	function createEntryWrapper($word, $officialConnection, $stagingConnection, $user) {
		return createEntry(convertWord($word, true), $officialConnection, $stagingConnection,
							$user);
	}
	
	/**
	 * Reads entries from the staging database that match the given parameters
	 * @param unknown $args The parameters for the search
	 * @param unknown $stagingConnection The connection to the staging database
	 * @return object in the following format: {page: (int), maxPage: (int),
	 * 											words: frontend word array}
	 */
	function readStagingWrapper($args, $stagingConnection) {
		$out = readStagingDatabase($args, $stagingConnection);
		convertWordList($out["words"], false);
		return $out;
	}
	
	/**
	 * Reads entries from the official database that match the given parameters
	 * @param unknown $args The parameters for the search
	 * @param unknown $officialConnection The connection to the official database
	 * @param unknown $stagingConnection The connection to the staging database
	 * @return array of frontend words
	 */
	function readOfficialWrapper($args, $officialConnection, $stagingConnection) {
		return convertWordList(findDefinitonsForSingleWordLooma($args['word'],
													$officialConnection, $stagingConnection), false);
	}
	
	/**
	 * Publishes all accepted and deleted changes to the official database
	 * @param connection $officialConnection The connection to the official database
	 * @param connection $stagingConnection The connection to the staging database
	 * @param string $user The user
	 * @return boolean True if publishing succeeded, false otherwise
	 */
	function publishWrapper($officialConnection, $stagingConnection, $user) {
		return publish($stagingConnection, $officialConnection, $user);
	}
	
	/**
	 * Updates the staging database with the given changes
	 * @param array $change an array containing information about the change in the following
	 * format: { wordId: the id of the word to change (string), field: name of field to change
	 * (string, uses frontend names, which don't always correspond to backend names),
	 * new: the new value of the field (string, may not be relevant), deleteToggled: true if
	 * 'deleted' should be toggled, in which case all else will be ignored (boolean)}
	 * @param connection $officialConnection The connection to the official database
	 * @param connection $stagingConnection The connection to the staging database
	 * @param string $user the user
	 * @return false if the update failed; if successful, should return the new value of the
	 * word in staging-style. If the type of update was 'cancel' will return true to
	 * differentiate it from a failure and a modification (since now the page should reload
	 * to get the official entry or nothing)
	 */
	function updateStagingWrapper($change, $officialConnection, $stagingConnection, $user) {
		$former = findDefinitionWithID($change["wordId"], $officialConnection,
														$stagingConnection);
		if($change["deleteToggled"] == "true") {
			$former["stagingData"]["deleted"] = !$former["stagingData"]["deleted"];
		} elseif($change["field"] == "cancel") {
			removeStaging($change["wordId"], $stagingConnection);
			return;
		} elseif ($change["field"] == "stat") {
			$former["stagingData"]["accepted"] = !$former["stagingData"]["accepted"];
		} elseif (in_array($change["field"], array("word", "root", "nep", "pos", "def"))) {
			// for all of these the value just needs to be updated to $change["new"]
			$former["wordData"][$change["field"]] = $change["new"];
		} else {
			// illegal update attempt
			return false;
		}
		
		// assumes that updateStaging will take care of changing the modifier, date modified,
		// and all staging data, since these are general tasks.
		return updateStaging(convertWord($former, true), $stagingConnection, $user);
	}
	
	function moveToStagingWrapper($moveId, $officialConnection, $stagingConnection, $user) {
		return moveEntryToStaging($stagingConnection, $officialConnection, $moveId, $user);
	}
	
	$officialConnection;
	$stagingConnection;
	
	if(!isset($_REQUEST['loginInfo'])) { // no login data means not logged in
		$response['status'] = array( 'type' => 'error', 'value' => 'Not logged in');
	} else {
		// attempt to create connections using the login data provided
		$officialConnection = createConnectionToLooma($_REQUEST['loginInfo']);
		$stagingConnection = createConnectionToStaging($_REQUEST['loginInfo']);
		if($officialConnection == null or $stagingConnection == null) {
			$response['status'] = array('type' => 'error', 'value' => 'Not logged in');
		} else if ($_SERVER['REQUEST_METHOD'] == 'POST' and isset($_REQUEST['wordList'])) {
			// adds all definitions for all words in 'wordsList' to the staging dictionary
			$list = json_decode($_REQUEST['wordList']);
			$skipped = 0;
			foreach ($list as $word) {
				$success = createEntryWrapper($word, $officialConnection, $stagingConnection,
											$_REQUEST['loginInfo']['user']);
				if (!$success) {
					if(!isset($response['skipped'])) {
						$response['skipped'] = array();
					}
					$response['skipped'][] = $word;
					$skipped++;
				}
			}
			
			// always considered successful, but may skip words
			$response['status'] = array('type' => 'success',
									'value' => "added words, skipped $skipped words");
		} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' and isset($_REQUEST['searchArgs'])) {
			// searches for the definitions specified by the 'searchArgs' and returns results
			if ($_REQUEST['staging'] == "true") {
				$response['data'] = readStagingWrapper($_REQUEST['searchArgs'],
						$stagingConnection);
			} else {
				$response['data'] = readOfficialWrapper($_REQUEST['searchArgs'],
						$officialConnection, $stagingConnection);
			}
		} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' and isset($_REQUEST['publish'])) {
			// publishes accepted changes to the official database
			$success = publishWrapper($officialConnection, $stagingConnection,
								$_REQUEST['loginInfo']['user']);
			if($success) {
				$response['status'] = array('type' => 'success');
			} else {
				$response['status'] = array('type' => 'error', 'value' => 'publishing failed');
			}
		} elseif($_SERVER['REQUEST_METHOD'] == 'POST' and isset($_REQUEST['mod'])) {
			// modifies the definition in the way specified by the 'mod'
			$success = updateStagingWrapper($_REQUEST['mod'], $officialConnection,
								$stagingConnection, $_REQUEST['loginInfo']['user']);
			if($success) {
				$response['status'] = array('type' => 'success');
				// if successful, also return the new value of the word
				$response['new'] = $success;
			} else {
				// if failed, don't return a new value, since the old value is still valid
				$response['status'] = array('type' => 'error',
												'value' => 'modifying failed');
			}
		} elseif($_SERVER['REQUEST_METHOD'] == 'GET' and isset($_REQUEST['moveId'])) {
			$success = moveToStagingWrapper($_REQUEST['moveId'], $officialConnection,
									$stagingConnection, $_REQUEST['loginInfo']['user']);
			if($success) {
				$response['status'] = array('type' => 'success');
			} else {
				$response['status'] = array('type' => 'error', 'value' => 'moving failed');
			}
		} else {
			// the arguments didn't match any acceptable requests
			$response['status'] = array('type' => 'error', 'value' => 'invalid request',
					'request' => json_encode($_REQUEST));
		}
	}
	unset($officialConnection);
	unset($stagingConnection);
	
	//return json encoded response
	$encoded = json_encode($response);
	header('Content-type: application/json');
	exit($encoded);
	
?>