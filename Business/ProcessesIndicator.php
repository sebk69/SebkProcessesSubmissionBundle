<?php
/**
 * This file is a part of SebkProcessesSubmission
 * Copyright 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 **/
	
	namespace Sebk\ProcessesSubmissionBundle\Business;
	
	use Sebk\ProcessesSubmissionBundle\Entity\ProcessesIndicator as ProcessesIndicatorEntity;
	use Sebk\ProcessesSubmissionBundle\Business\ProcessesIndicatorException;
	use Sebk\ProcessesSubmissionBundle\Entity\ProcessesIndicatorRepository;
	use Doctrine\ORM\EntityManager;
    use Symfony\Component\DependencyInjection\Container;
	/******Begin Custom Uses*/
	/******End Custom Uses*/
	
	/**
	 * @method unkown getName()
	 * @method setName(unknown $Name)
	 * @method unkown getState()
	 * @method setState(unknown $State)
	 * @method unkown getPid()
	 * @method setPid(unknown $Pid)
	 */
	class ProcessesIndicator implements BusinessObjectInterface
    /******Begin Custom Extends And Implements*/
    /******End Custom Extends And Implements*/
	{
		/**
		 * @var EntityManager
		 */
		private $entityManager;
		
		/**
		 * @var Container
		 */
		private $container;

		/**
		 * @var BusinessFactory
		 */
		private $factory;

		/**
		 * @var ProcessesIndicatorEntity
		 */
		private $entity;
		
		/**
		 * @var ProcessesIndicatorRepository
		 */
		protected $repository;
		
		/******Begin Custom Properties*/
        const STATE_PROGRESS = "progress";
        const STATE_STOPPED = "stopped";
		/******End Custom Properties*/
		
		/**
		 * ProcessesIndicator constructor
		 * @param EntityManager $em
		 * @param Container $container
		 * @param BusinessFactory $factory
		 */
		public function __construct(EntityManager $em, Container $container, BusinessFactory $factory)
		{
			$this->entityManager = $em;
            $this->container = $container;
			$this->repository = $em->getRepository("SebkProcessesSubmissionBundle:ProcessesIndicator");
			$this->entity = new ProcessesIndicatorEntity;
            $this->factory = $factory;
		}
		
		/**
		 * Magic method for implement getters and setters of entity
		 * @param string $method
		 * @param string $arguments
		 * @return mixed
		 */
		public function __call($method, $arguments)
		{
            if(substr($method, 0, 3) == "get" || substr($method, 0, 3) == "set" || $method == "toArray")
			{
				if(method_exists($this->getEntity(), $method))
					return call_user_func_array(array($this->getEntity(), $method), $arguments);
			}
			
			trigger_error("Method '$method' does not exists in ProcessesIndicator entity (Raised in business object __call)", E_USER_ERROR);
		}
		
		/**
		 * get entity property
		 * @return ProcessesIndicatorEntity
		 */
		public function getEntity()
		{
            if(@$this->entity === null) {
                $this->entity = new ProcessesIndicatorEntity;
            }
			return $this->entity;
		}
		
		/**
		 * set entity property
		 * @param ProcessesIndicatorEntity $entity
		 * @param boolean $force
         * @return $this
		 */
		public function setEntity($entity, $force = false)
		{
			$this->entity = $entity;
			
			return $this;
		}
		
		/**
		 * persist object and dependencies
		 */
		public function persist()
		{
			$this->entityManager->persist($this->entity);
            $this->entityManager->flush();
		}
		
		/**
		 * Remove entity from db
		 */
		public function remove()
		{
            $reference = $this->entityManager->getReference('SebkProcessesSubmissionBundle:ProcessesIndicator', array('id' => $this->getId(), ));
            $this->entityManager->remove($reference);
            $this->entityManager->flush();
		}

        public function dump() {
            \Doctrine\Common\Util\Debug::dump($this);
        }

        /**
         * Free object memory
         */
        public function free() {
            $this->entityManager->detach($this->entity);
            unset($this->entity);
            unset($this);
        }
		/******Begin Custom Methods*/
        /**
         * @param $name
         * @return $this
         * @throws IndicatorException
         * @throws \Exception
         */
        public function loadByName($name)
        {
            $entities = $this->factory->get("IndicatorCollection");
            $entities->addAndBuildFromQuery($entities->listForName($name));
            if(count($entities) > 1) {
                throw new IndicatorException("name_not_unique", "The name of indicator must be unique ($name).");
            } elseif(count($entities) == 0) {
                throw new IndicatorException("not_found", "The indicator was not found.");
            } else {
                $this->entity = $entities[0]->entity;
            }

            return $this;
        }

        /**
         * @param $name
         * @return $this
         * @throws IndicatorException
         * @throws \Exception
         */
        public function loadByPid($pid)
        {
            $entity = $this->repository->findOneBy(array("pid" => $pid));
            $this->setEntity($entity);

            return $this;
        }

        /**
         * Check if process is running
         * @return bool
         */
        public function isRunning()
        {
            // if entity is not loaded return false
            if ($this->entity === null) {
                return false;
            }

            // check if process is running, else remove from database and return false
            $process = $this->container->get("sebk_dixheure.process");
            $process->setPid($this->getPid());
            if (!$process->isRunning() || $this->entity->getState() == static::STATE_STOPPED) {
                if($this->getId() != null) {
                    $this->remove();
                }
                return false;
            }

            // process is running
            return true;
        }

        /**
         * Return indicator state
         * @return string
         */
        public function getState()
        {
            // If indicator correspond to a process
            if ($this->getPid() !== null) {
                // and is not running : return null
                if (!$this->isRunning()) {
                    return null;
                }
            }

            return $this->entity->getState();
        }

        public function getStateFromFile($directory)
        {
            if(!$this->getPid()) {
                throw new ProcessesIndicatorException("pid_not_defined", "The pid of the process is not defined : can't read corresponding file indicator");
            }
            return file_get_contents($directory."/".$this->getPid());
        }

        public function writeFileIndicator($directory)
        {
            if(!$this->getPid()) {
                throw new ProcessesIndicatorException("pid_not_defined", "The pid of the process is not defined : can't write corresponding file indicator");
            }
            $f = fopen($directory."/".$this->getPid(), "w");
            $state = $this->getState();
            fwrite($f, $state===null?static::STATE_STOPPED:$state);
            fclose($f);
        }
		/******End Custom Methods*/
	}