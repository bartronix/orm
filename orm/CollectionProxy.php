<?php
/**  
* @copyright Bart Leemans
* @author Bart Leemans <contact@bartleemans.be>
* @version 1.0
* @license MIT
*/
class CollectionProxy extends AbstractProxy {
	private $item = null;
	protected $mapper;
	protected $paramkey;
	protected $paramval;
	
	public function __construct($mapper, $paramKey, $paramVal) {
		$this->mapper = $mapper;
		$this->paramKey = $paramKey;
		$this->paramVal = $paramVal;		
	}
	
	public function get() {
		if ($this->item == null){			
			$this->load();			
		}		
        	return $this->item;
	}
	
	public function load() {
		$this->item = $this->mapper->findMany(array("conditions" => array($this->paramKey . " = ?", $this->paramVal)));
	}
}
