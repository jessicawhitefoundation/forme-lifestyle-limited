<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['base_price']) || (float) $data['base_price'] <= 0) {
            $rawVariants = $data['variants'] ?? [];
            if (is_array($rawVariants)) {
                $prices = [];
                foreach ($rawVariants as $variant) {
                    if (is_array($variant) && isset($variant['price_modifier'])) {
                        $modifier = (float) $variant['price_modifier'];
                        if ($modifier > 0) {
                            $prices[] = $modifier;
                        }
                    }
                }

                if (! empty($prices)) {
                    $data['base_price'] = min($prices);
                }
            }
        }

        return $data;
    }
}
