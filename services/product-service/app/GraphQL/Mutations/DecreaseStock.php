<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;

final readonly class DecreaseStock
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        $product = \App\Models\Product::findOrFail($args['productId']);
        
        if ($product->stock < $args['quantity']) {
            throw new \Exception("Stok tidak mencukupi.");
        }

        $product->decrement('stock', $args['quantity']);
        
        return $product;
    }
}
