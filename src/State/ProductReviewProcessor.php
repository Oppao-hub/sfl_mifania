<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Enum\OrderStatus;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\ProductReview;
use App\Entity\User;
use App\Repository\ProductReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class ProductReviewProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private EntityManagerInterface $entityManager,
        private ProductReviewRepository $reviewRepository,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof ProductReview) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        /** @var User|null $user */
        $user = $this->security->getUser();
        $customer = $user?->getCustomer();
        if (!$customer) {
            throw new BadRequestHttpException('Authenticated user must be a customer.');
        }

        $order = $data->getOrder();
        $product = $data->getProduct();
        $rating = $data->getRating();

        if (!$order instanceof Order || !$product instanceof Product) {
            throw new BadRequestHttpException('Order and product are required.');
        }

        if ($rating === null || $rating < 1 || $rating > 5) {
            throw new BadRequestHttpException('Rating must be between 1 and 5.');
        }

        $order = $this->entityManager->find(Order::class, $order->getId());
        if (!$order) {
            throw new BadRequestHttpException('Order not found.');
        }

        if ($order->getCustomer()?->getId() !== $customer->getId()) {
            throw new AccessDeniedHttpException('You can only review your own orders.');
        }

        if ($order->getOrderStatus() !== OrderStatus::DELIVERED) {
            throw new BadRequestHttpException('You can only review delivered orders.');
        }

        if (!$this->orderContainsProduct($order, $product)) {
            throw new BadRequestHttpException('This product is not part of the selected order.');
        }

        $product = $this->entityManager->find(Product::class, $product->getId());
        if (!$product) {
            throw new BadRequestHttpException('Product not found.');
        }

        $existing = $this->reviewRepository->findOneForCustomerOrderProduct($customer, $order, $product);
        if ($existing) {
            $existing->setRating($rating);
            $existing->setComment($data->getComment() !== null ? trim($data->getComment()) : null);
            $existing->setReviewerName($this->formatReviewerName($customer->getFirstName(), $customer->getLastName()));
            $existing->touchUpdatedAt();

            return $this->persistProcessor->process($existing, $operation, $uriVariables, $context);
        }

        $data->setCustomer($customer);
        $data->setOrder($order);
        $data->setProduct($product);
        $data->setReviewerName($this->formatReviewerName($customer->getFirstName(), $customer->getLastName()));
        $data->setComment($data->getComment() !== null ? trim($data->getComment()) : null);

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    private function orderContainsProduct(Order $order, Product $product): bool
    {
        foreach ($order->getOrderItems() as $item) {
            if (!$item instanceof OrderItem) {
                continue;
            }
            if ($item->getProduct()?->getId() === $product->getId()) {
                return true;
            }
        }

        return false;
    }

    private function formatReviewerName(?string $firstName, ?string $lastName): string
    {
        $first = trim((string) $firstName);
        $lastInitial = $lastName ? strtoupper(substr(trim($lastName), 0, 1)) : '';

        if ($first === '') {
            return 'Mifania Shopper';
        }

        return $lastInitial !== '' ? sprintf('%s %s.', $first, $lastInitial) : $first;
    }
}
