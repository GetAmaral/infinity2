<?php

declare(strict_types=1);

namespace App\Form\Generator;

use App\Entity\Generator\GeneratorProperty;
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

class GeneratorPropertyFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        // ====================================
        // BASIC INFORMATION
        // ====================================

        $builder
            ->add('propertyName', TextType::class, [
                'label' => 'Property Name (camelCase)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'emailAddress, phoneNumber, createdAt...',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Property name is required'),
                    new Assert\Regex([
                        'pattern' => '/^[a-z][a-zA-Z0-9]*$/',
                        'message' => 'Property name must be camelCase (e.g., emailAddress, phoneNumber)',
                    ]),
                    new Assert\Length(min: 2, max: 100),
                ],
            ])
            ->add('propertyLabel', TextType::class, [
                'label' => 'Property Label',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Email Address, Phone Number...',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Property label is required'),
                    new Assert\Length(min: 2, max: 100),
                ],
            ])
            ->add('propertyType', ChoiceType::class, [
                'label' => 'Property Type',
                'choices' => [
                    'String' => 'string',
                    'Text (long string)' => 'text',
                    'Integer' => 'integer',
                    'Float' => 'float',
                    'Decimal' => 'decimal',
                    'Boolean' => 'boolean',
                    'Date' => 'date',
                    'DateTime' => 'datetime',
                    'DateTime Immutable' => 'datetime_immutable',
                    'JSON' => 'json',
                    'Array' => 'array',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('propertyOrder', IntegerType::class, [
                'label' => 'Display Order',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                ],
                'constraints' => [
                    new Assert\PositiveOrZero(),
                ],
            ]);

        // ====================================
        // DATABASE CONFIGURATION
        // ====================================

        $builder
            ->add('nullable', CheckboxType::class, [
                'label' => 'Nullable',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('unique', CheckboxType::class, [
                'label' => 'Unique',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('length', IntegerType::class, [
                'label' => 'Length',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '255',
                ],
                'help' => 'For string/text types',
                'constraints' => [
                    new Assert\Positive(),
                ],
            ])
            ->add('precision', IntegerType::class, [
                'label' => 'Precision',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '10',
                ],
                'help' => 'For decimal types',
                'constraints' => [
                    new Assert\Positive(),
                ],
            ])
            ->add('scale', IntegerType::class, [
                'label' => 'Scale',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '2',
                ],
                'help' => 'For decimal types',
                'constraints' => [
                    new Assert\PositiveOrZero(),
                ],
            ])
            ->add('defaultValue', TextType::class, [
                'label' => 'Default Value',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Default value...',
                ],
            ]);

        // ====================================
        // RELATIONSHIPS
        // ====================================

        $builder
            ->add('relationshipType', ChoiceType::class, [
                'label' => 'Relationship Type',
                'required' => false,
                'choices' => [
                    'None' => null,
                    'ManyToOne' => 'ManyToOne',
                    'OneToMany' => 'OneToMany',
                    'ManyToMany' => 'ManyToMany',
                    'OneToOne' => 'OneToOne',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
                'placeholder' => 'Select relationship type...',
            ])
            ->add('targetEntity', TextType::class, [
                'label' => 'Target Entity',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'User, Product, Category...',
                ],
                'help' => 'Entity name this property relates to',
            ])
            ->add('inversedBy', TextType::class, [
                'label' => 'Inversed By',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'contacts, products...',
                ],
            ])
            ->add('mappedBy', TextType::class, [
                'label' => 'Mapped By',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'owner, category...',
                ],
            ])
            ->add('orphanRemoval', CheckboxType::class, [
                'label' => 'Orphan Removal',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('fetch', ChoiceType::class, [
                'label' => 'Fetch Strategy',
                'required' => false,
                'choices' => [
                    'LAZY' => 'LAZY',
                    'EAGER' => 'EAGER',
                    'EXTRA_LAZY' => 'EXTRA_LAZY',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ]);

        // ====================================
        // FORM CONFIGURATION
        // ====================================

        $builder
            ->add('formType', ChoiceType::class, [
                'label' => 'Form Type',
                'required' => false,
                'choices' => [
                    'TextType' => 'TextType',
                    'TextareaType' => 'TextareaType',
                    'EmailType' => 'EmailType',
                    'IntegerType' => 'IntegerType',
                    'NumberType' => 'NumberType',
                    'MoneyType' => 'MoneyType',
                    'PercentType' => 'PercentType',
                    'DateType' => 'DateType',
                    'DateTimeType' => 'DateTimeType',
                    'CheckboxType' => 'CheckboxType',
                    'ChoiceType' => 'ChoiceType',
                    'EntityType' => 'EntityType',
                    'FileType' => 'FileType',
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
                'placeholder' => 'Auto-detect from property type',
            ])
            ->add('formRequired', CheckboxType::class, [
                'label' => 'Required in Form',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('formReadOnly', CheckboxType::class, [
                'label' => 'Read Only in Form',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('formHelp', TextareaType::class, [
                'label' => 'Form Help Text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                ],
            ]);

        // ====================================
        // UI DISPLAY
        // ====================================

        $builder
            ->add('showInList', CheckboxType::class, [
                'label' => 'Show in List View',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('showInDetail', CheckboxType::class, [
                'label' => 'Show in Detail View',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('showInForm', CheckboxType::class, [
                'label' => 'Show in Form',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('sortable', CheckboxType::class, [
                'label' => 'Sortable',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('searchable', CheckboxType::class, [
                'label' => 'Searchable',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('filterable', CheckboxType::class, [
                'label' => 'Filterable',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ]);

        // ====================================
        // API CONFIGURATION
        // ====================================

        $builder
            ->add('apiReadable', CheckboxType::class, [
                'label' => 'API Readable',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('apiWritable', CheckboxType::class, [
                'label' => 'API Writable',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ]);

        // ====================================
        // SUBMIT BUTTON
        // ====================================

        $builder->add('submit', SubmitType::class, [
            'label' => $isEdit ? 'Update Property' : 'Create Property',
            'attr' => [
                'class' => 'btn luminai-btn-primary w-100 mt-3',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GeneratorProperty::class,
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
