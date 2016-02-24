<?php
namespace WillV\Project;

class ArrayFilterer {

	private function __construct() {
	}

	public static function create() {
		$arrayFilterer = new ArrayFilterer;
		return $arrayFilterer;
	}

	public function filterByKey($inputArray, $allowedKeys) {
		return array_intersect_key($inputArray, array_flip($allowedKeys));
	}

}