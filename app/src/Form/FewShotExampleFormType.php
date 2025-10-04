<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\FewShotExample;
use App\Enum\FewShotType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class FewShotExampleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'fewshot.form.type',
                'choices' => [
                    'fewshot.type.positive' => FewShotType::POSITIVE,
                    'fewshot.type.negative' => FewShotType::NEGATIVE,
                ],
                'expanded' => true, // Radio buttons
                'attr' => [
                    'class' => 'form-check',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'fewshot.form.name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'fewshot.form.name_placeholder',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('prompt', TextareaType::class, [
                'label' => 'fewshot.form.prompt',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'fewshot.form.prompt_placeholder',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'common.form.description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'fewshot.form.description_placeholder',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => $isEdit ? 'button.update' : 'button.create',
                'attr' => [
                    'class' => 'btn infinity-btn-primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FewShotExample::class,
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
