<?php

namespace App\Controller\Admin;

use App\Repository\CustomerRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\Query\Parameter;
use App\Repository\OrderItemRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin')]
    public function index(
        OrderItemRepository $orderItemRepository,
        OrderRepository $orderRepository,
        CustomerRepository $customerRepository,
        ProductRepository $productRepository,
        NotificationRepository $notificationRepository,
        Security $security
    ): Response {
        $user = $security->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // --- Previous changes for consistency ---
        $recentOrders = $orderRepository->findBy([], ['createdAt' => 'DESC'], 5);

        // --- All-Time Stats ---
        $totalSales = $orderRepository->createQueryBuilder('o')
            ->select('SUM(o.totalAmount)')
            ->getQuery()
            ->getSingleScalarResult();

        // --- Monthly Percentage Change Calculations ---
        $startOfCurrentMonth = new \DateTime('first day of this month midnight');
        $startOfLastMonth = new \DateTime('first day of last month midnight');
        $endOfLastMonth = new \DateTime('last day of last month 23:59:59');
        $parameters = new \Doctrine\Common\Collections\ArrayCollection([
            new Parameter('start', $startOfLastMonth),
            new Parameter('end', $endOfLastMonth),
        ]);

        $calculatePercentageChange = function ($current, $previous) {
            if ($previous == 0) {
                return $current > 0 ? 100.0 : 0.0;
            }
            return (($current - $previous) / $previous) * 100;
        };

        // Sales Change
        $salesLastMonth = $orderRepository->createQueryBuilder('o')
            ->select('SUM(o.totalAmount)')
            ->where('o.createdAt BETWEEN :start AND :end')
            ->setParameters($parameters)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $salesCurrentMonth = $orderRepository->createQueryBuilder('o')
            ->select('SUM(o.totalAmount)')
            ->where('o.createdAt >= :start')
            ->setParameter('start', $startOfCurrentMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $salesChange = $calculatePercentageChange($salesCurrentMonth, $salesLastMonth);

        // Orders Change
        $ordersLastMonth = $orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.createdAt BETWEEN :start AND :end')
            ->setParameters($parameters)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        $ordersCurrentMonth = $orderRepository->createQueryBuilder('o')

            ->select('COUNT(o.id)')
            ->where('o.createdAt >= :start')
            ->setParameter('start', $startOfCurrentMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        $ordersChange = $calculatePercentageChange($ordersCurrentMonth, $ordersLastMonth);

        // Customers Change
        $customersLastMonth = $customerRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.createdAt BETWEEN :start AND :end')
            ->setParameters($parameters)->getQuery()->getSingleScalarResult() ?? 0;

        $customersCurrentMonth = $customerRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.createdAt >= :start')
            ->setParameter('start', $startOfCurrentMonth)
            ->getQuery()->getSingleScalarResult() ?? 0;

        $customersChange = $calculatePercentageChange($customersCurrentMonth, $customersLastMonth);

        // Products Change
        $productsLastMonth = $productRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.createdAt BETWEEN :start AND :end')
            ->setParameters($parameters)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $productsCurrentMonth = $productRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.createdAt >= :start')
            ->setParameter('start', $startOfCurrentMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $productsChange = $calculatePercentageChange($productsCurrentMonth, $productsLastMonth);

        $stats = [
            'total_sales' => $totalSales ?? 0,
            'total_orders' => $orderRepository->count([]),
            'total_customers' => $customerRepository->count([]),
            'total_products' => $productRepository->count([]),
            'changes' => [
                'sales' => $salesChange,
                'orders' => $ordersChange,
                'customers' => $customersChange,
                'products' => $productsChange,
            ]
        ];

        $topSellingProducts = $orderItemRepository->findTopSelling(5);

        $topProducts = [];
        foreach ($topSellingProducts as $row) {
            $topProducts[] = [
                'name' => $row['name'],
                'unitsSold' => $row['unitsSold'],
                'revenue' => $row['revenue'],
            ];
        }

        $orders = $orderRepository->findAll();

        $user = $this->getUser();
        $unreadCount = 0;
        $recentNotifications = [];
        if ($user) {
            $unreadCount = $notificationRepository->countUnread($user);
            $recentNotifications = $notificationRepository->findRecent($user);
        }

        return $this->render('admin/index.html.twig', [
            'stats' => $stats,
            'orders' => $orders,
            'recentOrders' => $recentOrders,
            'unreadCount' => $unreadCount,
            'recentNotifications' => $recentNotifications,
            'topProducts' => $topProducts
        ]);
    }
}
