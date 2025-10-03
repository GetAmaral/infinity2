<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Course;
use App\Entity\CourseLecture;
use App\Entity\CourseModule;
use App\Entity\Organization;
use App\Entity\StudentCourse;
use App\Entity\StudentLecture;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form for searching and filtering audit logs
 */
class AuditSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('entityClass', ChoiceType::class, [
                'label' => 'Entity Type',
                'choices' => [
                    'All Entities' => '',
                    'User' => User::class,
                    'Organization' => Organization::class,
                    'Course' => Course::class,
                    'Course Module' => CourseModule::class,
                    'Course Lecture' => CourseLecture::class,
                    'Student Course' => StudentCourse::class,
                    'Student Lecture' => StudentLecture::class,
                ],
                'required' => false,
                'placeholder' => false,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('action', ChoiceType::class, [
                'label' => 'Action',
                'choices' => [
                    'All Actions' => '',
                    'Created' => 'entity_created',
                    'Updated' => 'entity_updated',
                    'Deleted' => 'entity_deleted',
                ],
                'required' => false,
                'placeholder' => false,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('user', EntityType::class, [
                'label' => 'User',
                'class' => User::class,
                'choice_label' => 'email',
                'required' => false,
                'placeholder' => 'All Users',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('dateFrom', DateType::class, [
                'label' => 'From Date',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateTo', DateType::class, [
                'label' => 'To Date',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
