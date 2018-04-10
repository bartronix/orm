<?php
class EntityProxy extends AbstractProxy {
	private $item = null;
	protected $mapper;
	protected $condition;
	
	public function __construct($mapper, $condition) { 
		$this->mapper = $mapper;
		$this->condition = $condition;
	}
	
	public function get() {
		if($this->item == null) {	
			$this->load();			
		}
		return $this->item;
	}
	
	public function load() {		
		$this->item = $this->mapper->findById($this->condition);		
	}
}