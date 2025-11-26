<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Pipeline;
use App\Entity\TreeFlow;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
            ->add('pipeline', EntityType::class, [
                'label' => 'Pipeline',
                'required' => false,
                'class' => Pipeline::class,
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) {
                    $qb = $er->createQueryBuilder('e');

                    // Determine best field to sort by
                    $metadata = $er->getClassMetadata();
                    $sortField = 'id';
                    foreach (['name', 'title', 'label', 'slug', 'subject'] as $field) {
                        if ($metadata->hasField($field)) {
                            $sortField = $field;
                            break;
                        }
                    }

                    // Use LOWER() for case-insensitive sorting (skip for UUID id fields)
                    if ($sortField === 'id') {
                        return $qb->orderBy('e.id', 'ASC');
                    }
                    return $qb->orderBy('LOWER(e.' . $sortField . ')', 'ASC');
                },
                'attr' => [
                    'data-controller' => 'relation-select',
                    'data-relation-select-entity-value' => 'Pipeline',
                    'data-relation-select-route-value' => 'pipeline_api_search',
                    'data-relation-select-add-route-value' => 'pipeline_new_modal',
                    'data-relation-select-multiple-value' => 'false',
                    'data-relation-select-one-to-one-value' => 'false',
                    'placeholder' => 'Select pipeline',
                    'class' => 'form-input-modern',
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
                    'class' => 'btn luminai-btn-primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TreeFlow::class,
            'is_edit' => false,
            'translation_domain' => 'treeflow',
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
