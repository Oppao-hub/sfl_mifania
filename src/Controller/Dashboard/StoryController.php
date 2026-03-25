<?php

namespace App\Controller\Dashboard;

use App\Entity\Story;
use App\Form\StoryType;
use App\Repository\StoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted; // <-- Security import

// 1. RBAC FIX: Lock to Staff
#[IsGranted('ROLE_STAFF')]
#[Route('/dashboard/story')]
final class StoryController extends AbstractController
{
    #[Route(name: 'app_story_index', methods: ['GET'])]
    public function index(StoryRepository $storyRepository): Response
    {
        $stories = $storyRepository->findAll();

        if (empty($stories)) {
            $this->addFlash('warning', 'No Story found. Please create one first.');
            return $this->redirectToRoute('app_story_new', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/story/index.html.twig', [
            'stories' => $stories,
        ]);
    }

    #[Route('/new', name: 'app_story_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $story = new Story();
        $form = $this->createForm(StoryType::class, $story);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($story);
            $entityManager->flush();

            // 2. UX FIX: Added missing success flash message
            $this->addFlash('success', 'Story created successfully!');
            return $this->redirectToRoute('app_story_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/story/new.html.twig', [
            'story' => $story,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_story_show', methods: ['GET'])]
    public function show(Story $story): Response
    {
        return $this->render('dashboard/story/show.html.twig', [
            'story' => $story,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_story_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Story $story, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(StoryType::class, $story);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // 3. UX FIX: Added missing success flash message
            $this->addFlash('success', 'Story updated successfully!');
            return $this->redirectToRoute('app_story_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/story/edit.html.twig', [
            'story' => $story,
            'form' => $form,
        ]);
    }

    // 4. RBAC FIX: Only Admins can delete
    #[Route('/{id}', name: 'app_story_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Story $story, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$story->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($story);
            $entityManager->flush();

            // 5. UX FIX: Added missing success flash message
            $this->addFlash('success', 'Story deleted successfully!');
        }

        return $this->redirectToRoute('app_story_index', [], Response::HTTP_SEE_OTHER);
    }
}
