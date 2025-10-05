<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Step;
use App\Entity\StepInput;
use App\Enum\InputType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class StepInputFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];
        $availableSteps = $options['available_steps'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'input.form.name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'input.form.name_placeholder',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'input.form.type',
                'choices' => [
                    'input.type.fully_completed' => InputType::FULLY_COMPLETED,
                    'input.type.not_completed_after_attempts' => InputType::NOT_COMPLETED_AFTER_ATTEMPTS,
                    'input.type.any' => InputType::ANY,
                ],
                'expanded' => true, // Radio buttons
                'attr' => [
                    'class' => 'form-check',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('sourceStep', EntityType::class, [
                'class' => Step::class,
                'choices' => $availableSteps,
                'choice_label' => 'name',
                'label' => 'input.form.source',
                'required' => false,
                'placeholder' => 'input.form.source_placeholder',
                'attr' => [
                    'class' => 'form-select',
                ],
                'help' => 'input.form.source_help',
            ])
            ->add('prompt', TextareaType::class, [
                'label' => 'input.form.prompt',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'input.form.prompt_placeholder',
                ],
                'help' => 'input.form.prompt_help',
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
            'data_class' => StepInput::class,
            'is_edit' => false,
            'available_steps' => [],
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
        $resolver->setAllowedTypes('available_steps', 'iterable');
    }
}
