<?php
/**
 * This file is a part of SebkProcessesSubmission
 * Copyright 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 **/

	namespace Sebk\ProcessesSubmissionBundle\Business;
	 

    use Doctrine\ORM\EntityManager;
    use Symfony\Component\DependencyInjection\Container;

    class BusinessFactory {
        private $entityManager;
        private $container;

        /**
         * Service constructor
         * @param EntityManager $entityManager
         * @param Container $container         */
        public function __construct(EntityManager $entityManager, Container $container) {
            $this->entityManager = $entityManager;
            $this->container = $container;
        }

        /**
         * get business class
         * @param $className
         * @param Array $array
         * @return mixed
         * @throws \Exception
        */
        public function get($className, $array = array()) {
            $className = 'Sebk\ProcessesSubmissionBundle\Business\\'.$className;
            if(!class_exists($className, true)) {
                throw new \Exception("The class '$className' is not exists (generated in BusinessFactory)");
            }

            if(strstr($className, "Collection") === false && count($array)) {
                throw new \Exception("The class '$className' is not a collection. You can't pass array in parameter. (generated in BusinessFactory)");
            } elseif(strstr($className, "Collection") === false) {
                return new $className($this->entityManager, $this->container, $this);
            } else {
                return new $className($this->entityManager, $this, $array);
            }
        }
    }