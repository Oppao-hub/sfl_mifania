<?php

namespace App\Controller\Dashboard\Admin;

use App\Entity\ActivityLog;
use App\Repository\ActivityLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/dashboard/activity-log')]
class ActivityLogController extends AbstractController
{
    #[Route('', name: 'app_activity_log_index')]
    public function index(ActivityLogRepository $activityLogRepository, Request $request): Response
    {
        $query = $request->query->get('q');

        $logs = $activityLogRepository->searchLogs($query);

        return $this->render('dashboard/activity_log/index.html.twig', [
            'logs' => $logs,
            'searchQuery' => $query,
        ]);
    }

    #[Route('/{id}', name: 'app_activity_log_show')]
    public function show(ActivityLog $log): Response
    {
        return $this->render('dashboard/activity_log/show.html.twig', [
            'log' => $log,
        ]);
    }
}
