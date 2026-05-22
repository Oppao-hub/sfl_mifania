private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (
            !in_array($resourceClass, [Order::class, Cart::class, Redemption::class, Wallet::class, Customer::class]) ||
            $this->security->isGranted('ROLE_ADMIN') ||
            null === $user = $this->security->getUser()
        ) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        if ($resourceClass === Customer::class) {
            $queryBuilder->andWhere(sprintf('%s.user = :current_user', $rootAlias));
            $queryBuilder->setParameter('current_user', $user);
            return;
        }

        // Logic for Order and other entities:
        // We join to the customer and filter, but we MUST handle cases where customer is null
        $queryBuilder->leftJoin(sprintf('%s.customer', $rootAlias), 'customer');

        // This condition now explicitly allows:
        // 1. Orders where the customer is linked to the current user
        // 2. OR orders where the customer is null (if your business logic allows guest orders)
        // Adjust the 'or' condition based on whether guests should see their orders!
        $queryBuilder->andWhere(
            $queryBuilder->expr()->orX(
                'customer.user = :current_user',
                'customer.id IS NULL' // Keeps orders that don't have a customer assigned
            )
        );

        $queryBuilder->setParameter('current_user', $user);
    }
