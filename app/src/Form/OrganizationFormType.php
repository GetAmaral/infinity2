<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Organization;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
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
            ->add('slug', TextType::class, [
                'label' => 'organization.form.slug',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'organization.form.slug_placeholder',
                    'pattern' => '[a-z0-9]+(?:-[a-z0-9]+)*',
                    'data-live-name-value' => 'slug',
                    'data-action' => 'live#update',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'organization.validation.slug_required'),
                    new Assert\Length(
                        min: 2,
                        max: 255,
                        minMessage: 'organization.validation.slug_min_length',
                        maxMessage: 'organization.validation.slug_max_length'
                    ),
                    new Assert\Regex(
                        pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                        message: 'organization.validation.slug_format'
                    ),
                ],
                'help' => 'organization.form.slug_help',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'common.form.description',
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
            ->add('logoFileLight', FileType::class, [
                'label' => 'organization.form.logo_light',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/png,image/jpeg,image/jpg,image/svg+xml',
                ],
                'constraints' => [
                    new Assert\File(
                        maxSize: '2M',
                        mimeTypes: ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml'],
                        mimeTypesMessage: 'organization.validation.logo_mime_type'
                    ),
                ],
                'help' => 'organization.form.logo_light_help',
            ])
            ->add('logoFileDark', FileType::class, [
                'label' => 'organization.form.logo_dark',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/png,image/jpeg,image/jpg,image/svg+xml',
                ],
                'constraints' => [
                    new Assert\File(
                        maxSize: '2M',
                        mimeTypes: ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml'],
                        mimeTypesMessage: 'organization.validation.logo_mime_type'
                    ),
                ],
                'help' => 'organization.form.logo_dark_help',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'organization.form.is_active',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'label_attr' => [
                    'class' => 'form-check-label',
                ],
                'help' => 'organization.form.is_active_help',
            ])
            ->add('submit', SubmitType::class, [
                'label' => $isEdit ? 'button.update_organization' : 'button.create_organization',
                'attr' => [
                    'class' => 'btn luminai-btn-primary w-100',
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
