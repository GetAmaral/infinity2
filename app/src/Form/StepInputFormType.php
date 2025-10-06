<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\StepInput;
use App\Enum\InputType;
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
                    'input.type.fully.completed' => InputType::FULLY_COMPLETED,
                    'input.type.not.completed.after.attempts' => InputType::NOT_COMPLETED_AFTER_ATTEMPTS,
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
            'translation_domain' => 'treeflow',
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
