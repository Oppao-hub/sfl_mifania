<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\ProductReview;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Product-filtered review lists are public; order/customer-scoped lists require ownership.
 */
final readonly class ProductReviewCollectionExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private Security $security,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if ($resourceClass !== ProductReview::class) {
            return;
        }

        $filters = $context['filters'] ?? [];
        if (isset($filters['product'])) {
            return;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $user = $this->security->getUser();

        if (!$user instanceof User || null === $customer = $user->getCustomer()) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $queryBuilder->andWhere(sprintf('%s.customer = :review_customer', $rootAlias));
        $queryBuilder->setParameter('review_customer', $customer);
    }
}
