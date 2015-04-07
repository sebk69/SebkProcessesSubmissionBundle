<?php
    /**
	 * This file is a part of SebkDixheureBundle
	 * Copyright 2014-2015 - Sébastien Kus
	 * Under GNU GPL V3 licence
	 */
	 

namespace Sebk\ProcessesSubmissionBundle\Business;


class BusinessFactory {
    private $container;
    public function __construct($container)
    {
        $this->container = $container;
    }

    public function get($className)
    {
        $fullClassName = '\Sebk\ProcessesSubmissionBundle\Business\\'.$className;
        if(!class_exists($fullClassName)) {
            throw new \Exception("The class $fullClassName does'nt exists in business namespace");
        }

        return new $fullClassName($this->container);
    }
}