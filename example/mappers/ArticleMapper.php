<?php
class ArticleMapper extends DataMapper{	
	protected $datasource = "article";	
	protected $entityClass = "Article";		
	protected $dbfields = array('id' => array('type' => 'int','primary' => true, 'auto_increment' => true, 'dbtype' => 'int')
		,'title' => array('type' => 'string', 'dbtype' => 'varchar(50)')
		,'userId' => array('type' => 'int', 'dbtype' => 'int'));
	protected $relations = array('User' => array('type' => 'single'));
}