<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Entity\Organization;
use App\Form\OrganizationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class OrganizationFormComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?Organization $initialFormData = null;

    #[LiveProp]
    public bool $isEdit = false;

    #[LiveProp]
    public bool $isSaved = false;

    #[LiveProp]
    public array $validationErrors = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            OrganizationFormType::class,
            $this->initialFormData,
            ['is_edit' => $this->isEdit]
        );
    }

    #[LiveAction]
    public function save()
    {
        $this->submitForm();

        /** @var Organization $organization */
        $organization = $this->getForm()->getData();

        if (!$this->getForm()->isValid()) {
            $this->validationErrors = $this->getFormErrors($this->getForm());
            return;
        }

        try {
            if (!$this->isEdit) {
                $this->entityManager->persist($organization);
            }

            $this->entityManager->flush();

            $this->isSaved = true;
            $this->validationErrors = [];

            // Emit a browser event for the parent to handle
            $this->emit('organization:saved', [
                'organization' => [
                    'id' => $organization->getId()?->toString() ?? '',
                    'name' => $organization->getName(),
                    'description' => $organization->getDescription(),
                    'isNew' => !$this->isEdit,
                ],
            ]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'organization.flash.save_error');
            $this->validationErrors = ['general' => [$e->getMessage()]];
        }
    }

    #[LiveAction]
    public function reset()
    {
        $this->isSaved = false;
        $this->validationErrors = [];
        $this->resetForm();
    }

    public function hasValidationErrors(): bool
    {
        return !empty($this->validationErrors);
    }

    public function getValidationError(string $field): ?string
    {
        return $this->validationErrors[$field][0] ?? null;
    }

    private function getFormErrors(FormInterface $form): array
    {
        $errors = [];

        // Get form-level errors
        foreach ($form->getErrors() as $error) {
            $errors['general'][] = $error->getMessage();
        }

        // Get field-level errors
        foreach ($form->all() as $fieldName => $field) {
            foreach ($field->getErrors() as $error) {
                $errors[$fieldName][] = $error->getMessage();
            }
        }

        return $errors;
    }
}