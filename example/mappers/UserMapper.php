<?php
class UserMapper extends DataMapper {
	protected $datasource = "user";	
	protected $entityClass = "User";		
	protected $dbfields = array('id' => array('type' => 'int','primary' => true,'auto_increment' => true, 'dbtype' => 'int')
		,'username' => array('type' => 'string','dbtype' => 'varchar(30)'));
	protected $relations = array('Article' => array('type' => 'multiple','alias' => "articles")); 
	//alias is optional but comes in handy when you have situations like "Story" which becomes Storys by default, which you can rename to "stories"
}