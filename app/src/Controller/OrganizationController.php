<?php

namespace App\Controller;

use App\Entity\Organization;
use App\Repository\OrganizationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/organization')]
class OrganizationController extends AbstractController
{
    #[Route('/', name: 'organization_index')]
    public function index(OrganizationRepository $repository): Response
    {
        return $this->render('organization/index.html.twig', [
            'organizations' => $repository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'organization_show')]
    public function show(Organization $organization): Response
    {
        return $this->render('organization/show.html.twig', [
            'organization' => $organization,
        ]);
    }
}