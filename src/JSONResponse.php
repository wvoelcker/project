<?php
namespace WillV\Project;

class JSONResponse {
	private $statuscode, $data, $headersets = array();
	private $validStatusCodesWithMessages = array(
		200 => "OK",
		400 => "Bad Request",
		403 => "Forbidden",
		404 => "Resource Not Found",
		500 => "Internal Server Error",
	);

	static public function create($data, $statuscode = 200) {
		$response = new JSONResponse;

		if (!isset($response->validStatusCodesWithMessages[$statuscode])) {
			throw new \Exception("Invalid status code");
		}

		$response->data = $data;
		$response->statuscode = $statuscode;

		return $response;
	}

	static public function createFromValidationErrors($validationErrors) {
		return JSONResponse::create(array("status" => "validationError", "errors" => $validationErrors), 400);
	}

	public function addHeaders($headerset) {
		$this->headersets[] = $headerset;

		return $this;
	}

	public function send() {
		header("HTTP/1.1 ".$this->statuscode." ".$this->validStatusCodesWithMessages[$this->statuscode]);
		header("Content-type: application/json");
		foreach ($this->headersets as $headerset) {
			$headerset->send();
		}
		echo json_encode($this->data);
		exit;
	}
}
