<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\StepQuestion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
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
            ->add('importance', RangeType::class, [
                'label' => 'question.form.importance',
                'attr' => [
                    'class' => 'form-range',
                    'min' => 1,
                    'max' => 10,
                    'step' => 1,
                    'oninput' => 'this.nextElementSibling.value = this.value',
                ],
                'constraints' => [
                    new Assert\Range(min: 1, max: 10),
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
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
