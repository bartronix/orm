<?php
abstract class AbstractProxy {
	protected $mapper;
	protected $condition;
	
	public function __construct($mapper,$condition) {
		$this->mapper = $mapper;
		$this->condition = $condition;
	}
}