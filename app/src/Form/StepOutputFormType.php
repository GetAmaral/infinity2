<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Step;
use App\Entity\StepOutput;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class StepOutputFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];
        $availableSteps = $options['available_steps'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'output.form.name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'output.form.name_placeholder',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'common.form.description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'output.form.description_placeholder',
                ],
            ])
            ->add('conditional', TextareaType::class, [
                'label' => 'output.form.conditional',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'output.form.conditional_placeholder',
                ],
                'help' => 'output.form.conditional_help',
            ])
            ->add('destinationStep', EntityType::class, [
                'class' => Step::class,
                'choices' => $availableSteps,
                'choice_label' => 'name',
                'label' => 'output.form.destination',
                'required' => false,
                'placeholder' => 'output.form.destination_placeholder',
                'attr' => [
                    'class' => 'form-select',
                ],
                'help' => 'output.form.destination_help',
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
            'data_class' => StepOutput::class,
            'is_edit' => false,
            'available_steps' => [],
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
        $resolver->setAllowedTypes('available_steps', 'iterable');
    }
}
