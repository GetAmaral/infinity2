<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\CourseLecture;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Form\Type\VichFileType;

class CourseLectureFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'course.lecture.form.name',
                'translation_domain' => 'course',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'course.lecture.form.name_placeholder',
                    'data-live-name-value' => 'name',
                    'data-action' => 'live#update',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'course.lecture.validation.name_required'),
                    new Assert\Length(
                        min: 2,
                        max: 255,
                        minMessage: 'course.lecture.validation.name_min_length',
                        maxMessage: 'course.lecture.validation.name_max_length'
                    ),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'common.form.description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'course.lecture.form.description_placeholder',
                    'rows' => 4,
                    'data-live-name-value' => 'description',
                    'data-action' => 'live#update',
                ],
                'constraints' => [
                    new Assert\Length(
                        max: 5000,
                        maxMessage: 'course.lecture.validation.description_max_length'
                    ),
                ],
            ])
            ->add('videoFile', VichFileType::class, [
                'label' => 'course.lecture.form.video_file',
                'translation_domain' => 'course',
                'required' => false, // Video is optional - some lectures can be text-only
                'allow_delete' => false,
                'download_uri' => false,
                'attr' => [
                    'class' => 'form-input-modern',
                    'accept' => 'video/mp4,video/webm,video/ogg,video/quicktime,video/mp2t,video/x-matroska,.ts,.mp4,.webm,.ogg,.mov,.avi,.mkv',
                ],
                'constraints' => [
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
                ],
            ])
            ->add('viewOrder', IntegerType::class, [
                'label' => 'course.lecture.form.view_order',
                'translation_domain' => 'course',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'course.lecture.form.view_order_placeholder',
                    'min' => 0,
                    'data-live-name-value' => 'viewOrder',
                    'data-action' => 'live#update',
                ],
                'constraints' => [
                    new Assert\PositiveOrZero(message: 'course.lecture.validation.view_order_positive'),
                ],
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'common.form.active',
                'required' => false,
                'data' => true, // Default to true
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('free', CheckboxType::class, [
                'label' => 'course.lecture.form.free',
                'translation_domain' => 'course',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'help' => 'course.lecture.form.free_help',
            ])
            ->add('submit', SubmitType::class, [
                'label' => $isEdit ? 'button.update_lecture' : 'button.create_lecture',
                'attr' => [
                    'class' => 'btn luminai-btn-primary w-100',
                    'data-loading-text' => $isEdit ? 'button.updating' : 'button.creating',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CourseLecture::class,
            'is_edit' => false,
            'attr' => [
                'novalidate' => 'novalidate',
                'data-form-submit-loading-class' => 'form-loading',
            ],
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
