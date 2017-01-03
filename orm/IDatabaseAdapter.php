<?php
interface IDatabaseAdapter
{
	public function insert($table, array $data);
	public function update($table, array $data, $where);
	public function delete($table, $where);
	public function findOne($table, array $params);
	public function findMany($table, array $params);
}