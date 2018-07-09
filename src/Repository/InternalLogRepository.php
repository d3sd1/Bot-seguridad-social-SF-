<?php

namespace App\Repository;

use App\Entity\InternalLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method InternalLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method InternalLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method InternalLog[]    findAll()
 * @method InternalLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InternalLogRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, InternalLog::class);
    }

//    /**
//     * @return InternalLog[] Returns an array of InternalLog objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?InternalLog
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
