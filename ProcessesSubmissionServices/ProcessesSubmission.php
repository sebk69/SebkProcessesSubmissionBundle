<?php
/**
 * This file is a part of SebkProcessesSubmissionBundle
 * Copyright 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 */
	 

namespace Sebk\ProcessesSubmissionBundle\ProcessesSubmissionServices;


use Sebk\ProcessesSubmissionBundle\Business\BusinessFactory;
use Sebk\ProcessesSubmissionBundle\Business\Job;
use Sebk\ProcessesSubmissionBundle\Business\ProcessesIndicator;
use Sebk\ProcessesSubmissionBundle\Business\ProcessesIndicatorException;

/**
 * Class ProcessesSubmission
 * @package Sebk\ProcessesSubmissionBundle\ProcessesSubmissionServices
 */
class ProcessesSubmission {
    private $doctrine;
    private $em;
    protected $businessFactory;
    protected $maxSubmittedProcesses;
    private $queue = array();
    private $queueIndex = 0;
    private $filesIndicatorsDirectory;

    /**
     * @param BusinessFactory $businessFactory
     * @param $doctrine
     */
    public function __construct(BusinessFactory $businessFactory, $doctrine, $em, $initialMaxSubmittedProcesses = 4) {
        $this->doctrine = $doctrine;
        $this->businessFactory = $businessFactory;
        $this->maxSubmittedProcesses = $initialMaxSubmittedProcesses;
        $this->em = $em;
        $this->filesIndicatorsDirectory = tempnam("/tmp", "processesIndicator");
        unlink($this->filesIndicatorsDirectory);
        mkdir($this->filesIndicatorsDirectory);
    }

    /**
     * Fork process and refresh doctrine connection for both child and father
     * @param null $processName
     * @return int
     * @throws ProcessesSubmissionException
     * @throws \Exception
     */
    public function fork($processName = null) {
        $indicator = $this
            ->businessFactory
            ->get("ProcessesIndicator");
        
        if($processName !== null) {
            $found = true;
            try {
                $indicator->loadByName($processName);
            } catch (ProcessesIndicatorException $e) {
                switch ($e->getId()) {
                    case "not_found":
                        $found = false;
                }
            }
            
            if($found && $indicator->isRunning()) {
                throw new ProcessesSubmissionException("in_use", "A process with that name already running");
            }
        }

        $pid = pcntl_fork();

        $conn = $this->doctrine->getConnection();
        $conn->close();
        $conn->connect();

        if($pid > 0) {
            if($processName === null) {
                $processName = "pid".$pid;
            }
            $indicator->setName($processName);
            $indicator->setPid($pid);
            $indicator->setState(ProcessesIndicator::STATE_PROGRESS);
            $indicator->persist();
            $indicator->writeFileIndicator($this->filesIndicatorsDirectory);

            return $indicator;
        } elseif($pid == -1) {
            throw new ProcessesSubmissionException("cant_fork", "The process can't fork");
        }

        return null;
    }

    /**
     * @return int
     */
    public function getMaxSubmittedProcesses()
    {
        return $this->maxSubmittedProcesses;
    }

    /**
     * @param $max
     * @return $this
     */
    public function setMaxSubmittedProcesses($max)
    {
        $this->maxSubmittedProcesses = $max;

        return $this;
    }

    /**
     * @param Job $job
     * @return $this
     */
    public function addToQueue(Job $job) {
        $this->queue[$this->queueIndex] = $job;
        $this->queueIndex++;

        return $this;
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getNumberOfJobsRunning()
    {
        $indicators = $this
            ->businessFactory
            ->get("ProcessesIndicatorCollection");
        $indicators->addAndBuildFromQuery($indicators->createQueryBuilder("test"), true);
        $numJobsRunning = 0;
        foreach($indicators as $indicator) {
            if($indicator->isRunning()) {
                if($indicator->getStateFromFile($this->filesIndicatorsDirectory) != ProcessesIndicator::STATE_STOPPED) {
                    $numJobsRunning++;
                }
            }
        }

        return $numJobsRunning;
    }

    /**
     * @return $this
     * @throws ProcessesSubmissionException
     */
    public function flushQueue()
    {
        while(count($this->queue)) {
            if($this->getNumberOfJobsRunning() < $this->maxSubmittedProcesses) {
                foreach($this->queue as $key => $job) {
                    array_splice($this->queue, 0, 1);
                    break;
                }

                $indicator = $this->fork($job->getProcessName());
                if ($indicator === null) {
                    $job->doJob();
                    $indicator = $this
                        ->businessFactory
                        ->get("ProcessesIndicator")
                        ->loadByPid(getmypid());
                    $indicator->setState(ProcessesIndicator::STATE_STOPPED);
                    $indicator->persist();
                    $indicator->writeFileIndicator($this->filesIndicatorsDirectory);
                    exit;
                }
                $job->setProcessIndicator($indicator);
                if ($job->getProcessName() === null) {
                    $job->setProcessName($indicator->getName());
                }
            }
        }

        pcntl_wait($status);

        return $this;
    }

    /**
     * @return int
     */
    public function getNumJobsInQueue()
    {
        return count($this->queue);
    }
}