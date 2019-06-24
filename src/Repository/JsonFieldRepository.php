<?php

namespace App\Repository;

use App\Entity\JsonField;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method JsonField|null find($id, $lockMode = null, $lockVersion = null)
 * @method JsonField|null findOneBy(array $criteria, array $orderBy = null)
 * @method JsonField[]    findAll()
 * @method JsonField[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JsonFieldRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, JsonField::class);
    }

    // /**
    //  * @return JsonField[] Returns an array of JsonField objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('j.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?JsonField
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
