<?php

declare(strict_types=1);

namespace App\Form\Generator;

use App\Entity\Generator\GeneratorEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class GeneratorEntityFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        // ====================================
        // BASIC INFORMATION
        // ====================================

        $builder
            ->add('entityName', TextType::class, [
                'label' => 'Entity Name (PascalCase)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contact, Product, Invoice...',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Entity name is required'),
                    new Assert\Regex([
                        'pattern' => '/^[A-Z][a-zA-Z0-9]*$/',
                        'message' => 'Entity name must be PascalCase (e.g., Contact, ProductCategory)',
                    ]),
                    new Assert\Length(min: 2, max: 100),
                ],
            ])
            ->add('entityLabel', TextType::class, [
                'label' => 'Entity Label',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contact, Product...',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Entity label is required'),
                    new Assert\Length(min: 2, max: 100),
                ],
            ])
            ->add('pluralLabel', TextType::class, [
                'label' => 'Plural Label',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Contacts, Products...',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Plural label is required'),
                    new Assert\Length(min: 2, max: 100),
                ],
            ])
            ->add('icon', ChoiceType::class, [
                'label' => 'Icon',
                'choices' => [
                    'Person' => 'bi-person',
                    'People' => 'bi-people',
                    'Building' => 'bi-building',
                    'Briefcase' => 'bi-briefcase',
                    'Cart' => 'bi-cart',
                    'Box' => 'bi-box',
                    'Tag' => 'bi-tag',
                    'File' => 'bi-file-text',
                    'Calendar' => 'bi-calendar',
                    'Clock' => 'bi-clock',
                    'Envelope' => 'bi-envelope',
                    'Phone' => 'bi-telephone',
                    'Gear' => 'bi-gear',
                    'Graph' => 'bi-graph-up',
                    'List' => 'bi-list-ul',
                    'Table' => 'bi-table',
                    'Database' => 'bi-database',
                    'Diagram' => 'bi-diagram-3',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Brief description of this entity...',
                ],
                'constraints' => [
                    new Assert\Length(max: 5000),
                ],
            ]);

        // ====================================
        // MULTI-TENANCY
        // ====================================

        $builder->add('hasOrganization', CheckboxType::class, [
            'label' => 'Multi-Tenant (Has Organization)',
            'required' => false,
            'attr' => [
                'class' => 'form-check-input',
            ],
            'help' => 'Check if this entity should be isolated by organization',
        ]);

        // ====================================
        // API CONFIGURATION
        // ====================================

        $builder
            ->add('apiEnabled', CheckboxType::class, [
                'label' => 'Enable API Platform',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('apiOperations', ChoiceType::class, [
                'label' => 'API Operations',
                'required' => false,
                'multiple' => true,
                'choices' => [
                    'GetCollection' => 'GetCollection',
                    'Get' => 'Get',
                    'Post' => 'Post',
                    'Put' => 'Put',
                    'Patch' => 'Patch',
                    'Delete' => 'Delete',
                ],
                'attr' => [
                    'class' => 'form-select',
                    'size' => 6,
                ],
            ])
            ->add('apiSecurity', TextType::class, [
                'label' => 'API Security Expression',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => "is_granted('ROLE_USER')",
                ],
            ])
            ->add('apiPaginationEnabled', CheckboxType::class, [
                'label' => 'Enable Pagination',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('apiItemsPerPage', IntegerType::class, [
                'label' => 'Items Per Page',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 100,
                ],
                'constraints' => [
                    new Assert\Positive(),
                    new Assert\Range(min: 1, max: 100),
                ],
            ]);

        // ====================================
        // SECURITY
        // ====================================

        $builder
            ->add('voterEnabled', CheckboxType::class, [
                'label' => 'Enable Security Voter',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('voterAttributes', ChoiceType::class, [
                'label' => 'Voter Attributes',
                'required' => false,
                'multiple' => true,
                'choices' => [
                    'VIEW' => 'VIEW',
                    'EDIT' => 'EDIT',
                    'DELETE' => 'DELETE',
                    'CREATE' => 'CREATE',
                ],
                'attr' => [
                    'class' => 'form-select',
                    'size' => 4,
                ],
            ]);

        // ====================================
        // NAVIGATION
        // ====================================

        $builder
            ->add('menuGroup', TextType::class, [
                'label' => 'Menu Group',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'CRM, System, Reports...',
                ],
                'constraints' => [
                    new Assert\Length(max: 100),
                ],
            ])
            ->add('menuOrder', IntegerType::class, [
                'label' => 'Menu Order',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                ],
                'constraints' => [
                    new Assert\PositiveOrZero(),
                ],
            ]);

        // ====================================
        // TESTING
        // ====================================

        $builder->add('testEnabled', CheckboxType::class, [
            'label' => 'Enable Test Generation',
            'required' => false,
            'attr' => [
                'class' => 'form-check-input',
            ],
        ]);

        // ====================================
        // SUBMIT BUTTON
        // ====================================

        $builder->add('submit', SubmitType::class, [
            'label' => $isEdit ? 'Update Entity' : 'Create Entity',
            'attr' => [
                'class' => 'btn luminai-btn-primary w-100 mt-3',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GeneratorEntity::class,
            'is_edit' => false,
            'attr' => [
                'novalidate' => 'novalidate',
                'data-controller' => 'form-submit',
                'data-form-submit-loading-class' => 'form-loading',
            ],
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
