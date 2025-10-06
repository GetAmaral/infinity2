<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\StepQuestion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class StepQuestionFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'question.form.name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'question.form.name_placeholder',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('prompt', TextareaType::class, [
                'label' => 'question.form.prompt',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'question.form.prompt_placeholder',
                ],
            ])
            ->add('objective', TextareaType::class, [
                'label' => 'question.form.objective',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'question.form.objective_placeholder',
                ],
            ])
            ->add('importance', ChoiceType::class, array_merge([
                'label' => 'question.form.importance',
                'choices' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                ],
                'expanded' => true,
                'required' => false,
                'attr' => [
                    'class' => 'star-rating-group',
                ],
                'constraints' => [
                    new Assert\Range(min: 1, max: 3),
                ],
            ], $isEdit ? [] : ['data' => 1])) // Default to 1 star when creating
            ->add('fewShotPositive', CollectionType::class, [
                'label' => 'question.form.fewshot.positive',
                'entry_type' => TextareaType::class,
                'entry_options' => [
                    'label' => false,
                    'attr' => [
                        'class' => 'form-input-modern fewshot-entry',
                        'rows' => 2,
                        'placeholder' => 'question.form.fewshot.positive.placeholder',
                    ],
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'required' => false,
                'attr' => [
                    'class' => 'fewshot-collection',
                ],
            ])
            ->add('fewShotNegative', CollectionType::class, [
                'label' => 'question.form.fewshot.negative',
                'entry_type' => TextareaType::class,
                'entry_options' => [
                    'label' => false,
                    'attr' => [
                        'class' => 'form-input-modern fewshot-entry',
                        'rows' => 2,
                        'placeholder' => 'question.form.fewshot.negative.placeholder',
                    ],
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'required' => false,
                'attr' => [
                    'class' => 'fewshot-collection',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => $isEdit ? 'button.update' : 'button.create',
                'attr' => [
                    'class' => 'btn luminai-btn-primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StepQuestion::class,
            'is_edit' => false,
            'translation_domain' => 'treeflow',
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
