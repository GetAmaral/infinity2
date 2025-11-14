<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Generated\StepActionTypeGenerated;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * StepAction Form Type
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom form fields, transformers, and event listeners here.
 *
 * @generated once by Genmax
 */
class StepActionType extends StepActionTypeGenerated
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        // Make viewOrder field optional - use existing value if empty
        $builder->add('viewOrder', null, [
            'required' => false,
            'empty_data' => function ($form) {
                // Return the existing value if the field is empty
                return $form->getParent()->getData()->getViewOrder();
            },
            'attr' => [
                'class' => 'form-input-modern',
                'type' => 'number',
                'min' => 1,
            ],
        ]);

        // Replace importance field with ChoiceType for star rating
        $builder->add('importance', ChoiceType::class, [
            'label' => 'Importance',
            'choices' => [
                '1' => 1,
                '2' => 2,
                '3' => 3,
            ],
            'expanded' => true,
            'required' => false,
            'data' => 1, // Default to 1 star
        ]);

        // Replace fewShot with CollectionType for structured user/assistant pairs
        $builder->add('fewShot', CollectionType::class, [
            'entry_type' => FewShotExampleType::class,
            'entry_options' => [
                'label' => false,
            ],
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'required' => false,
            'label' => 'Few-Shot Examples',
            'attr' => [
                'class' => 'fewshot-collection',
                'data-controller' => 'collection',
                'data-collection-prototype-name-value' => '__fewshot_name__',
            ],
        ]);
    }
}
