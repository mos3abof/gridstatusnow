<?php

namespace PowerGrid\PowerGridBundle\Entity;

use Doctrine\ORM\EntityRepository;

class RecordRepository extends EntityRepository
{
    public function getLatestStatus()
    {
        $QueryBuilder = $this->createQueryBuilder('r');

        $QueryBuilder
            ->setMaxResults(1)
            ->addOrderBy('r.createdAt', 'DESC')
        ;

        return $QueryBuilder;
    }
}
