<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Step;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class StepFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'step.form.name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'step.form.name_placeholder',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('first', CheckboxType::class, [
                'label' => 'step.form.first',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'help' => 'step.form.first_help',
            ])
            ->add('objective', TextareaType::class, [
                'label' => 'step.form.objective',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'step.form.objective_placeholder',
                ],
            ])
            ->add('prompt', TextareaType::class, [
                'label' => 'step.form.prompt',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'step.form.prompt_placeholder',
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
            'data_class' => Step::class,
            'is_edit' => false,
            'translation_domain' => 'treeflow',
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
