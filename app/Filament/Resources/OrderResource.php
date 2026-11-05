<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Services\OrderMailService;
use App\Services\RoutelyService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Shop';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereIn('status', ['pending', 'processing'])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Order Info')->schema([
                Forms\Components\TextInput::make('order_number')->disabled(),
                Forms\Components\Select::make('status')
                    ->options(['pending' => 'Pending', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'])
                    ->required(),
                Forms\Components\Select::make('payment_status')
                    ->options(['unpaid' => 'Unpaid', 'paid' => 'Paid', 'refunded' => 'Refunded'])
                    ->required(),
                Forms\Components\TextInput::make('payment_reference')->disabled(),
            ])->columns(2),

            Forms\Components\Section::make('Customer')->schema([
                Forms\Components\TextInput::make('customer_name')->disabled(),
                Forms\Components\TextInput::make('customer_email')->disabled(),
                Forms\Components\TextInput::make('customer_phone')->disabled(),
            ])->columns(3),

            Forms\Components\Section::make('Shipping')->schema([
                Forms\Components\TextInput::make('shipping_address')->disabled()->columnSpanFull(),
                Forms\Components\TextInput::make('shipping_city')->disabled(),
                Forms\Components\TextInput::make('shipping_state')->disabled(),
                Forms\Components\TextInput::make('shipping_country')->disabled(),
            ])->columns(3),

            Forms\Components\Section::make('Totals')->schema([
                Forms\Components\TextInput::make('subtotal')->disabled()->prefix('₦'),
                Forms\Components\TextInput::make('shipping_fee')->disabled()->prefix('₦'),
                Forms\Components\TextInput::make('total_amount')->disabled()->prefix('₦'),
            ])->columns(3),

            Forms\Components\Section::make('Routely Logistics & Dispatch')->schema([
                Forms\Components\TextInput::make('routely_tracking_number')
                    ->label('Tracking Number')
                    ->disabled(),
                Forms\Components\TextInput::make('routely_status')
                    ->label('Routely Status')
                    ->disabled(),
                Forms\Components\TextInput::make('routely_shipment_id')
                    ->label('Routely Shipment ID')
                    ->disabled(),
                Forms\Components\TextInput::make('routely_fee')
                    ->label('Logistics Cost')
                    ->prefix('₦')
                    ->disabled(),
            ])->columns(4),

            Forms\Components\Section::make('Notes')->schema([
                Forms\Components\Textarea::make('notes')->disabled()->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Order Items')->schema([
                Forms\Components\Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        Forms\Components\TextInput::make('product_name')
                            ->disabled()
                            ->label('Product'),
                        Forms\Components\TextInput::make('variant_description')
                            ->disabled()
                            ->label('Variant'),
                        Forms\Components\TextInput::make('quantity')
                            ->disabled()
                            ->numeric(),
                        Forms\Components\TextInput::make('unit_price')
                            ->disabled()
                            ->prefix('₦')
                            ->label('Unit Price'),
                        Forms\Components\TextInput::make('total_price')
                            ->disabled()
                            ->prefix('₦')
                            ->label('Line Total'),
                    ])
                    ->columns(5)
                    ->disabled()
                    ->deletable(false)
                    ->addable(false)
                    ->reorderable(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('order_number')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('customer_name')->searchable(),
            Tables\Columns\TextColumn::make('customer_email')->searchable(),
            Tables\Columns\BadgeColumn::make('status')
                ->colors(['warning' => 'pending', 'primary' => 'processing', 'info' => 'shipped', 'success' => 'delivered', 'danger' => 'cancelled']),
            Tables\Columns\BadgeColumn::make('payment_status')
                ->colors(['danger' => 'unpaid', 'success' => 'paid', 'warning' => 'refunded']),
            Tables\Columns\TextColumn::make('routely_tracking_number')
                ->label('Routely Tracking')
                ->searchable()
                ->copyable()
                ->placeholder('Not dispatched'),
            Tables\Columns\TextColumn::make('total_amount')
                ->label('Total')
                ->formatStateUsing(fn ($state) => filled($state) ? '₦'.Number::format((float) $state, 2) : '—')
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
        ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled']),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options(['unpaid' => 'Unpaid', 'paid' => 'Paid', 'refunded' => 'Refunded']),
            ])
            ->actions([
                Tables\Actions\Action::make('dispatch_routely')
                    ->label('Dispatch Routely')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->visible(fn (Order $record) => empty($record->routely_tracking_number) && $record->payment_status === 'paid')
                    ->requiresConfirmation()
                    ->modalHeading('Book Shipment with Routely')
                    ->modalDescription(fn (Order $record) => "Are you sure you want to book a courier to deliver Order #{$record->order_number} to {$record->shipping_address}, {$record->shipping_city}, {$record->shipping_state}?")
                    ->action(function (Order $record) {
                        $service = app(RoutelyService::class);
                        $result = $service->createShipment($record);
                        if ($result['success'] ?? false) {
                            Notification::make()
                                ->title('Dispatched on Routely')
                                ->body('Tracking ID: '.($result['tracking_number'] ?? 'Created'))
                                ->success()
                                ->send();
                            $record->update(['status' => 'shipped']);
                            OrderMailService::sendOrderShippedNotification($record->fresh());
                            \App\Services\OrderMailService::sendOrderShippedNotification($record->fresh());
                        } else {
                            Notification::make()
                                ->title('Routely Dispatch Failed')
                                ->body($result['message'] ?? 'Could not create shipment on Routely. Check your wallet balance.')
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
