<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\ContactControllerGenerated;
use App\Entity\Contact;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contact Controller
 *
 * This controller handles all contact operations.
 * Custom business logic can be added by overriding lifecycle hooks.
 *
 * @see ContactControllerGenerated for available lifecycle hooks
 */
#[Route('/contact')]
final class ContactController extends ContactControllerGenerated
{
    /**
     * List all contacts
     */
    #[Route('', name: 'contact_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->indexAction();
    }

    /**
     * API endpoint for searching contacts
     * Used by list page for dynamic data loading
     */
    #[Route('/search', name: 'contact_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        return $this->apiSearchAction($request);
    }

    /**
     * Create new contact
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/new', name: 'contact_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->createAction($request);
        }

        return $this->newFormAction($request);
    }

    /**
     * Edit existing contact
     * Handles both GET (show form) and POST (process form)
     */
    #[Route('/{id}/edit', name: 'contact_edit', methods: ['GET', 'POST'])]
    public function edit(Contact $contact, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            return $this->updateAction($contact, $request);
        }

        return $this->editFormAction($contact, $request);
    }

    /**
     * Delete contact
     */
    #[Route('/{id}', name: 'contact_delete', methods: ['POST'])]
    public function delete(Contact $contact, Request $request): Response
    {
        return $this->deleteAction($contact, $request);
    }

    /**
     * Show contact details
     */
    #[Route('/{id}', name: 'contact_show', methods: ['GET'])]
    public function show(Contact $contact): Response
    {
        return $this->showAction($contact);
    }

    // ====================================
    // CUSTOM METHODS & LIFECYCLE HOOKS
    // ====================================

    // Example: Override lifecycle hooks for custom logic
    //
    // protected function afterCreate(Contact $contact): void
    // {
    //     // Send notification email
    //     // $this->emailService->sendCreationNotification($contact);
    //
    //     // Trigger domain event
    //     // $this->eventDispatcher->dispatch(new ContactCreatedEvent($contact));
    // }
    //
    // protected function beforeDelete(Contact $contact): void
    // {
    //     // Check for dependencies
    //     // if ($contact->hasRelatedRecords()) {
    //     //     throw new \RuntimeException('Cannot delete contact with related records');
    //     // }
    // }

    // Add your custom controller methods here
}
