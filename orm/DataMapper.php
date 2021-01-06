<?php

abstract class DataMapper
{
    protected $database;
    protected $datasource;
    protected $entityClass;
    protected $dbfields;
    protected $entity;
    protected $relations = [];

    public function __construct(IDatabaseAdapter $database)
    {
        $this->database = $database;
    }

    public function getDataSource()
    {
        return $this->datasource;
    }

    public function getDbFields()
    {
        return $this->dbfields;
    }

    public function getEntityClass()
    {
        return $this->entityClass;
    }

    private function filterFields(&$item)
    {
        foreach ($item->fields as $key => $value) {
            if (!array_key_exists($key, $this->dbfields)) {
                unset($item->fields[$key]);
            }
        }
    }

    public function query($query, array $params = [])
    {
        return $this->database->query($query, $params);
    }

    public function insert($item)
    {
        $this->filterFields($item);

        return $this->database->insert($this->datasource, $item->getFields());
    }

    public function update($item)
    {
        $this->filterFields($item);
        $pk = $this->findPk();
        $clause = $this->generateClause($pk, $item->$pk);

        return $this->database->update($this->datasource, $item->getFields(), $clause);
    }

    public function generateClause($field1, $field2)
    {
        switch (gettype($field2)) {
            case 'string':
                return $field1." = '".$field2."'";
            default:
                return $field1.' = '.$field2;
        }
    }

    public function delete($id)
    {
        $clause = $this->generateClause($this->findPk(), $id);

        return $this->database->delete($this->datasource, $clause);
    }

    //overwrite lazy load proxies with data for direct access
    private function eagerLoadRelations($relations, &$results)
    {
        if (sizeof($results) > 0) {
            foreach ($relations as $relation) {
                if (!array_key_exists($relation, $this->relations)) {
                    throw new Exception("Datamapper misconfiguration. Relation '".$relation."' not defined in datamapper.");
                }
                $mapper = $relation.'Mapper';
                $relationMapper = new $mapper($this->database);
                foreach ($this->relations as $relationKey => $relationValue) {
                    if ($relation === $relationKey) {
                        $relationType = (isset($relationValue['type']) ? $relationValue['type'] : '');
                        if ($relationType === 'single') {
                            $relationAlias = (isset($relationValue['alias']) ? $relationValue['alias'] : lcfirst($relationKey));
                        } elseif ($relationType === 'multiple') {
                            $relationAlias = (isset($relationValue['alias']) ? $relationValue['alias'] : lcfirst($relationKey).'s');
                        }
                    }
                }
                if (empty($relationType)) {
                    throw new Exception('Datamapper error: relation type expected');
                }
                $pkCount = 0;
                foreach ($relationMapper->dbfields as $dbfield => $val) {
                    if (isset($val['primary'])) {
                        $pk = $dbfield;
                        ++$pkCount;
                    }
                }
                if (($pkCount > 1) || !isset($pk)) {
                    $pk = 'id';
                }
                $relationValues = [];
                //load single entity relations
                if ($relationType === 'single') {
                    //build the search for related entries
                    $mapper = ucfirst(strtolower($relation)).'Mapper';
                    $mapper = new $mapper($this->database);
                    $field = lcfirst($mapper->entityClass).ucfirst($mapper->findPk());
                    foreach ($results as $entry) {
                        if (!in_array($entry->$field, $relationValues)) {
                            $relationValues[] = $entry->$field;
                        }
                    }
                    $firstEntry = $pk.' in (';
                    foreach ($relationValues as $t) {
                        $firstEntry .= '?,';
                    }
                    $firstEntry = rtrim($firstEntry, ',').')';
                    array_unshift($relationValues, $firstEntry);
                    $related = $this->database->findMany($relationMapper->datasource, ['conditions' => $relationValues]);

                    if (empty($related)) {
                        throw new Exception('Datamapper error: key to single entity not found, check your datamapper configuration');
                    }
                    foreach ($results as $key => $value) {
                        foreach ($related as $struct) {
                            if ($value->$field == $struct->$pk) {
                                $value->$relationAlias = $relationMapper->toEntity($struct);
                                break;
                            }
                        }
                    }
                }

                //load possible multi entity relations
                if ($relationType === 'multiple') {
                    $field = $this->parseFk($this);
                    $firstEntry = $field.' in (';
                    foreach ($results as $entry) {
                        if (!in_array($entry->$pk, $relationValues)) {
                            $firstEntry .= '?,';
                            $relationValues[] = $entry->$pk;
                        }
                    }
                    $firstEntry = rtrim($firstEntry, ',');
                    $firstEntry .= ')';
                    array_unshift($relationValues, $firstEntry);
                    $related = $this->database->findMany($relationMapper->datasource, ['conditions' => $relationValues]);
                    foreach ($results as $key => $value) {
                        $resultArray[$value->$pk] = [];
                        foreach ($related as $struct) {
                            if ($value->$pk == $struct->$field) {
                                $resultArray[$value->$pk][] = $relationMapper->toEntity($struct);
                            }
                        }
                        $value->$relationAlias = $resultArray[$value->$pk];
                    }
                }
            }
        }
    }

