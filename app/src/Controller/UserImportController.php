<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\OrganizationRepository;
use App\Security\Voter\UserVoter;
use App\Service\OrganizationContext;
use App\Service\UserImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RequestStack;

#[Route('/user/import')]
final class UserImportController extends AbstractController
{
    public function __construct(
        private readonly UserImportService $userImportService,
        private readonly OrganizationContext $organizationContext,
        private readonly OrganizationRepository $organizationRepository,
        private readonly RequestStack $requestStack
    ) {
    }

    private function getSession()
    {
        return $this->requestStack->getSession();
    }

    /**
     * Upload XLSX file for user import
     */
    #[Route('/upload', name: 'user_import_upload', methods: ['GET', 'POST'])]
    public function upload(Request $request): Response
    {
        // Check permission - only ADMIN, SUPER_ADMIN, and ORGANIZATION_ADMIN can import
        $this->denyAccessUnlessGranted(UserVoter::CREATE);

        // Get active organization
        $organization = null;
        if ($this->organizationContext->hasActiveOrganization()) {
            $orgId = $this->organizationContext->getOrganizationId();
            if ($orgId) {
                $organization = $this->organizationRepository->find($orgId);
            }
        }

        if ($request->isMethod('POST')) {
            /** @var UploadedFile|null $file */
            $file = $request->files->get('xlsx_file');

            if ($file === null) {
                $this->addFlash('error', 'Please select an XLSX file to upload.');
                return $this->redirectToRoute('user_import_upload');
            }

            // Validate file extension
            $extension = $file->getClientOriginalExtension();
            if (!in_array(strtolower($extension), ['xlsx', 'xls'])) {
                $this->addFlash('error', 'Invalid file type. Please upload an XLSX or XLS file.');
                return $this->redirectToRoute('user_import_upload');
            }

            // Validate file size (max 5MB)
            if ($file->getSize() > 5 * 1024 * 1024) {
                $this->addFlash('error', 'File size exceeds 5MB limit.');
                return $this->redirectToRoute('user_import_upload');
            }

            // Move file to temporary location
            $tempPath = sys_get_temp_dir() . '/user_import_' . uniqid() . '.' . $extension;
            $file->move(dirname($tempPath), basename($tempPath));

            try {
                // Parse XLSX file
                $result = $this->userImportService->parseXlsx($tempPath, $organization);

                // Store results in session for review step
                $this->getSession()->set('user_import_data', [
                    'users' => $result['users'],
                    'errors' => $result['errors'],
                    'file_path' => $tempPath,
                    'organization_id' => $organization?->getId()?->toString(),
                ]);

                return $this->redirectToRoute('user_import_review');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error parsing file: ' . $e->getMessage());
                return $this->redirectToRoute('user_import_upload');
            }
        }

        return $this->render('user_import/upload.html.twig', [
            'organization' => $organization,
        ]);
    }

    /**
     * Review parsed users before import
     */
    #[Route('/review', name: 'user_import_review', methods: ['GET'])]
    public function review(): Response
    {
        $this->denyAccessUnlessGranted(UserVoter::CREATE);

        $importData = $this->getSession()->get('user_import_data');

        if ($importData === null) {
            $this->addFlash('error', 'No import data found. Please upload a file first.');
            return $this->redirectToRoute('user_import_upload');
        }

        $organization = null;
        if (isset($importData['organization_id'])) {
            $organization = $this->organizationRepository->find($importData['organization_id']);
        }

        return $this->render('user_import/review.html.twig', [
            'users' => $importData['users'],
            'errors' => $importData['errors'],
            'organization' => $organization,
            'total_users' => count($importData['users']),
            'total_errors' => count($importData['errors']),
        ]);
    }

    /**
     * Confirm and execute import
     */
    #[Route('/confirm', name: 'user_import_confirm', methods: ['POST'])]
    public function confirm(): Response
    {
        $this->denyAccessUnlessGranted(UserVoter::CREATE);

        $importData = $this->getSession()->get('user_import_data');

        if ($importData === null) {
            $this->addFlash('error', 'No import data found. Please upload a file first.');
            return $this->redirectToRoute('user_import_upload');
        }

        $organization = null;
        if (isset($importData['organization_id'])) {
            $organization = $this->organizationRepository->find($importData['organization_id']);
        }

        if ($organization === null) {
            $this->addFlash('error', 'Organization not found.');
            return $this->redirectToRoute('user_import_upload');
        }

        try {
            $result = $this->userImportService->importUsers(
                $importData['users'],
                $organization,
                $this->getUser()
            );

            // Clean up temp file
            if (isset($importData['file_path']) && file_exists($importData['file_path'])) {
                unlink($importData['file_path']);
            }

            // Clear session data
            $this->getSession()->remove('user_import_data');

            // Show success message
            $importedCount = count($result['imported']);
            $failedCount = count($result['failed']);

            if ($importedCount > 0) {
                $this->addFlash('success', "{$importedCount} user(s) imported successfully.");
            }

            if ($failedCount > 0) {
                $this->addFlash('warning', "{$failedCount} user(s) failed to import.");
                // Store failed imports in session for display
                $this->getSession()->set('user_import_failed', $result['failed']);
            }

            return $this->redirectToRoute('user_index');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error importing users: ' . $e->getMessage());
            return $this->redirectToRoute('user_import_review');
        }
    }

    /**
     * Cancel import and clean up
     */
    #[Route('/cancel', name: 'user_import_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        $importData = $this->getSession()->get('user_import_data');

        // Clean up temp file
        if ($importData && isset($importData['file_path']) && file_exists($importData['file_path'])) {
            unlink($importData['file_path']);
        }

        // Clear session data
        $this->getSession()->remove('user_import_data');

        $this->addFlash('info', 'Import cancelled.');
        return $this->redirectToRoute('user_index');
    }
}
