<?php
namespace WillV\Project;

// TODO:WV:20180310:Unit tests
class PostRequest {
	static public function JSONToPOST() {
		if ($_SERVER["REQUEST_METHOD"] == "POST" and empty($_POST) and $_SERVER["CONTENT_TYPE"] == "application/json") {
			$incomingRequestBody = file_get_contents('php://input');
			$incomingData = @json_decode($incomingRequestBody, true);
			if (is_array($incomingData) and !empty($incomingData)) {
				$_POST = $incomingData;
			}
		}
	}
}
