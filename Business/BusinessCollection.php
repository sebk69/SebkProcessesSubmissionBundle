<?php
/**
 * This file is a part of SebkProcessesSubmission
 * Copyright 2015 - Sébastien Kus
 * Under GNU GPL V3 licence
 **/
	
	namespace Sebk\ProcessesSubmissionBundle\Business;
	
	use Doctrine\Common\Collections\ArrayCollection;
	use Doctrine\ORM\EntityManager;
    use Doctrine\ORM\QueryBuilder;

	class BusinessCollection extends ArrayCollection implements \JsonSerializable
	{
		private $className;
		protected $entityManager;
        protected $repository;
        protected $factory;
		
		/**
		 * constructor
		 * @param EntityManager $entityManager
		 * @param BusinessFactory $factory
		 * @param Array $array
		 */
		public function __construct(EntityManager $entityManager, BusinessFactory $factory, Array $array = array())
		{
			$this->entityManager = $entityManager;
            $this->factory = $factory;
            $this->repository = $this->entityManager->getRepository('SebkProcessesSubmissionBundle:'.$this->className);
            parent :: __construct($array);
        }

        /**
         * set class of the collection
         * @param string $className
         * @return $this
         */
        protected function setClass($className)
        {
            $this->className = $className;

            return $this;
        }

        /**
         * create business objects of array of entities and add them to collection
         * @param Array $entities
         * @return $this
         */
        public function addAndBuildFromEntities($entities)
        {
            if(count($entities)) {
                foreach ($entities as $entity) {
                    $this->addAndBuildFromEntity($entity, $this->entityManager);
                }
            }

            return $this;
        }

        /**
         * create business object of an entity and add it to collection
         * @param unknown $entity
         * @return $this
         */
        public function addAndBuildFromEntity($entity)
        {
            $businessObject = $this->factory->get($this->className);
            $businessObject->setEntity($entity);
            $this[] = $businessObject;

            return $this;
        }

        /**
         * Execute query, create business objects and add into collection
         * @param QueryBuilder $query
         * @return BusinessCollection
         */
        public function addAndBuildFromQuery(QueryBuilder $query, $disableCache = false)
        {
            if(!$disableCache) {
                $entities = $query->getQuery()->getResult();
            } else {
                $entities = $query->getQuery()->useResultCache(false)->useQueryCache(false)->getResult();
            }

            return $this->addAndBuildFromEntities($entities);
        }

        /**
         * Get all entities as array
         * @return array|ArrayCollection
         */
        public function getEntities()
        {
            $result = new ArrayCollection();
            foreach($this as $bo) {
                $result[] = $bo->getEntity();
            }

            return $result;
        }

        /**
         * Create all entities into collection
         * @return $this
         */
        public function addAll()
        {
            $entities = $this->entityManager->getRepository("SebkProcessesSubmissionBundle:".$this->className)->findAll();
            $this->addAndBuildFromEntities($entities);

            return $this;
        }

        /**
         * Return array to be serialized to json
         * @return Array
         */
        public function JsonSerialize()
        {
            $result = array();
            foreach($this as $business) {
                if(!$business instanceOf \JsonSerializable) {
                    $result[] = $business->getEntity()->toArray();
                } else {
                    $result[] = $business->JsonSerialize();
                }
            }

            return $result;
        }

        /**
         * Select only page number $age
         * @param int $page
         * @param int $pageSize
         * @param QueryBuilder $query
         * @return QueryBuilder
         */
        public function page($page, $pageSize, QueryBuilder $query = null) {
            if($query === null) {
                $query = $this->query();
            }

            $query->setFirstResult(($page - 1) * $pageSize)
                ->setMaxResults($pageSize);

            return $query;
        }

        /**
         * Call repository
         * @param $name
         * @param $arguments
         * @return mixed
         */
        public function __call($name, $arguments)
        {
            if(method_exists($this->repository, $name)) {
                return call_user_func_array(array($this->repository, $name), $arguments);
            }
        }
    }
