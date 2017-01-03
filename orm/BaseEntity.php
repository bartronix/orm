<?php
abstract class BaseEntity{
	//default id value - you can override this in your class if necessary
	public $fields = array("id" => 0);	
	public function __set($name, $value)
	{
		$this->fields[$name] = $value;
	}
	public function __get($name)
	{
		$field = array_key_exists($name, $this->fields) ? $this->fields[$name] : null;		
		if ($field instanceof EntityProxy) {			
			$field = $field->get();			
		}
		if ($field instanceof AbstractProxy){
			$field = $field->get();
		}
		return $field;
	}
	
	public function __isset($name) {
		if(isset($this->fields[$name])){
			return true;
		}
		return false;
	}
	
	public function getFields(){
		return $this->fields;
	}
}
