<?php

namespace App\Controller\Admin;

use App\Entity\ActivityLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/activity-log')]
class ActivityLogController extends AbstractController
{
    #[Route('/', name: 'app_admin_activity_log_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $logs = $entityManager->getRepository(ActivityLog::class)->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/activity_log/index.html.twig', [
            'logs' => $logs,
        ]);
    }
}
