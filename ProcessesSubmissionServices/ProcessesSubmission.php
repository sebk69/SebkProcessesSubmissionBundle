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
    const FLUSH_MANUALLY = 0;

    private $doctrine;
    protected $maxSubmittedProcesses;
    private static $queue = array();
    private $queueIndex = 0;
    private static $filesIndicatorsDirectory;
    private static $flushOnQueueSize = self::FLUSH_MANUALLY;
    private static $follow = array();

    /**
     * @param BusinessFactory $businessFactory
     * @param int $initialMaxSubmittedProcesses
     */
    public function __construct(BusinessFactory $businessFactory, $doctrine, $initialMaxSubmittedProcesses = 4) {
        $this->businessFactory = $businessFactory;
        $this->doctrine = $doctrine;
        $this->maxSubmittedProcesses = $initialMaxSubmittedProcesses;
        if(self::$filesIndicatorsDirectory === null) {
            self::$filesIndicatorsDirectory = tempnam("/tmp", "processesIndicator");
            unlink(self::$filesIndicatorsDirectory);
            mkdir(self::$filesIndicatorsDirectory);
        }
    }

    /**
     * @param bool $reopen
     * @return $this
     */
    public function closeDoctrineConnections($reopen = false)
    {
        $connections = $this->doctrine->getConnections();
        foreach ($connections as $connection) {
            $connection->close();
            gc_collect_cycles();
            if($reopen) {
                $connection->connect();
            }
        }

        return $this;
    }

    /**
     * Fork process and refresh doctrine connection for both child and father
     * @return int
     * @throws ProcessesSubmissionException
     * @throws \Exception
     */
    public function fork() {
        $pid = pcntl_fork();

        $this->closeDoctrineConnections(true);

        if($pid > 0) {
            $indicator = $this
                ->businessFactory
                ->get("ProcessesIndicator");
            $indicator->setPid($pid);
            $indicator->setState(ProcessesIndicator::STATE_PROGRESS);
            $indicator->setDirectory(self::$filesIndicatorsDirectory);
            $indicator->persist();

            self::$follow[] = $indicator;

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
        self::$queue[$this->queueIndex] = $job;
        $this->queueIndex++;

        if(count(self::$queue) > self::$flushOnQueueSize) {
            $this->flushQueue();
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getNumberOfJobsRunning()
    {
        $numJobsRunning = 0;
        foreach(self::$follow as $key => $indicator) {
            switch($indicator->getState()) {
                case ProcessesIndicator::STATE_PROGRESS:
                    $numJobsRunning++;
                    break;
                case ProcessesIndicator::STATE_STOPPED:
                    array_splice(self::$follow, $key, 1);
                    break;
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
        while($this->getNumJobsInQueue()) {
            if($this->getNumberOfJobsRunning() < $this->maxSubmittedProcesses) {
                foreach(self::$queue as $key => $job) {
                    array_splice(self::$queue, 0, 1);
                    break;
                }

                $indicator = $this->fork();
                if ($indicator === null) {
                    $indicator = $this
                        ->businessFactory
                        ->get("ProcessesIndicator");
                    $indicator->setPid(getmypid());
                    $indicator->setDirectory(self::$filesIndicatorsDirectory);
                    $indicator->waitForFile();
                    $job->doJob();
                    $indicator->setState(ProcessesIndicator::STATE_STOPPED);
                    $indicator->persist(self::$filesIndicatorsDirectory);
                    $this->closeDoctrineConnections();
                    exit;
                }

                $job->setProcessIndicator($indicator);
                pcntl_wait($status, WNOHANG);
            }
        }


        return $this;
    }

    /**
     * @return $this
     */
    public function waitAllFinished()
    {
        while($this->getNumberOfJobsRunning()) {
            sleep(1);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getNumJobsInQueue()
    {
        return count(self::$queue);
    }

    /**
     * @return Job
     */
    public function createJob()
    {
        return $this
            ->businessFactory
            ->get("Job");
    }

    /**
     * @param $queueSize
     * @return $this
     */
    public function setFlushOnQueueSize($queueSize)
    {
        self::$flushOnQueueSize = $queueSize;

        return $this;
    }
}