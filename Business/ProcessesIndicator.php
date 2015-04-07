<?php
/**
 * This file is a part of SebkProcessesSubmission
 * Copyright 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 **/
	
	namespace Sebk\ProcessesSubmissionBundle\Business;
	
	use Sebk\ProcessesSubmissionBundle\Business\ProcessesIndicatorException;
    use Symfony\Component\DependencyInjection\Container;

	class ProcessesIndicator
	{
		private $container;
        private $pid;
        private $state;
        private $directory;

		/******Begin Custom Properties*/
        const STATE_PENDING = "pending";
        const STATE_PROGRESS = "progress";
        const STATE_STOPPED = "stopped";
		/******End Custom Properties*/
		
		/**
		 * ProcessesIndicator constructor
		 * @param Container $container
		 */
		public function __construct(Container $container)
		{
            $this->container = $container;
            $this->state = self::STATE_PENDING;
		}

        /**
         * @return mixed
         */
        public function getDirectory()
        {
            return $this->directory;
        }

        /**
         * @param $directory
         * @return $this
         * @throws ProcessesIndicatorException
         */
        public function setDirectory($directory)
        {
            if(!is_dir($directory)) {
                throw new ProcessesIndicatorException("not_exists", "Directory no exists");
            }

            $this->directory = $directory;

            return $this;
        }

        /**
         * @return mixed
         */
        public function getPid()
        {
            return $this->pid;
        }

        /**
         * @param $pid
         * @return $this
         */
        public function setPid($pid)
        {
            $this->pid = $pid;

            return $this;
        }

        /**
         * @return mixed
         */
        public function getState()
        {
            if($this->state == self::STATE_PROGRESS) {
                if (!file_exists($this->getFilename())) {
                    $this->state = self::STATE_STOPPED;
                    return self::STATE_STOPPED;
                } else {
                    $process = $this->container->get("sebk_processes_submition_bundle_process");
                    $process->setPid($this->pid);
                    if (!$process->isRunning()) {
                        $this->state = self::STATE_STOPPED;
                        unlink($this->getFilename());
                        return self::STATE_STOPPED;
                    }
                }
            }

            return $this->state;
        }

        /**
         * @param $state
         * @return $this
         */
        public function setState($state)
        {
            $this->state = $state;

            return $this;
        }

        public function getFilename()
        {
            if($this->pid === null) {
                throw new ProcessesIndicatorException("pid_not_set", "The pid of the process has not been set");
            }

            return $this->directory."/".$this->pid;
        }
		
		/**
		 * persist object and dependencies
		 */
		public function persist()
		{
            switch($this->state) {
                case self::STATE_PROGRESS:
                    $f = fopen($this->getFilename(), "w");
                    fwrite($f, $this->state);
                    fclose($f);
                    break;

                default:
                    if(file_exists($this->getFilename())) {
                        unlink($this->getFilename());
                    }
            }
		}

        public function waitForFile()
        {
            while(!@fopen($this->getFilename(), "r"));
        }
		/******End Custom Methods*/
	}