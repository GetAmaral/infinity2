<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Course;
use App\Repository\StudentCourseRepository;
use App\Service\Utils;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_USER')]
final class CertificateController extends AbstractController
{
    public function __construct(
        private readonly StudentCourseRepository $studentCourseRepository,
        private readonly TranslatorInterface $translator
    ) {}

    /**
     * Display course completion certificate (HTML view for printing)
     */
    #[Route('/course/{id}/certificate', name: 'certificate_course', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
    public function course(Course $course): Response
    {
        $student = $this->getUser();

        if (!$student) {
            throw $this->createAccessDeniedException('You must be logged in to view certificates.');
        }

        // Find student enrollment for this course
        $enrollment = $this->studentCourseRepository->findOneBy([
            'student' => $student,
            'course' => $course,
            'active' => true
        ]);

        if (!$enrollment) {
            throw $this->createAccessDeniedException('You are not enrolled in this course.');
        }

        // Security check: Only allow access if course is completed
        if (!$enrollment->isCompleted()) {
            $this->addFlash('error', 'certificate.error.not_completed');
            return $this->redirectToRoute('student_course', ['id' => $course->getId()->toString()]);
        }

        // Get organization
        $organization = $course->getOrganization();

        return $this->render('course/certificate.html.twig', [
            'course' => $course,
            'enrollment' => $enrollment,
            'student' => $student,
            'organization' => $organization,
            'isPdf' => false,
        ]);
    }

    /**
     * Generate PDF certificate
     */
    #[Route('/course/{id}/certificate/pdf', name: 'certificate_course_pdf', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'], methods: ['GET'])]
    public function coursePdf(Course $course, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        $student = $this->getUser();

        if (!$student) {
            throw $this->createAccessDeniedException('You must be logged in to generate certificates.');
        }

        // Find student enrollment for this course
        $enrollment = $this->studentCourseRepository->findOneBy([
            'student' => $student,
            'course' => $course,
            'active' => true
        ]);

        if (!$enrollment) {
            throw $this->createAccessDeniedException('You are not enrolled in this course.');
        }

        // Security check: Only allow access if course is completed
        if (!$enrollment->isCompleted()) {
            $this->addFlash('error', 'certificate.error.not_completed');
            return $this->redirectToRoute('student_course', ['id' => $course->getId()->toString()]);
        }

        // Get organization
        $organization = $course->getOrganization();

        // Render HTML for PDF using dedicated PDF template
        $html = $this->renderView('course/certificate_pdf.html.twig', [
            'course' => $course,
            'enrollment' => $enrollment,
            'student' => $student,
            'organization' => $organization,
        ]);

        // Debug mode: show HTML instead of PDF
        if ($request->query->get('debug') === '1') {
            return new Response($html);
        }

        // Configure dompdf with minimal options
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        // Initialize dompdf
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->loadHtml($html);
        $dompdf->render();

        // Generate filename: Certificate-Course_Name_Snake-Student_Name_Snake-yyyy-mm-dd-hh-mm-ss
        $certificateWord = $this->translator->trans('certificate.title');
        $filename = sprintf(
            '%s-%s-%s-%s.pdf',
            Utils::stringToSnake($certificateWord, true),
            Utils::stringToSnake($course->getName(), true),
            Utils::stringToSnake($student->getName(), true),
            date('Y-m-d-H-i-s')
        );

        // Return PDF response
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }

}
