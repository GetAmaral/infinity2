<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\TreeFlow;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TreeFlowFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'treeflow.form.name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'treeflow.form.name_placeholder',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 2, max: 255),
                ],
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'treeflow.form.active',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'help' => 'treeflow.form.active_help',
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
            'data_class' => TreeFlow::class,
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
