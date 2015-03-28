<?php 
/**
 * This file is a part of SebkProcessesSubmission
 * Copyright 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 **/
	
	namespace Sebk\ProcessesSubmissionBundle\Business;
	
	use Sebk\ProcessesSubmissionBundle\Business\BusinessCollection;
	use Doctrine\Common\Collections\ArrayCollection;
	use Doctrine\ORM\EntityManager;
	
	class ProcessesIndicatorCollection extends BusinessCollection
	{
		public function __Construct(EntityManager $entityManager, BusinessFactory $factory, Array $array = array())
		{
			$this->setClass("ProcessesIndicator");
			parent :: __construct($entityManager, $factory, $array);
		}

		/******Begin Custom Methods*/
		/******End Custom Methods*/
	}