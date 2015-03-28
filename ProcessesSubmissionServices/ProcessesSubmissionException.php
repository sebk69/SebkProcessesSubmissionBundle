<?php
    /**
	 * This file is a part of SebkProcessesSubmissionBundle
	 * Copyright 2015 - Sébastien Kus
	 * Under GNU GPL V3 licence
	 */
	 

namespace Sebk\ProcessesSubmissionBundle\ProcessesSubmissionServices;

/**
 * Class ProcessesSubmissionException
 * @package Sebk\ProcessesSubmissionBundle\ProcessesSubmissionServices
 */
class ProcessesSubmissionException extends \Exception
{
    public $id;

    /**
     * Constructor
     * @param $id
     * @param $message
     */
    public function __contruct($id, $message)
    {
        parent::__construct($message);
        $this->id = $id;
    }

    /**
     * Get id of exception
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}