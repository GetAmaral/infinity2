<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\PipelineStage;
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
        $pipelineId = $options['pipeline_id'];

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
            ->add('condition', TextareaType::class, [
                'label' => 'output.form.condition',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'output.form.condition_placeholder',
                ],
                'help' => 'output.form.condition_help',
            ])
            ->add('pipelineStage', EntityType::class, [
                'label' => 'Pipeline Stage',
                'required' => false,
                'class' => PipelineStage::class,
                'query_builder' => function (\Doctrine\ORM\EntityRepository $er) use ($pipelineId) {
                    $qb = $er->createQueryBuilder('e');

                    // Filter by pipeline if provided
                    if ($pipelineId) {
                        $qb->where('e.pipeline = :pipeline_id')
                           ->setParameter('pipeline_id', $pipelineId);
                    }

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
                    'data-relation-select-entity-value' => 'PipelineStage',
                    'data-relation-select-route-value' => 'pipelinestage_api_search',
                    'data-relation-select-add-route-value' => 'pipelinestage_new_modal',
                    'data-relation-select-multiple-value' => 'false',
                    'data-relation-select-one-to-one-value' => 'false',
                    'placeholder' => 'Select pipeline stage',
                    'class' => 'form-input-modern',
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
            'data_class' => StepOutput::class,
            'is_edit' => false,
            'available_steps' => [],
            'pipeline_id' => null,
            'translation_domain' => 'treeflow',
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
        $resolver->setAllowedTypes('available_steps', 'array');
        $resolver->setAllowedTypes('pipeline_id', ['null', 'string']);
    }
}
