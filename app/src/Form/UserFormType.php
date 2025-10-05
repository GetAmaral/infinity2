<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Organization;
use App\Entity\Role;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'user.form.name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'user.form.name_placeholder',
                    'data-live-name-value' => 'name',
                    'data-action' => 'live#update',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'user.validation.name_required'),
                    new Assert\Length(
                        min: 2,
                        max: 255,
                        minMessage: 'user.validation.name_min_length',
                        maxMessage: 'user.validation.name_max_length'
                    ),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'user.form.email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'user.form.email_placeholder',
                    'data-live-name-value' => 'email',
                    'data-action' => 'live#update',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'user.validation.email_required'),
                    new Assert\Email(message: 'user.validation.email_invalid'),
                ],
            ])
            ->add('organization', EntityType::class, [
                'class' => Organization::class,
                'choice_label' => 'name',
                'label' => 'user.form.organization',
                'placeholder' => 'user.form.organization_placeholder',
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                    'data-live-name-value' => 'organization',
                    'data-action' => 'live#update',
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('o')
                        ->orderBy('o.name', 'ASC');
                },
            ])
            ->add('roleEntities', EntityType::class, [
                'class' => Role::class,
                'choice_label' => 'name',
                'label' => 'user.form.roles',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'attr' => [
                    'class' => 'form-check-group',
                ],
                'choice_attr' => function (Role $role) {
                    return [
                        'class' => 'form-check-input',
                        'data-live-name-value' => 'roles',
                        'data-action' => 'live#update',
                    ];
                },
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('r')
                        ->orderBy('r.name', 'ASC');
                },
            ])
            ->add('isVerified', CheckboxType::class, [
                'label' => 'user.form.is_verified',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                    'data-live-name-value' => 'isVerified',
                    'data-action' => 'live#update',
                ],
            ]);

        // Add password field only for new users or when explicitly requested
        if (!$isEdit || $options['include_password']) {
            $builder->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'user.validation.password_mismatch',
                'required' => !$isEdit,
                'first_options' => [
                    'label' => 'user.form.password',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'user.form.password_placeholder',
                    ],
                ],
                'second_options' => [
                    'label' => 'user.form.password_confirm',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'user.form.password_confirm_placeholder',
                    ],
                ],
                'constraints' => [
                    new Assert\NotBlank(
                        message: 'user.validation.password_required',
                        groups: ['create']
                    ),
                    new Assert\Length(
                        min: 6,
                        minMessage: 'user.validation.password_min_length',
                        max: 4096
                    ),
                ],
                'mapped' => false, // This field is not mapped to the User entity
            ]);
        }

        $builder
            ->add('submit', SubmitType::class, [
                'label' => $isEdit ? 'button.update_user' : 'button.create_user',
                'attr' => [
                    'class' => 'btn luminai-btn-primary w-100',
                    'data-loading-text' => $isEdit ? 'button.updating' : 'button.creating',
                ],
            ]);

        // Add event listener to handle password encoding
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($isEdit) {
            $user = $event->getData();
            $form = $event->getForm();

            if ($form->has('plainPassword') && $form->get('plainPassword')->getData()) {
                // Password will be encoded in the controller or service
                $user->setPassword($form->get('plainPassword')->getData());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
            'include_password' => false,
            'validation_groups' => function (FormInterface $form) {
                $data = $form->getData();
                return $data && $data->getId() ? ['Default'] : ['Default', 'create'];
            },
            'attr' => [
                'novalidate' => 'novalidate',
                'data-controller' => 'form-submit user-form',
                'data-form-submit-loading-class' => 'form-loading',
            ],
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
        $resolver->setAllowedTypes('include_password', 'bool');
    }
}
