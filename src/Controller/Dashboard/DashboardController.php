<?php

namespace App\Controller\Dashboard;

use App\Repository\CustomerRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        OrderRepository $orderRepository,
        CustomerRepository $customerRepository,
        ProductRepository $productRepository,
        ChartBuilderInterface $chartBuilder
    ): Response {

        // --- 1. CORE STATS ---
        $recentOrders  = $orderRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $orders        = $orderRepository->findAllWithOrderItems();

        $totalOrders   = $orderRepository->count([]);
        $totalCustomers = $customerRepository->count([]);
        $totalProducts = $productRepository->count([]);

        $totalSales = $orderRepository->createQueryBuilder('o')
            ->select('SUM(o.totalAmount)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // --- 2. MONTHLY PERCENTAGE CALCULATIONS ---
        $startOfCurrentMonth = new \DateTime('first day of this month midnight');
        $startOfLastMonth    = new \DateTime('first day of last month midnight');
        $endOfLastMonth      = new \DateTime('last day of last month 23:59:59');

        $parameters = new ArrayCollection([
            new Parameter('start', $startOfLastMonth),
            new Parameter('end', $endOfLastMonth),
        ]);

        $calcChange = function ($current, $previous) {
            if ($previous == 0) return $current > 0 ? 100.0 : 0.0;
            return (($current - $previous) / $previous) * 100;
        };

        // Sales Change
        $salesLastMonth = $orderRepository->createQueryBuilder('o')->select('SUM(o.totalAmount)')
            ->where('o.createdAt BETWEEN :start AND :end')->setParameters($parameters)->getQuery()->getSingleScalarResult() ?? 0;
        $salesCurrentMonth = $orderRepository->createQueryBuilder('o')->select('SUM(o.totalAmount)')
            ->where('o.createdAt >= :start')->setParameter('start', $startOfCurrentMonth)->getQuery()->getSingleScalarResult() ?? 0;
        $salesChange = $calcChange($salesCurrentMonth, $salesLastMonth);

        // Orders Change
        $ordersLastMonth = $orderRepository->createQueryBuilder('o')->select('COUNT(o.id)')
            ->where('o.createdAt BETWEEN :start AND :end')->setParameters($parameters)->getQuery()->getSingleScalarResult() ?? 0;
        $ordersCurrentMonth = $orderRepository->createQueryBuilder('o')->select('COUNT(o.id)')
            ->where('o.createdAt >= :start')->setParameter('start', $startOfCurrentMonth)->getQuery()->getSingleScalarResult() ?? 0;
        $ordersChange = $calcChange($ordersCurrentMonth, $ordersLastMonth);

        // Customers Change
        $customersLastMonth = $customerRepository->createQueryBuilder('c')->select('COUNT(c.id)')
            ->where('c.createdAt BETWEEN :start AND :end')->setParameters($parameters)->getQuery()->getSingleScalarResult() ?? 0;
        $customersCurrentMonth = $customerRepository->createQueryBuilder('c')->select('COUNT(c.id)')
            ->where('c.createdAt >= :start')->setParameter('start', $startOfCurrentMonth)->getQuery()->getSingleScalarResult() ?? 0;
        $customersChange = $calcChange($customersCurrentMonth, $customersLastMonth);

        // Products Change
        $productsLastMonth = $productRepository->createQueryBuilder('p')->select('COUNT(p.id)')
            ->where('p.createdAt BETWEEN :start AND :end')->setParameters($parameters)->getQuery()->getSingleScalarResult() ?? 0;
        $productsCurrentMonth = $productRepository->createQueryBuilder('p')->select('COUNT(p.id)')
            ->where('p.createdAt >= :start')->setParameter('start', $startOfCurrentMonth)->getQuery()->getSingleScalarResult() ?? 0;
        $productsChange = $calcChange($productsCurrentMonth, $productsLastMonth);

        $stats = [
            'total_sales'     => $totalSales,
            'total_orders'    => $totalOrders,
            'total_customers' => $totalCustomers,
            'total_products'  => $totalProducts,
            'changes'         => [
                'sales'     => $salesChange,
                'orders'    => $ordersChange,
                'customers' => $customersChange,
                'products'  => $productsChange,
            ]
        ];

        // --- 3. TOP SELLING PRODUCTS ---
        $topSellingProducts = $productRepository->findTopSellingProducts(5);
        $topProducts = array_map(function ($row) {
            return [
                'name'      => $row['name'] ?? 'Unknown',
                'unitsSold' => $row['unitsSold'] ?? 0,
                'revenue'   => $row['revenue'] ?? 0.0,
            ];
        }, $topSellingProducts);


        // --- 4. CHART.JS GENERATION ---
        // 1. Fetch the real dynamic data from the database
        $monthlySalesData = $orderRepository->getMonthlySalesData(6);

        // 2. Split the data into two flat arrays for Chart.js
        $chartLabels = array_column($monthlySalesData, 'month');
        $chartValues = array_column($monthlySalesData, 'total');

       // 3. Build the Chart
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => $chartLabels, // Inject dynamic labels
            'datasets' => [
                [
                    'label'           => 'Monthly Sales (₱)',
                    'backgroundColor' => 'rgba(82, 98, 46, 0.1)',
                    'borderColor'     => '#52622E',
                    'data'            => $chartValues, // Inject dynamic totals
                    'tension'         => 0.4,
                    'fill'            => true,
                ],
            ],
        ]);

        $chart->setOptions([
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    // Optional: Add a peso sign to the Y-axis labels
                    'ticks' => [
                        'callback' => 'function(value) { return "₱" + value; }'
                    ]
                ],
            ],
        ]);

        // --- 5. RENDER TEMPLATE ---
        return $this->render('dashboard/index.html.twig', [
            'stats'              => $stats,
            'orders'             => $orders,
            'recentOrders'       => $recentOrders,
            'totalCustomers'     => $totalCustomers,
            'totalProducts'      => $totalProducts,
            'totalOrders'        => $totalOrders,
            'totalSales'         => $totalSales,
            'topSellingProducts' => $topProducts,
            'chart'              => $chart,
        ]);
    }

    #[Route('/dashboard/search', name: 'app_global_search', methods: ['GET'])]
    public function search(
        Request $request,
        ProductRepository $productRepo,
        OrderRepository $orderRepo,
        CustomerRepository $customerRepo
    ): Response {
        $query = $request->query->get('q', '');

        $products = [];
        $orders = [];
        $customers = [];

        if (!empty(trim($query))) {
            $products  = $productRepo->searchByTerm($query);
            $orders    = $orderRepo->searchByTerm($query);
            $customers = $customerRepo->searchByTerm($query);
        }

        return $this->render('dashboard/search_results.html.twig', [
            'query'     => $query,
            'products'  => $products,
            'orders'    => $orders,
            'customers' => $customers,
        ]);
    }
}
