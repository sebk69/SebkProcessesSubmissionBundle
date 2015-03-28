<?php
/**
 * This file is a part of SebkProcessesSubmissionBundle
 * Copyright 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 */

namespace Sebk\ProcessesSubmissionBundle\ProcessesSubmissionServices;

/**
 * Class Process
 * @package Sebk\DixheureBundle\Toolbox
 */
class Process
{
    /**
     * @var string
     */
    private $pid;

    /**
     * Set process pid to manage
     * @param $pid
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    }

    /**
     * Get process pid managed
     * @return string
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Return true if process is running
     * @return bool
     */
    public function isRunning()
    {
        $command = 'ps -p ' . $this->pid;
        exec($command, $op);
        if (!isset($op[1])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Stop process
     * @param string $level
     * @return bool
     */
    public function stop($level = "")
    {
        $command = 'kill ' . $level . ' ' . $this->pid;
        exec($command);

        if ($this->status() == false) {
            return true;
        } else {
            return false;
        }
    }
}