<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

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
