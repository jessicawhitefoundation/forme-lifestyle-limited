<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Shop';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Product Details')->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                    Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
                    Forms\Components\Textarea::make('short_description')->rows(2),
                    Forms\Components\RichEditor::make('description')->columnSpanFull(),
                ])->columns(2),

                Forms\Components\Section::make('Images')
                    ->description('Upload product photos. Assign a Color to an image so when a customer picks that color on the store, this photo displays automatically.')
                    ->schema([
                        Forms\Components\Repeater::make('images')
                            ->relationship()
                            ->schema([
                                Forms\Components\FileUpload::make('image_path')
                                    ->image()
                                    ->required()
                                    ->disk('public')
                                    ->directory('products')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('color')
                                    ->label('Associated Color')
                                    ->placeholder('e.g. Burgundy, Navy, Black, Green')
                                    ->helperText('Type the color shown in this photo (e.g. Burgundy). Matches the color buttons on the store.')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('alt_text')
                                    ->placeholder('e.g. Front view of dress')
                                    ->columnSpan(1),
                                Forms\Components\Toggle::make('is_primary')->label('Primary / Cover Image'),
                                Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
                            ])->columns(2),
                    ]),

                Forms\Components\Section::make('Variants')
                    ->description('Define available sizes and colors. Give variants matching color names to photos uploaded above.')
                    ->schema([
                        Forms\Components\Repeater::make('variants')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('color')
                                    ->placeholder('e.g. Burgundy, Navy, Black')
                                    ->helperText('Color name matching the image above.'),
                                Forms\Components\ColorPicker::make('color_hex')->label('Swatch Color (Hex)'),
                                Forms\Components\TextInput::make('size'),
                                Forms\Components\TextInput::make('sku'),
                                Forms\Components\TextInput::make('price_modifier')
                                    ->numeric()
                                    ->prefix('₦')
                                    ->default(0)
                                    ->helperText('Extra cost added to Base Price (+₦0 if standard)'),
                                Forms\Components\TextInput::make('stock_quantity')->numeric()->default(0),
                                Forms\Components\Toggle::make('is_active')->default(true),
                            ])->columns(3),
                    ]),

                Forms\Components\Section::make('Additional Info')->schema([
                    Forms\Components\TextInput::make('material'),
                    Forms\Components\TextInput::make('care_instructions'),
                ])->columns(2),
            ])->columnSpan(2),

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Status')->schema([
                    Forms\Components\Select::make('category_id')
                        ->label('Category')
                        ->options(Category::pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('gender')
                        ->options([
                            'female' => 'Female',
                            'male' => 'Male',
                            'kids' => 'Kids',
                            'unisex' => 'Unisex',
                        ])
                        ->required()
                        ->default('female'),
                    Forms\Components\Toggle::make('is_active')->default(true),
                    Forms\Components\Toggle::make('is_featured'),
                    Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
                ]),

                Forms\Components\Section::make('Pricing')->schema([
                    Forms\Components\TextInput::make('base_price')
                        ->required()
                        ->numeric()
                        ->prefix('₦')
                        ->helperText('Main selling price for this product'),
                    Forms\Components\TextInput::make('compare_price')->numeric()->prefix('₦')
                        ->helperText('Original price (for sale display)'),
                ]),
            ])->columnSpan(1),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('primary_image')
                ->label('')
                ->getStateUsing(function (Product $record): ?string {
                    $path = $record->primaryImage()?->image_path;
                    if (! $path) {
                        return null;
                    }

                    return str_starts_with($path, 'http') ? $path : asset('storage/'.ltrim($path, '/'));
                })
                ->extraImgAttributes([
                    'onerror' => "this.style.display='none'",
                    'alt' => 'Product',
                ])
                ->square(),
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('category.name')->sortable(),
            Tables\Columns\TextColumn::make('gender')->sortable()->badge()->color(fn (string $state): string => match ($state) {
                'female' => 'pink',
                'male' => 'blue',
                'kids' => 'success',
                'unisex' => 'gray',
                default => 'gray',
            }),
            Tables\Columns\TextColumn::make('base_price')
                ->label('Price')
                ->formatStateUsing(function ($state, Product $record): string {
                    $base = (float) $state;
                    $variants = $record->variants;

                    if ($base > 0) {
                        if ($variants->isNotEmpty()) {
                            $prices = $variants->map(fn ($v) => $base + (float) $v->price_modifier)->unique();
                            if ($prices->count() > 1) {
                                return '₦'.Number::format($prices->min(), 2).' – ₦'.Number::format($prices->max(), 2);
                            }
                        }

                        return '₦'.Number::format($base, 2);
                    }

                    if ($variants->isNotEmpty()) {
                        $variantPrices = $variants->map(fn ($v) => (float) $v->price_modifier)->filter(fn ($p) => $p > 0);
                        if ($variantPrices->isNotEmpty()) {
                            $min = (float) $variantPrices->min();
                            $max = (float) $variantPrices->max();

                            return $min === $max
                                ? '₦'.Number::format($min, 2)
                                : '₦'.Number::format($min, 2).' – ₦'.Number::format($max, 2);
                        }
                    }

                    return '₦'.Number::format($base, 2);
                })
                ->sortable(),
            Tables\Columns\TextColumn::make('variants_count')->counts('variants')->label('Variants'),
            Tables\Columns\IconColumn::make('is_featured')->boolean()->label('Featured'),
            Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
        ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('gender')->options([
                    'female' => 'Female',
                    'male' => 'Male',
                    'kids' => 'Kids',
                    'unisex' => 'Unisex',
                ]),
                Tables\Filters\TernaryFilter::make('is_featured'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->groups([
                Tables\Grouping\Group::make('category.name')
                    ->label('Category'),
            ])
            ->defaultGroup('category.name')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription(function (Product $record) {
                        if ($record->orderItems()->exists()) {
                            return 'Warning: This product is linked to existing customer orders. Deleting it will keep historical order records intact, but will permanently remove this product and its variants. Are you sure you want to proceed?';
                        }

                        return 'Are you sure you want to delete this product? This action cannot be undone.';
                    }),
            ])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    /**
     * @return Builder<Product>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['variants', 'images', 'category']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
