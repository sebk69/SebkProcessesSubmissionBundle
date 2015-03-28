<?php
/**
 * This file is a part of SebkProcessesSubmission
 * Copyright 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 **/
	 

namespace Sebk\ProcessesSubmissionBundle\Business;

/**
 * Class Job
 * @package Sebk\ProcessesSubmissionBundle\Business
 */
class Job {
    private $processName = null;
    private $methodName = null;
    private $object = null;
    private $parameters = array();
    private $processIndicator = null;
    private $cloned = false;
    private $parametersCloned = false;

    const JOB_TYPE_FUNCTION = "function";
    const JOB_TYPE_METHOD = "method";

    /**
     * @return string | null
     */
    public function getProcessName()
    {
        return $this->processName;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setProcessName($name)
    {
        $this->processName = $name;

        return $this;
    }

    /**
     * @return null
     */
    public function getProcessIndicator()
    {
        return $this->processIndicator;
    }

    /**
     * @param $indicator
     * @return $this
     */
    public function setProcessIndicator($indicator)
    {
        $this->processIndicator = $indicator;

        return $this;
    }

    /**
     * @return $this
     */
    private function cloneParameters()
    {
        foreach($this->parameters as $key => $parameter) {
            if(is_object($parameter)) {
                $this->parameters[$key] = clone $parameter;
            }
        }

        return $this;
    }

    public function __clone()
    {
        if($this->cloned) {
            $this->object = clone $this->object;
        }

        if($this->parametersCloned) {
            $this->cloneParameters();
        }
    }

    /**
     * @param $object
     * @param $methodName
     * @param array $parameters
     * @param bool $clone
     * @param bool $cloneParameters
     * @return $this
     * @throws JobException
     */
    public function setMethodCall($object, $methodName, $parameters = array(), $clone = false, $cloneParameters = false)
    {
        if(!is_object($object)) {
            throw new JobException("not_object", 'The $object parameter must be an object');
        }

        if(!method_exists($object, $methodName)) {
            throw new JobException("method_not_exists", "The object no implement method ($methodName)");
        }

        $this->type = static::JOB_TYPE_METHOD;
        $this->methodName = $methodName;
        $this->functionName = null;
        $this->parameters = $parameters;

        if($clone) {
            $this->object = clone $object;
            $this->cloned = true;
        } else {
            $this->object = $object;
            $this->cloned = false;
        }

        if($cloneParameters) {
            $this->cloneParameters();
            $this->parametersCloned = true;
        } else {
            $this->parametersCloned = false;
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function doJob()
    {
        switch($this->type) {
            case static::JOB_TYPE_FUNCTION:
                call_user_func_array($this->functionName, $this->parameters);
                break;
            case static::JOB_TYPE_METHOD:
                call_user_func_array(array($this->object, $this->methodName), $this->parameters);
                break;
        }

        return $this;
    }
}