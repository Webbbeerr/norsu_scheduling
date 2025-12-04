<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/faculty', name: 'faculty_')]
#[IsGranted('ROLE_FACULTY')]
class FacultyController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        return $this->render('faculty/dashboard.html.twig', [
            'page_title' => 'Faculty Dashboard',
        ]);
    }

    #[Route('/schedule', name: 'schedule')]
    public function schedule(): Response
    {
        return $this->render('faculty/schedule.html.twig', [
            'page_title' => 'My Teaching Schedule',
        ]);
    }

    #[Route('/office-hours', name: 'office_hours')]
    public function officeHours(): Response
    {
        return $this->render('faculty/office_hours.html.twig', [
            'page_title' => 'Office Hours',
        ]);
    }

    #[Route('/classes', name: 'classes')]
    public function classes(): Response
    {
        return $this->render('faculty/classes.html.twig', [
            'page_title' => 'My Classes',
        ]);
    }

    #[Route('/performance', name: 'performance')]
    public function performance(): Response
    {
        return $this->render('faculty/performance.html.twig', [
            'page_title' => 'My Performance',
        ]);
    }

    #[Route('/profile', name: 'profile')]
    public function profile(): Response
    {
        return $this->render('faculty/profile.html.twig', [
            'page_title' => 'Profile & Settings',
        ]);
    }
}