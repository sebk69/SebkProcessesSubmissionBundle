<?php 
/**
 * This file is a part of SebkProcessesSubmission
 * Copyright 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 **/

	namespace Sebk\ProcessesSubmissionBundle\Business;
	
	interface BusinessObjectInterface
	{
		public function setEntity($entity, $force);
		public function persist();
	}