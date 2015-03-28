<?php
	/**
	 * This file is a part of SebkDixheureBundle
	 * Copyright 2014 - Sébastien Kus
	 * Under GNU GPL V3 licence
	 */
	
	namespace Sebk\ProcessesSubmissionBundle\Business;
	
	class BusinessException extends \Exception
	{
		/**
		 * @var string
		 */
		private $id;
		
		/**
		 * constructor
		 * 
		 * @param string $id
		 * @param string $message
		 */
		public function __construct($id, $message)
		{
			parent :: __construct($message);
			$this->id = $id;
		}
		
		/**
		 * Get the id of exception
		 * 
		 * @return string
		 */
		public function getId()
		{
			return $this->id;
		}
	}