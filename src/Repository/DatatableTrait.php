<?php

namespace App\Repository;

trait DatatableTrait {
    public function findForDatatables(int $start, int $length, ?string $search, array $searchableColumns): array {
        $qb = $this->createQueryBuilder('e')->setFirstResult($start)->setMaxResults($length);
        if ($search) {
            $orX = $qb->expr()->orX();
            foreach ($searchableColumns as $col) { $orX->add($qb->expr()->like("e.$col", ':s')); }
            $qb->andWhere($orX)->setParameter('s', '%' . $search . '%');
        }
        return $qb->getQuery()->getArrayResult();
    }

    public function countForDatatables(?string $search, array $searchableColumns): int {
        $qb = $this->createQueryBuilder('e')->select('COUNT(e.id)');
        if ($search) {
            $orX = $qb->expr()->orX();
            foreach ($searchableColumns as $col) { $orX->add($qb->expr()->like("e.$col", ':s')); }
            $qb->andWhere($orX)->setParameter('s', '%' . $search . '%');
        }
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
