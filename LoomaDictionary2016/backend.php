<?php
	function login($info, $user) {
		return true;
	}
	function createEntry($word, $user) {
		return true;
	}
	function readSimplified($args) {
		
	}
	function readAdvanced($args) {
		
	}
	function publish($user) {
		
	}
	function updateStaging($new, $user) {
		
	}
	if(!isset($_REQUEST['login_info']) or !isset($_REQUEST['user']) or
			!login($_REQUEST['login_info'], $_REQUEST['user'])) {
		$response['status'] = array( 'type' => 'error', 'value' => 'Not logged in');
	} elseif ($_SERVER['REQUEST_METHOD'] == 'PUT' and isset($_REQUEST['word_list'])) {
		$list = $_REQUEST['word_list'];
		foreach ($list as $word) {
			$success = createEntry($word, $_REQUEST['user']);
			if (!$success) {
				if(!isset($response['skipped'])) {
					$response['skipped'] = array();
				}
				$response['skipped'][] = $word;
			}
		}
	} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' and isset($_REQUEST['searchArgs'])) {
		if (isset($_REQUEST['simplified'])) {
			$response['data'] = readSimplified($_REQUEST['searchArgs']);
		} else {
			$response['data'] = readAdvanced($_REQUEST['searchArgs']);
		}
	} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' and isset($_REQUEST['publish'])) {
		$success = publish($_REQUEST['user']);
		if($success) {
			$response['status'] = array('type' => 'success');
		} else {
			$response['status'] = array('type' => 'error', 'value' => 'publishing failed');
		}
	} elseif($_SERVER['REQUEST_METHOD'] == 'POST' and isset($_REQUEST['mod'])) {
		$new = json_decode(stripslashes($_REQUEST['mod']), true);
		$success = updateStaging($new, $_REQUEST['user']);
		if($success) {
			$response['status'] = array('type' => 'success');
		} else {
			$response['status'] = array('type' => 'error', 'value' => 'modification failed');
		}
	} else {
		$response['status'] = array('type' => 'error', 'value' => 'invalid request',
				'request' => json_encode($_REQUEST));
	}
	header('Content-type: application/json');
	exit(json_encode($response));
	
?>