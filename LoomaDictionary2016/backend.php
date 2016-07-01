<?php
	date_default_timezone_set("America/Los_Angeles");

	$testword = array('wordData' =>
						array('word' => 'test', 'pos' => 'noun', 'nep' => 'sklfj',
						'def' => 'a large quiz', 'mod' => 'me',
						'date' => '1/24/2012 01:23:45am', 'id' => 'asdklfjals',
						'ch_id' => 'sdlkf'),
					'stagingData' =>
						array('added' => true, 'modified' => true, 'accepted' => true,
						'deleted' => false));

	/**
	 * Opens a connection to the official database and returns it
	 * @param unknown $login The login information of the user which must be verified before
	 * the connection can be created
	 * @return a new connection object if successful, otherwise return null
	 */
	function openOfficialConnection($login) {
		return 2;
	}
	
	/**
	 * Opens a connection to the staging database and returns it
	 * @param unknown $login The login information of the user which must be verified before
	 * the connection can be created
	 * @return a new connection object if successful, otherwise return null
	 */
	function openStagingConnection($login) {
		return 2;
	}
	
	/**
	 * Closes the given connection. After this is called, the variable should be unset
	 * @param unknown $connection The connection to disconnect
	 */
	function closeConnection($connection) {
	}
	
	/**
	 * Creates new dictionary entries with the given word
	 * @param string $word The word to create entries fors
	 * @param connection $officialConnection the connection to the official database
	 * @param connection $stagingConnection the connection to the staging database
	 * @param string $user the name of the user
	 * @return boolean true if the entry was created successfully, false otherwise
	 */
	function createEntryWrapper($word, $officialConnection, $stagingConnection, $user) {
		return true;
	}
	
	function readStagingWrapper($args, $stagingConnection) {
		global $testword;
		return array("page" => 1, "maxPage" => 1, "words" => array($testword,
				$testword, $testword, $testword, $testword, $testword, $testword,
				$testword, $testword, $testword, $testword, $testword, $testword, $testword,
				$testword, $testword));
	}
	
	function readOfficialWrapper($args, $officialConnection) {
		global $testword;
		return array($testword);
	}
	
	/**
	 * Publishes all accepted and deleted changes to the official database
	 * @param connection $officialConnection The connection to the official database
	 * @param connection $stagingConnection The connection to the staging database
	 * @param string $user The user
	 * @return boolean True if publishing succeeded, false otherwise
	 */
	function publishWrapper($officialConnection, $stagingConnection, $user) {
		return true;
	}
	
	/**
	 * Updates the staging database with the given changes
	 * @param array $change an array containing information about the change in the following
	 * format: { wordId: the id of the word to change (string), field: name of field to change
	 * (string, uses frontend names, which don't always correspond to backend names),
	 * new: the new value of the field (string), deleteToggled: true if 'deleted' should be
	 * toggled (boolean)
	 * @param connection $officialConnection The connection to the official database
	 * @param connection $stagingConnection The connection to the staging database
	 * @param string $user the user
	 * @return false if the update failed; if successful, should return the new value of the
	 * word in staging-style. If the type of update was 'cancel' will return true to
	 * differentiate it from a failure and a modification (since now the page should reload
	 * to get the official entry or nothing)
	 */
	function updateStagingWrapper($change, $officialConnection, $stagingConnection, $user) {
		global $testword;
		// should also automatically turn on modified and off accepted for field modifications
		// but not for status modifications. Should also update other non-editable wordData
		// such as 'mod' and 'date'
		//Also only allow modifications of permitted fields
		$field = $change["field"];
		if($field == 'cancel') {
			// cancel change;
			return true;
		} else if($change["deleteToggled"] == "true") {
			$testword["stagingData"]["deleted"] = !$testword["stagingData"]["deleted"];
		} elseif($field == "word" or $field == "root" or $field == "pos" or $field == "nep"
				or $field == "def") {
			$testword["wordData"][$field] = $change["new"];
			$testword["stagingData"]["modified"] = true;
			$testword["stagingData"]["accepted"] = false;
		} elseif($field == "stat") {
			$testword["stagingData"]["accepted"] = !$testword["stagingData"]["accepted"];
		} else {
			return false;
		}
		$testword["wordData"]["mod"] = $user;
		$testword["wordData"]["date"] = date("m/d/Y h:i:sa");
		return $testword;
	}
	
	function moveToStagingWrapper($moveId, $officialConnection, $stagingConnection, $user) {
		return true;
	}
	
	$officialConnection;
	$stagingConnection;
	
	if(!isset($_REQUEST['loginInfo'])) { // no login data means not logged in
		$response['status'] = array( 'type' => 'error', 'value' => 'Not logged in');
	} else {
		// attempt to create connections using the login data provided
		$officialConnection = openOfficialConnection($_REQUEST['loginInfo']);
		$stagingConnection = openStagingConnection($_REQUEST['loginInfo']);
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
						$officialConnection);
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
												'value' => 'modification failed');
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
	
	closeConnection($officialConnection);
	closeConnection($stagingConnection);
	unset($officialConnection);
	unset($stagingConnection);
	
	//return json encoded response
	$encoded = json_encode($response);
	error_log("$encoded");
	header('Content-type: application/json');
	exit($encoded);
	
?>