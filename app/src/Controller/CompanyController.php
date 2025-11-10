<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\CompanyControllerGenerated;
use App\Entity\Company;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Company Controller
 *
 * This controller handles all company operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see CompanyControllerGenerated for available lifecycle hooks
 */
#[Route('/company')]
final class CompanyController extends CompanyControllerGenerated
{
    /**
     * List all nies
     */
    #[Route('', name: 'company_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching nies
     * Used by list page for dynamic data loading
     */
    #[Route('/api/search', name: 'company_api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new company
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'company_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing company
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'company_edit', methods: ['GET', 'POST'])]
    public function edit(Company $company, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($company, $request);
        }

        return $this->editFormAction($company, $request);
    }

    /**
     * Delete company
     */
    #[Route('/{id}', name: 'company_delete', methods: ['POST'])]
    public function delete(Company $company, Request $request): Response
    {
        return $this->deleteAction($company, $request);
    }

    /**
     * Show company details
     */
    #[Route('/{id}', name: 'company_show', methods: ['GET'])]
    public function show(Company $company): Response
    {
        return $this->showAction($company);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Company $company): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($company);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new CompanyCreatedEvent($company));
    // }
    //
    // protected function beforeDelete(Company $company): void
    // {
    //     // Check for dependencies
    //     // if ($company->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete company with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
