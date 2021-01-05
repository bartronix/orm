<?php

class ArticleMapper extends DataMapper
{
    protected $datasource = 'article';
    protected $entityClass = 'Article';
    protected $dbfields = ['id' => ['type' => 'int', 'primary' => true, 'auto_increment' => true, 'dbtype' => 'int'], 'title' => ['type' => 'string', 'dbtype' => 'varchar(50)'], 'userId' => ['type' => 'int', 'dbtype' => 'int']];
    protected $relations = ['User' => ['type' => 'single']];
}
