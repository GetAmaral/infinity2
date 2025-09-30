<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Organization;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class OrganizationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'organization.form.name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'organization.form.name_placeholder',
                    'data-live-name-value' => 'name',
                    'data-action' => 'live#update',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'organization.validation.name_required'),
                    new Assert\Length(
                        min: 2,
                        max: 255,
                        minMessage: 'organization.validation.name_min_length',
                        maxMessage: 'organization.validation.name_max_length'
                    ),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'organization.form.description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'organization.form.description_placeholder',
                    'rows' => 4,
                    'data-live-name-value' => 'description',
                    'data-action' => 'live#update',
                ],
                'constraints' => [
                    new Assert\Length(
                        max: 1000,
                        maxMessage: 'organization.validation.description_max_length'
                    ),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => $isEdit ? 'button.update_organization' : 'button.create_organization',
                'attr' => [
                    'class' => 'btn infinity-btn-primary w-100',
                    'data-loading-text' => $isEdit ? 'button.updating' : 'button.creating',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Organization::class,
            'is_edit' => false,
            'attr' => [
                'novalidate' => 'novalidate', // We handle validation with live components
                'data-controller' => 'form-submit',
                'data-form-submit-loading-class' => 'form-loading',
            ],
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}