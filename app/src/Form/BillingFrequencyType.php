<?php

declare(strict_types=1);

namespace App\Form;

use App\Form\Generated\BillingFrequencyTypeGenerated;

/**
 * BillingFrequency Form Type
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom form fields, transformers, and event listeners here.
 *
 * @generated once by Genmax
 */
class BillingFrequencyType extends BillingFrequencyTypeGenerated
{
    // Override buildForm() to add custom fields or modify generated ones

    // Example:
    // public function buildForm(FormBuilderInterface $builder, array $options): void
    // {
    //     parent::buildForm($builder, $options);
    //
    //     // Add custom field
    //     $builder->add('customField', TextType::class, [
    //         'label' => 'Custom Field',
    //         'required' => false,
    //     ]);
    //
    //     // Or modify existing field
    //     $builder->get('existingField')->setRequired(false);
    // }
}
