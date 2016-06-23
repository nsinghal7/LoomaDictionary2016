<?php
	/**
	 * Opens a connection to the official database and returns it
	 * @param unknown $login The login information of the user which must be verified before
	 * the connection can be created
	 */
	function openOfficialConnection($login) {
		return array();
	}
	
	/**
	 * Opens a connection to the staging database and returns it
	 * @param unknown $login The login information of the user which must be verified before
	 * the connection can be created
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
	
	function createEntry($word, $officialConnection, $stagingConnection, $user) {
		return true;
	}
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
	function publish($officialConnection, $stagingConnection, $user) {
		return true;
	}
	function updateStaging($change, $officialConnection, $stagingConnection, $user) {
		// change: {wordId: id of word to change, field: field to change, new: new value,
		//			deleteToggled: if should switch from deleted to not deleted or vice versa}
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
	if(!isset($_REQUEST['loginInfo'])) {
		$response['status'] = array( 'type' => 'error', 'value' => 'Not logged in');
	} else {
		$officialConnection = openOfficialConnection($_REQUEST['loginInfo']);
		$stagingConnection = openStagingConnection($_REQUEST['loginInfo']);
		
		if ($_SERVER['REQUEST_METHOD'] == 'POST' and isset($_REQUEST['wordList'])) {
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
			$response['status'] = array('type' => 'success',
									'value' => "added words, skipped $skipped words");
		} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' and isset($_REQUEST['searchArgs'])) {
			if ($_REQUEST['simplified'] == "true") {
				$response['data'] = readSimplified($_REQUEST['searchArgs'],
												$officialConnection, $stagingConnection);
			} else {
				$response['data'] = readAdvanced($_REQUEST['searchArgs'],
												$officialConnection, $stagingConnection);
			}
		} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' and isset($_REQUEST['publish'])) {
			$success = publish($officialConnection, $stagingConnection,
								$_REQUEST['loginInfo']['user']);
			if($success) {
				$response['status'] = array('type' => 'success');
			} else {
				$response['status'] = array('type' => 'error', 'value' => 'publishing failed');
			}
		} elseif($_SERVER['REQUEST_METHOD'] == 'POST' and isset($_REQUEST['mod'])) {
			$success = updateStaging($_REQUEST['mod'], $officialConnection, $stagingConnection,
										$_REQUEST['loginInfo']['user']);
			if($success) {
				$response['status'] = array('type' => 'success');
				$response['new'] = $success;
			} else {
				$response['status'] = array('type' => 'error',
												'value' => 'modification failed');
			}
		} else {
			$response['status'] = array('type' => 'error', 'value' => 'invalid request',
					'request' => json_encode($_REQUEST));
		}
	}
	$encoded = json_encode($response);
	header('Content-type: application/json');
	exit($encoded);
	
?>