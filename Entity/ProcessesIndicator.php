<?php
	// This doctrine entity class has been generated with SebkMysqlToDoctrineBundle
	
	namespace Sebk\ProcessesSubmissionBundle\Entity;
	
	use Doctrine\Common\Collections\ArrayCollection;
	
	class ProcessesIndicator
	/******Begin Custom Extends And Implements*/
	/******End Custom Extends And Implements*/
	{
		// id properties
		protected $id;
		
		// fields properties
		protected $name;
		protected $state;
		protected $pid;
		
		public function __construct()
		{
		}
		
		public function getId()
		{
			return $this->id;
		}
		
		public function getName()
		{
			return $this->name;
		}
		
		public function getState()
		{
			return $this->state;
		}
		
		public function getPid()
		{
			return $this->pid;
		}
		
		public function setName($parm)
		{
			$this->name = $parm;
			return $this;
		}
		
		public function setState($parm)
		{
			$this->state = $parm;
			return $this;
		}
		
		public function setPid($parm)
		{
			$this->pid = $parm;
			return $this;
		}
		
        public function toArray()
        {
            $result = array();
            foreach($this as $property => $value) {
                if(!is_object($value) && $property != "salt" && $property != "password" && $property != "plainPassword"
                    && $property != "confirmationToken") {
                    $result[$property] = $value;
                } elseif(is_object($value) && get_class($value) == "DateTime") {
                    $result[$property] = $value->format("Y-m-d");
                }
            }

            return $result;
        }
    }