    public function findOne(array $params = [])
    {
        $result = $this->database->findOne($this->datasource, $params);
        if (!empty($result)) {
            $result = $this->toEntity($result);
        }
        if (!empty($params['relations'])) {
            $results[] = $result;
            $this->eagerLoadRelations($params['relations'], $results);
        }

        return $result;
    }

    public function findMany(array $params = [])
    {
        $result = $this->database->findMany($this->datasource, $params);
        $results = [];
        if (!empty($result)) {
            foreach ($result as $res) {
                $results[] = $this->toEntity($res);
            }
        }
        if (!empty($params['relations'])) {
            $this->eagerLoadRelations($params['relations'], $results);
        }

        return $results;
    }

    public function findById($id, array $params = [])
    {
        $result = $this->database->findOne($this->datasource, ['conditions' => [$this->findPk().'= ?', $id]]);
        if (!empty($result)) {
            $result = $this->toEntity($result);
        }
        if (!empty($params['relations'])) {
            $results[] = $result;
            $this->eagerLoadRelations($params['relations'], $results);
        }

        return $result;
    }

    public function findPk()
    {
        $pkCount = 0;
        $pk = '';
        foreach ($this->dbfields as $dbfield => $value) {
            if (isset($value['primary'])) {
                ++$pkCount;
                $pk = $dbfield;
            }
        }
        if ($pkCount > 1) {
            return 'id';
        }

        return $pk;
    }

    public function toEntity($data)
    {
        $this->entity = new $this->entityClass();
        foreach ($this->dbfields as $dbfield => $value) {
            $fieldparts = preg_split('/(?=[A-Z])/', $dbfield);
            $field = '';
            if (sizeof($fieldparts) > 1) {
                foreach ($fieldparts as $fieldpart) {
                    $field .= strtolower($fieldpart).'_';
                }
                $field = rtrim($field, '_');
            } else {
                $field = $dbfield;
            }
            $this->entity->$dbfield = $data->$field;
        }
        //check for relations and add lazy load proxies
        foreach ($this->relations as $key => $value) {
            $relationType = (isset($value['type']) ? $value['type'] : '');
            if (empty($relationType)) {
                throw new Exception('Datamapper error: relation type expected');
            }
            $mapper = $key.'Mapper';
            $m = new $mapper($this->database);
            if ($relationType === 'single') {
                $entityKey = $entityKey = (isset($value['alias']) ? $value['alias'] : lcfirst($key));
                $fk = $this->parseFk($m);
                $key = lcfirst($key);
                $this->entity->$entityKey = new EntityProxy($m, $data->$fk);
            }
            if ($relationType === 'multiple') {
                $entityKey = $entityKey = (isset($value['alias']) ? $value['alias'] : lcfirst($key).'s');
                $pk = $this->findPk();
                $this->entity->$entityKey = new CollectionProxy($m, $this->parseFk($this), $data->$pk);
            }
        }

        return $this->entity;
    }

    private function parseFk($m)
    {
        $pieces = explode('_', $m->datasource);
        $fk = '';
        foreach ($pieces as $piece) {
            $fk .= strtolower($piece).'_';
        }
        $fk .= strtolower($m->findPk());

        return $fk;
    }
}
