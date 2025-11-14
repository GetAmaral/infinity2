<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for a single few-shot example (user/assistant pair)
 */
class FewShotExampleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', TextareaType::class, [
                'label' => 'User Message',
                'required' => true,
                'attr' => [
                    'class' => 'form-input-modern fewshot-user',
                    'rows' => 2,
                    'placeholder' => 'What the user says...',
                ],
            ])
            ->add('assistant', TextareaType::class, [
                'label' => 'Assistant Response',
                'required' => true,
                'attr' => [
                    'class' => 'form-input-modern fewshot-assistant',
                    'rows' => 3,
                    'placeholder' => 'How the assistant should respond...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // No data_class - we're working with arrays
        ]);
    }
}
