<?php

namespace App\Controller\Dashboard;

use App\Repository\CustomerRepository;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[IsGranted('ROLE_STAFF')]
#[Route('/dashboard/analytics')]
final class AnalyticsController extends AbstractController
{
    #[Route('/', name: 'app_analytics')]
    public function index(
        CustomerRepository $customerRepository,
        OrderRepository $orderRepository,
        ChartBuilderInterface $chartBuilder
    ): Response {
        // --- 1. Summary Stats ---
        $totalCustomers = $customerRepository->count([]);
        $newCustomersThisMonth = $customerRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.createdAt >= :start')
            ->setParameter('start', new \DateTimeImmutable('first day of this month midnight'))
            ->getQuery()
            ->getSingleScalarResult();
        
        $totalRewardPoints = $customerRepository->createQueryBuilder('c')
            ->select('SUM(w.rewardPoints)')
            ->innerJoin('c.wallet', 'w')
            ->getQuery()
            ->getSingleScalarResult();

        // --- 2. Chart: Monthly Registrations ---
        $regData = $customerRepository->getMonthlyRegistrations(6);
        $regChart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $regChart->setData([
            'labels' => array_column($regData, 'month'),
            'datasets' => [
                [
                    'label' => 'New Customers',
                    'backgroundColor' => 'rgba(82, 98, 46, 0.1)',
                    'borderColor' => '#52622E',
                    'data' => array_column($regData, 'count'),
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ]);
        $regChart->setOptions([
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1]]]
        ]);

        // --- 3. Chart: City Distribution ---
        $cityData = $customerRepository->getCityDistribution();
        $cityChart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $cityChart->setData([
            'labels' => array_column($cityData, 'label'),
            'datasets' => [
                [
                    'backgroundColor' => ['#52622E', '#6A7B42', '#3F4C23', '#8A7363', '#EAE8E3'],
                    'data' => array_column($cityData, 'value'),
                ],
            ],
        ]);

        // --- 4. Data for Tables ---
        $topSpenders = $orderRepository->getTopSpenders(5);
        $topRewardHolders = $customerRepository->getTopRewardHolders(5);
        $recentCustomers = $customerRepository->findBy([], ['createdAt' => 'DESC'], 5);

        return $this->render('dashboard/analytics/index.html.twig', [
            'stats' => [
                'totalCustomers' => $totalCustomers,
                'newCustomers' => $newCustomersThisMonth,
                'totalPoints' => $totalRewardPoints ?? 0,
            ],
            'regChart' => $regChart,
            'cityChart' => $cityChart,
            'topSpenders' => $topSpenders,
            'topRewardHolders' => $topRewardHolders,
            'recentCustomers' => $recentCustomers,
        ]);
    }
}
