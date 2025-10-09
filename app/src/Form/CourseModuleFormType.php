<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\CourseModule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CourseModuleFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'course.module.form.name',
                'translation_domain' => 'course',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'course.module.form.name_placeholder',
                    'data-live-name-value' => 'name',
                    'data-action' => 'live#update',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'course.module.validation.name_required'),
                    new Assert\Length(
                        min: 2,
                        max: 255,
                        minMessage: 'course.module.validation.name_min_length',
                        maxMessage: 'course.module.validation.name_max_length'
                    ),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'course.module.form.description',
                'translation_domain' => 'course',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'course.module.form.description_placeholder',
                    'rows' => 4,
                    'data-live-name-value' => 'description',
                    'data-action' => 'live#update',
                ],
                'constraints' => [
                    new Assert\Length(
                        max: 5000,
                        maxMessage: 'course.module.validation.description_max_length'
                    ),
                ],
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'course.module.form.active',
                'translation_domain' => 'course',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                    'data-live-name-value' => 'active',
                    'data-action' => 'live#update',
                ],
            ])
            ->add('releaseDate', DateTimeType::class, [
                'label' => 'course.module.form.release_date',
                'translation_domain' => 'course',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'data-live-name-value' => 'releaseDate',
                    'data-action' => 'live#update',
                ],
            ])
            ->add('viewOrder', IntegerType::class, [
                'label' => 'course.module.form.view_order',
                'translation_domain' => 'course',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'data-live-name-value' => 'viewOrder',
                    'data-action' => 'live#update',
                ],
                'constraints' => [
                    new Assert\PositiveOrZero(message: 'course.module.validation.view_order_positive'),
                ],
            ]);

        // Add bulk video upload field only when editing
        if ($isEdit) {
            $builder->add('bulkVideoFiles', FileType::class, [
                'label' => 'course.module.form.bulk_video_upload',
                'translation_domain' => 'course',
                'required' => false,
                'multiple' => true,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'video/mp4,video/webm,video/ogg,video/quicktime,video/mp2t,video/x-matroska,.ts,.mp4,.webm,.ogg,.mov,.avi,.mkv',
                ],
                'constraints' => [
                    new Assert\Count(
                        max: 50,
                        maxMessage: 'course.module.validation.bulk_videos_max_count'
                    ),
                    new Assert\All([
                        new Assert\File([
                            'maxSize' => '4G',
                            'mimeTypes' => [
                                'video/mp4',
                                'video/webm',
                                'video/ogg',
                                'video/quicktime',
                                'video/x-msvideo',
                                'video/mp2t',
                                'video/MP2T',
                                'video/x-matroska',
                                'application/octet-stream',
                            ],
                            'mimeTypesMessage' => 'course.lecture.validation.invalid_video_format',
                        ]),
                    ]),
                ],
            ]);
        }

        $builder->add('submit', SubmitType::class, [
            'label' => $isEdit ? 'button.update_module' : 'button.create_module',
            'attr' => [
                'class' => 'btn luminai-btn-primary w-100',
                'data-loading-text' => $isEdit ? 'button.updating' : 'button.creating',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CourseModule::class,
            'is_edit' => false,
            'attr' => [
                'novalidate' => 'novalidate',
                'data-controller' => 'form-submit course-module-form',
                'data-form-submit-loading-class' => 'form-loading',
            ],
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
