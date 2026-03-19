<?php

namespace App\Controller\Dashboard\Admin;

use App\Entity\ActivityLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dashboard/activity-log')]
class ActivityLogController extends AbstractController
{
    #[Route('', name: 'app_activity_log_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $logs = $entityManager->getRepository(ActivityLog::class)->findBy([], ['createdAt' => 'DESC']);

        return $this->render('dashboard/activity_log/index.html.twig', [
            'logs' => $logs,
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
