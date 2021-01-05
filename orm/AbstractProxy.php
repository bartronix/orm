<?php
/**
* @copyright Bart Leemans
* @author Bart Leemans <contact@bartleemans.be>
*
* @version 1.0
*
* @license MIT
*/
abstract class AbstractProxy
{
    protected $mapper;
    protected $condition;

    public function __construct($mapper, $condition)
    {
        $this->mapper = $mapper;
        $this->condition = $condition;
    }
}
