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
        $count = static::getModel()::where('payment_status', 'paid')->where('status', 'processing')->count();

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
                Forms\Components\TextInput::make('shipping_state')->label('State / region')->disabled(),
                Forms\Components\TextInput::make('shipping_postcode')->label('Postcode')->disabled(),
                Forms\Components\TextInput::make('shipping_country')->disabled(),
                Forms\Components\TextInput::make('shipping_carrier')->label('Carrier')->disabled()
                    ->visible(fn (?Order $record) => (bool) $record?->isInternational()),
                Forms\Components\TextInput::make('tracking_number')->label('Tracking number')->disabled()
                    ->visible(fn (?Order $record) => (bool) $record?->isInternational()),
            ])->columns(3),

            Forms\Components\Section::make('Totals')->schema([
                Forms\Components\TextInput::make('subtotal')->disabled()->prefix('₦'),
                Forms\Components\TextInput::make('shipping_fee')->disabled()->prefix('₦'),
                Forms\Components\TextInput::make('vat_amount')->label('VAT')->disabled()->prefix('₦'),
                Forms\Components\TextInput::make('total_amount')->disabled()->prefix('₦'),
                Forms\Components\Toggle::make('free_shipping')
                    ->label('Free delivery applied')
                    ->helperText('Check the address is really in Lagos before dispatch.')
                    ->disabled()
                    ->columnSpanFull(),
            ])->columns(4),

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
            Tables\Columns\TextColumn::make('shipping_country')
                ->label('Ship to')
                ->badge()
                ->color(fn (?string $state) => $state && $state !== 'Nigeria' ? 'warning' : 'gray'),
            Tables\Columns\TextColumn::make('routely_tracking_number')
                ->label('Tracking')
                ->state(fn (Order $record) => $record->tracking_number ?: $record->routely_tracking_number)
                ->description(fn (Order $record) => $record->tracking_number ? $record->shipping_carrier : null)
                ->searchable(['routely_tracking_number', 'tracking_number'])
                ->copyable()
                ->placeholder('Not dispatched'),
            Tables\Columns\TextColumn::make('total_amount')
                ->label('Total')
                ->formatStateUsing(fn ($state) => filled($state) ? '₦'.Number::format((float) $state, 2) : '—')
                ->sortable(),
            Tables\Columns\IconColumn::make('free_shipping')
                ->label('Free delivery')
                ->boolean()
                ->trueColor('warning')
                ->falseColor('gray')
                ->tooltip(fn (Order $record) => $record->free_shipping ? 'Check the address is really in Lagos before dispatch.' : null),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
        ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled']),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options(['unpaid' => 'Unpaid', 'paid' => 'Paid', 'refunded' => 'Refunded'])
                    ->default('paid'),
            ])
            ->actions([
                Tables\Actions\Action::make('dispatch_routely')
                    ->label('Dispatch Routely')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    // Routely only delivers inside Nigeria.
                    ->visible(fn (Order $record) => empty($record->routely_tracking_number) && $record->payment_status === 'paid' && ! $record->isInternational())
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
                        } else {
                            Notification::make()
                                ->title('Routely Dispatch Failed')
                                ->body($result['message'] ?? 'Could not create shipment on Routely. Check your wallet balance.')
                                ->danger()
                                ->send();
                        }
                    }),
                // Overseas parcels are booked with the courier by hand; this records
                // the tracking number and emails it to the customer.
                Tables\Actions\Action::make('mark_shipped')
                    ->label('Mark as shipped')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (Order $record) => $record->isInternational() && $record->payment_status === 'paid' && empty($record->tracking_number))
                    ->modalHeading(fn (Order $record) => "Order #{$record->order_number} shipped to {$record->shipping_country}")
                    ->modalDescription(fn (Order $record) => 'Book the parcel in your Routely dashboard first (the customer paid ₦'.number_format((float) $record->shipping_fee, 2).' for delivery). The customer then gets an email with the tracking number.')
                    ->form([
                        Forms\Components\TextInput::make('shipping_carrier')
                            ->label('Courier')
                            ->helperText('If you enter a DHL waybill number, write "DHL Express" here and the email links to DHL tracking.')
                            ->default(fn (Order $record) => $record->shipping_carrier ?: config('store.international_carrier'))
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('tracking_number')
                            ->label('Tracking number (from Routely)')
                            ->required()
                            ->maxLength(100),
                    ])
                    ->action(function (Order $record, array $data) {
                        $record->update([
                            'shipping_carrier' => trim($data['shipping_carrier']),
                            'tracking_number' => trim($data['tracking_number']),
                            'status' => 'shipped',
                        ]);
                        OrderMailService::sendOrderShippedNotification($record->fresh());

                        Notification::make()
                            ->title('Marked as shipped')
                            ->body("The tracking number was emailed to {$record->customer_email}.")
                            ->success()
                            ->send();
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
