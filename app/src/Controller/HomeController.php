<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\StudentCourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class HomeController extends AbstractController
{
    public function __construct(
        private readonly StudentCourseRepository $studentCourseRepository
    ) {}

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $user = $this->getUser();

        // Guests see the public landing page
        if (!$user) {
            return $this->render('public/landing.html.twig');
        }

        // Authenticated users are redirected to dashboard
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function dashboard(): Response
    {
        $user = $this->getUser();

        // If user is a student, show student courses dashboard
        if ($user && in_array('ROLE_STUDENT', $user->getRoles(), true)) {
            // Get student's active enrolled courses
            $enrollments = $this->studentCourseRepository->findBy(
                ['student' => $user, 'active' => true],
                ['enrolledAt' => 'DESC']
            );

            return $this->render('student/courses.html.twig', [
                'enrollments' => $enrollments,
            ]);
        }

        // Default dashboard for non-students
        return $this->render('home/index.html.twig', [
            'title' => 'Welcome to Luminai',
        ]);
    }
}
