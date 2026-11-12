<?php

namespace App\Filament\Pages;

use App\Models\StoreSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class StoreSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Storefront';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Store Settings';

    protected static string $view = 'filament.pages.store-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(StoreSetting::current()->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Announcement Bar')
                    ->description('Manage the global announcement bar shown at the top of the store.')
                    ->schema([
                        Forms\Components\Toggle::make('announcement_is_active')
                            ->label('Show Announcement Bar')
                            ->default(true),
                        Forms\Components\TextInput::make('announcement_text')
                            ->label('Announcement Text')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('announcement_link')
                            ->label('Link URL')
                            ->placeholder('/shop')
                            ->nullable()
                            ->maxLength(255),
                    ]),
                Forms\Components\Section::make('Free Delivery')
                    ->description('Applies to Lagos deliveries only. Customers in other states always pay delivery.')
                    ->schema([
                        Forms\Components\Toggle::make('free_shipping_enabled')
                            ->label('Offer free delivery within Lagos'),
                        Forms\Components\TextInput::make('free_shipping_threshold')
                            ->label('Minimum order value (before delivery)')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('₦')
                            ->required(),
                    ]),
                Forms\Components\Section::make('VAT')
                    ->description('Leave off until your accountant confirms whether the business must charge VAT. Applies to Nigerian orders only: overseas orders are exports, which are zero-rated, so they never carry VAT.')
                    ->schema([
                        Forms\Components\Toggle::make('vat_enabled')
                            ->label('Charge VAT')
                            ->live(),
                        Forms\Components\TextInput::make('vat_rate')
                            ->label('VAT rate')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->required()
                            ->visible(fn (Forms\Get $get) => (bool) $get('vat_enabled')),
                        Forms\Components\Toggle::make('vat_inclusive')
                            ->label('Product prices already include VAT')
                            ->helperText('When off, VAT is added on top of the price at checkout.')
                            ->visible(fn (Forms\Get $get) => (bool) $get('vat_enabled')),
                    ]),
                Forms\Components\Section::make('International Delivery')
                    ->description('Lets customers order to the UK, US and Canada, shipped by '.config('store.international_carrier').'. Turn on international payments in your Paystack dashboard first, or their cards will be declined.')
                    ->schema([
                        Forms\Components\Toggle::make('international_enabled')
                            ->label('Deliver outside Nigeria')
                            ->live(),
                        Forms\Components\TextInput::make('international_delivery_time')
                            ->label('Delivery time shown to customers')
                            ->placeholder('3–5 business days')
                            ->maxLength(100)
                            ->visible(fn (Forms\Get $get) => (bool) $get('international_enabled')),
                        ...$this->countryFields(),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * One row per overseas country: on/off, flat delivery fee, exchange rate.
     *
     * @return array<int, Forms\Components\Component>
     */
    private function countryFields(): array
    {
        $fields = [];

        foreach (config('store.countries') as $code => $country) {
            if ($code === 'NG') {
                continue;
            }

            $on = fn (Forms\Get $get) => (bool) $get("international_rates.{$code}.enabled");

            $fields[] = Forms\Components\Fieldset::make($country['name'])
                ->columns(3)
                ->visible(fn (Forms\Get $get) => (bool) $get('international_enabled'))
                ->schema([
                    Forms\Components\Toggle::make("international_rates.{$code}.enabled")
                        ->label('Deliver here')
                        ->live()
                        ->inline(false),
                    Forms\Components\TextInput::make("international_rates.{$code}.fee")
                        ->label('Delivery fee per order')
                        ->helperText('What the customer pays for delivery, in naira.')
                        ->numeric()
                        ->minValue(1)
                        ->prefix('₦')
                        ->required($on),
                    Forms\Components\TextInput::make("international_rates.{$code}.rate")
                        ->label("Naira per 1 {$country['symbol']}")
                        ->helperText("Used to show prices roughly in {$country['currency']}. Leave empty to hide the estimate.")
                        ->numeric()
                        ->minValue(1)
                        ->prefix('₦'),
                ]);
        }

        return $fields;
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            StoreSetting::firstOrFail()->update($data);

            Notification::make()
                ->title('Saved successfully')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to save')
                ->danger()
                ->send();
        }
    }
}
