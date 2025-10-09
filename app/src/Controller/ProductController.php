<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\Generated\ProductControllerGenerated;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Product Controller
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom actions and override methods here.
 *
 * @generated once by Luminai Code Generator
 */
#[Route('/product')]
class ProductController extends ProductControllerGenerated
{
    // Add custom actions here

    // Example:
    // #[Route('/custom-action', name: 'product_custom')]
    // public function customAction(): Response
    // {
    //     return $this->render('product/custom.html.twig');
    // }
}
