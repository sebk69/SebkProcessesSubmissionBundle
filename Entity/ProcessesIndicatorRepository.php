<?php
	// This doctrine repository class has been generated with SebkMysqlToDoctrineBundle
	namespace Sebk\ProcessesSubmissionBundle\Entity;
	
	use Doctrine\ORM\EntityRepository;
	
	class ProcessesIndicatorRepository extends EntityRepository
	{
        /**
         * List indicators for a name
         * @param $name
         * @param QueryBuilder $query
         * @return QueryBuilder
         */
        public function listForName($name, QueryBuilder $query = null)
        {
            if($query === null) {
                $query = $this->createQueryBuilder("processesIndicator");
            }

            $query->where("processesIndicator.name = :name")
                ->setParameter("name", $name);

            return $query;
        }
	}