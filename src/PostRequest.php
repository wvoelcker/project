<?php
namespace WillV\Project;

class PostRequest {

	static public function dataFromJSON($postData, $incomingRequestBody = null) {
		if ($incomingRequestBody === null) {
			$incomingRequestBody = file_get_contents('php://input');
		}
		if (empty($postData)) {
			$incomingData = @json_decode($incomingRequestBody, true);

			if (is_array($incomingData) and !empty($incomingData)) {
				return $incomingData;
			}
		}
		return $postData;
	}
}
