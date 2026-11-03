<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
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

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->modalDescription(function ($record) {
                    if ($record->orderItems()->exists()) {
                        return 'Warning: This product is linked to existing customer orders. Deleting it will keep historical order records intact, but will permanently remove this product and its variants. Are you sure you want to proceed?';
                    }

                    return 'Are you sure you want to delete this product? This action cannot be undone.';
                }),
        ];
    }
}
