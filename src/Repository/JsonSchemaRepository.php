<?php

namespace App\Repository;

use App\Entity\JsonSchema;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class JsonSchemaRepository.
 */
class JsonSchemaRepository extends ServiceEntityRepository
{
    /**
     * JsonSchemaRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, JsonSchema::class);
    }
}
