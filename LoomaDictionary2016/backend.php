<?php
	function login($info, $user) {
		return true;
	}
	function createEntry($word, $user) {
		return true;
	}
	function readSimplified($args) {
		return array('values' => 'simple');
	}
	function readAdvanced($args) {
		return array('values' => 'advance');
	}
	function publish($user) {
		return true;
	}
	function updateStaging($new, $user) {
		return true;
	}
	if(!isset($_REQUEST['loginInfo']) or !isset($_REQUEST['user']) or
			!login($_REQUEST['loginInfo'], $_REQUEST['user'])) {
		$response['status'] = array( 'type' => 'error', 'value' => 'Not logged in');
	} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' and isset($_REQUEST['wordList'])) {
		$list = json_decode($_REQUEST['wordList']);
		$skipped = 0;
		foreach ($list as $word) {
			$success = createEntry($word, $_REQUEST['user']);
			if (!$success) {
				if(!isset($response['skipped'])) {
					$response['skipped'] = array();
				}
				$response['skipped'][] = $word;
				$skipped++;
			}
		}
		$response['status'] = array('type' => 'success', 'value' => "added words, skipped $skipped words");
	} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' and isset($_REQUEST['searchArgs'])) {
		if ($_REQUEST['simplified'] == "true") {
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
	$encoded = json_encode($response);
	header('Content-type: application/json');
	exit($encoded);
	
?>