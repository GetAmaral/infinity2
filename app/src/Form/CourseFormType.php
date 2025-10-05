<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Course;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CourseFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'course.form.name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'course.form.name_placeholder',
                    'data-live-name-value' => 'name',
                    'data-action' => 'live#update',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'course.validation.name_required'),
                    new Assert\Length(
                        min: 2,
                        max: 255,
                        minMessage: 'course.validation.name_min_length',
                        maxMessage: 'course.validation.name_max_length'
                    ),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'common.form.description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'course.form.description_placeholder',
                    'rows' => 4,
                    'data-live-name-value' => 'description',
                    'data-action' => 'live#update',
                ],
                'constraints' => [
                    new Assert\Length(
                        max: 5000,
                        maxMessage: 'course.validation.description_max_length'
                    ),
                ],
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'course.form.active',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                    'data-live-name-value' => 'active',
                    'data-action' => 'live#update',
                ],
            ])
            ->add('releaseDate', DateTimeType::class, [
                'label' => 'course.form.release_date',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'data-live-name-value' => 'releaseDate',
                    'data-action' => 'live#update',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => $isEdit ? 'button.update_course' : 'button.create_course',
                'attr' => [
                    'class' => 'btn luminai-btn-primary w-100',
                    'data-loading-text' => $isEdit ? 'button.updating' : 'button.creating',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
            'is_edit' => false,
            'attr' => [
                'novalidate' => 'novalidate',
                'data-controller' => 'form-submit course-form',
                'data-form-submit-loading-class' => 'form-loading',
            ],
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
