<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Organization;
use App\Repository\OrganizationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrganizationController extends AbstractController
{
    #[Route('/organization', name: 'organization_index')]
    public function index(OrganizationRepository $repository): Response
    {
        return $this->render('organization/index.html.twig', [
            'organizations' => $repository->findAll(),
        ]);
    }

    #[Route('/organization/{id}', name: 'organization_show', requirements: ['id' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'])]
    public function show(Organization $organization): Response
    {
        return $this->render('organization/show.html.twig', [
            'organization' => $organization,
        ]);
    }
}