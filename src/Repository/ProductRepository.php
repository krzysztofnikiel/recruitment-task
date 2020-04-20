<?php
declare(strict_types=1);

namespace KrzysztofNikiel\Bundle\RecruitmentTaskBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use KrzysztofNikiel\Bundle\RecruitmentTaskBundle\Entity\Product;

/**
 * Class ProductRepository
 * @package KrzysztofNikiel\Bundle\RecruitmentTaskBundle\Repository
 */
class ProductRepository extends ServiceEntityRepository
{
    public const IN_STOCK = 'in-stock';
    public const OUT_STOCK = 'out-stock';
    /**
     * ProductRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return array
     */
    public function findProductByStock($stockType): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        return $qb->select(['p'])
            ->from(Product::class, 'p')
            ->where($this->getComparisionByStockType($qb, $stockType))
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
    /**
     * @return array
     */
    public function findProductMoreThanAmount($amount): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        return $qb->select(['p'])
            ->from(Product::class, 'p')
            ->where($qb->expr()->gt('p.amount', $amount))
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param QueryBuilder $qb
     * @param string $type
     * @return Comparison
     */
    private function getComparisionByStockType(QueryBuilder $qb, string $type): Comparison
    {
        if ($type === self::IN_STOCK) {
            return $qb->expr()->gt('p.amount', 0);
        }

        return $qb->expr()->eq('p.amount', 0);
    }
}