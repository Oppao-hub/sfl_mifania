<?php

namespace App\Controller\Dashboard;

use App\Repository\CustomerRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\Query\Parameter;
use App\Repository\OrderItemRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        OrderItemRepository $orderItemRepository,
        OrderRepository $orderRepository,
        CustomerRepository $customerRepository,
        ProductRepository $productRepository,
        NotificationRepository $notificationRepository,
    ): Response {

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

        $topSellingProducts = $productRepository->findTopSellingProducts(5);

        // Ensure the data structure is correct
        $topProducts = array_map(function ($row) {
            if (is_array($row) && isset($row['name'], $row['unitsSold'], $row['revenue'])) {
                return [
                    'name' => $row['name'],
                    'unitsSold' => $row['unitsSold'],
                    'revenue' => $row['revenue'],
                ];
            }

            return [
                'name' => 'Unknown',
                'unitsSold' => 0,
                'revenue' => 0.0,
            ];
        }, $topSellingProducts);

        $orders = $orderRepository->findAllWithOrderItems();

        //total counts
        $totalCustomers = $customerRepository->count([]);
        $totalProducts = $productRepository->count([]);
        $totalOrders = $orderRepository->count([]);

        return $this->render('dashboard/index.html.twig', [
            'stats' => $stats,
            'orders' => $orders,
            'recentOrders' => $recentOrders,
            'totalCustomers' => $totalCustomers,
            'totalProducts' => $totalProducts,
            'totalOrders' => $totalOrders,
            'topSellingProducts' => $topProducts,
            'totalSales' => $totalSales,
        ]);
    }
}
