<?php
	/**
	 * Opens a connection to the official database and returns it
	 * @param unknown $login The login information of the user which must be verified before
	 * the connection can be created
	 * @return a new connection object if successful, otherwise return null
	 */
	function openOfficialConnection($login) {
		return array();
	}
	
	/**
	 * Opens a connection to the staging database and returns it
	 * @param unknown $login The login information of the user which must be verified before
	 * the connection can be created
	 * @return a new connection object if successful, otherwise return null
	 */
	function openStagingConnection($login) {
		return array();
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
	function createEntry($word, $officialConnection, $stagingConnection, $user) {
		return true;
	}
	
	/**
	 * Reads all definitions that match the inputted search arguments and returns them
	 * @param array $args The search arguments
	 * @param connection $officialConnection The connection to the official database
	 * @param connection $stagingConnection The connection to the staging database
	 * @return array An array in the following format:
	 * {format: 'simple', page: the page of the search (integer),
	 * 		maxPage: the maximum page available with the same search (integer),
	 * 		words: array of staging-style word objects
	 * }
	 */
	function readSimplified($args, $officialConnection, $stagingConnection) {
		return array('format' => 'simple', 'page' => 1, 'maxPage' => 1, 'words' =>
				array(array('wordData' =>
						array('word' => 'test', 'pos' => 'noun', 'nep' => 'sklfj',
						'def' => 'a large quiz', 'mod' => 'me', 'date' => 'Jan 24, 2012',
						'other' => 'nothing'),
					'metaData' =>
						array('added' => true, 'modified' => true, 'accepted' => true,
						'deleted' => false)
				))
		);
	}
	
	/**
	 * Reads all definitions that match the inputted search arguments and returns them along
	 * with all other definitions for those words
	 * @param array $args The search arguments
	 * @param connection $officialConnection The connection to the official database
	 * @param connection $stagingConnection The connection to the staging database
	 * @return array An array in the following format:
	 * {format: 'simple', page: the page of the search (integer),
	 * 		maxPage: the maximum page available with the same search (integer),
	 * 		words: array of staging-style word objects
	 * }
	 */
	function readAdvanced($args, $officialConnection, $stagingConnection) {
		return array('format' => 'advanced', 'page' => 1, 'maxPage' => 1, 'words' =>
				array(array('wordData' =>
						array('word' => 'test', 'pos' => 'noun', 'nep' => 'sklfj',
						'def' => 'a large quiz', 'mod' => 'me', 'date' => 'Jan 24, 2012',
						'other' => 'nothing'),
					'metaData' =>
						array('added' => true, 'modified' => true, 'accepted' => true,
						'deleted' => true)
				))
		);
	}
	
	/**
	 * Publishes all accepted and deleted changes to the official database
	 * @param connection $officialConnection The connection to the official database
	 * @param connection $stagingConnection The connection to the staging database
	 * @param string $user The user
	 * @return boolean True if publishing succeeded, false otherwise
	 */
	function publish($officialConnection, $stagingConnection, $user) {
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
	 * word in staging-style.
	 */
	function updateStaging($change, $officialConnection, $stagingConnection, $user) {
		// should also automatically turn on modified and off accepted for field modifications
		// but not for status modifications. Should also update other non-editable wordData
		// such as 'mod' and 'date'
		//Also only allow modifications of permitted fields
		return array('wordData' =>
					array('word' => 'test', 'pos' => 'noun', 'nep' => 'sklfj',
							'def' => 'a large quiz', 'mod' => 'me', 'date' => 'Jan 24, 2012',
							'other' => 'changed'),
					'metaData' =>
					array('added' => true, 'modified' => true, 'accepted' => false,
							'deleted' => true));
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
				$success = createEntry($word, $officialConnection, $stagingConnection,
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
			if ($_REQUEST['simplified'] == "true") {
				$response['data'] = readSimplified($_REQUEST['searchArgs'],
												$officialConnection, $stagingConnection);
			} else {
				$response['data'] = readAdvanced($_REQUEST['searchArgs'],
												$officialConnection, $stagingConnection);
			}
		} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' and isset($_REQUEST['publish'])) {
			// publishes accepted changes to the official database
			$success = publish($officialConnection, $stagingConnection,
								$_REQUEST['loginInfo']['user']);
			if($success) {
				$response['status'] = array('type' => 'success');
			} else {
				$response['status'] = array('type' => 'error', 'value' => 'publishing failed');
			}
		} elseif($_SERVER['REQUEST_METHOD'] == 'POST' and isset($_REQUEST['mod'])) {
			// modifies the definition in the way specified by the 'mod'
			$success = updateStaging($_REQUEST['mod'], $officialConnection, $stagingConnection,
										$_REQUEST['loginInfo']['user']);
			if($success) {
				$response['status'] = array('type' => 'success');
				// if successful, also return the new value of the word
				$response['new'] = $success;
			} else {
				// if failed, don't return a new value, since the old value is still valid
				$response['status'] = array('type' => 'error',
												'value' => 'modification failed');
			}
		} else {
			// the arguments didn't match any acceptable requests
			$response['status'] = array('type' => 'error', 'value' => 'invalid request',
					'request' => json_encode($_REQUEST));
		}
	}
	
	//return json encoded response
	$encoded = json_encode($response);
	header('Content-type: application/json');
	exit($encoded);
	
?>