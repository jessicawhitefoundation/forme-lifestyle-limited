<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Shop';

    protected static ?int $navigationSort = 3;

    /**
     * Deleting a category would also delete every product in it, so it must be
     * empty first.
     */
    public static function canDelete(Model $record): bool
    {
        return parent::canDelete($record) && ! $record->products()->exists();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('description')->columnSpanFull(),
                Forms\Components\FileUpload::make('image_path')
                    ->label('Category Image')
                    ->image()
                    ->disk('public')
                    ->directory('categories')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')->default(true),
                Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ImageColumn::make('image_path')
                ->label('Image')
                ->getStateUsing(function (Category $record): ?string {
                    $path = $record->image_path;
                    if (! $path) {
                        return null;
                    }

                    return str_starts_with($path, 'http') ? $path : asset('storage/'.ltrim($path, '/'));
                })
                ->square(),
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('slug'),
            Tables\Columns\TextColumn::make('products_count')->counts('products')->label('Products'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
            Tables\Columns\TextColumn::make('sort_order')->sortable(),
        ])
            ->defaultSort('sort_order')
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
