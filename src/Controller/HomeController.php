<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Redirect authenticated users to their appropriate dashboard
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }
        
        if ($this->isGranted('ROLE_DEPARTMENT_HEAD')) {
            return $this->redirectToRoute('department_head_dashboard');
        }
        
        if ($this->isGranted('ROLE_FACULTY')) {
            return $this->redirectToRoute('faculty_dashboard');
        }
        
        // Fallback for other authenticated users
        return $this->render('home/index.html.twig', [
            'page_title' => 'Welcome to Smart Scheduling System'
        ]);
    }

    #[Route('/profile', name: 'app_profile')]
    public function profile(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        return $this->json([
            'user' => [
                'email' => $user?->getUserIdentifier(),
                'roles' => $user?->getRoles(),
                'role_string' => $user?->getRoleString(),
                'role_number' => $user?->getRole(),
            ]
        ]);
    }
}