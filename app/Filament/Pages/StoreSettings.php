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
        $settings = StoreSetting::current();

        $this->form->fill($settings->attributesToArray() + [
            'international_country_codes' => $settings->enabledCountryCodes(),
        ]);
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
                    ->description('Tick each country you deliver to and it appears at checkout. Routely prices every overseas order live (DHL Express through Routely, by country and parcel weight), and the customer pays that price by card in naira. Book the parcel in your Routely dashboard, then use "Mark as shipped" on the order. All '.(count(config('store.countries')) - 1).' countries here were checked with Routely.')
                    ->schema([
                        Forms\Components\CheckboxList::make('international_country_codes')
                            ->label('Countries you deliver to')
                            ->options(fn () => collect(config('store.countries'))->except('NG')->map(fn ($c) => $c['name'])->sort()->all())
                            ->columns(['default' => 2, 'md' => 3, 'xl' => 4])
                            ->searchable()
                            ->bulkToggleable()
                            ->helperText('Use "Select all" to deliver everywhere listed, or search for a country.'),
                        Forms\Components\TextInput::make('international_backup_fee')
                            ->label('Backup delivery fee (optional)')
                            ->helperText('Used for any country only if Routely can’t give a price. Leave empty and checkout asks the customer to try again instead.')
                            ->numeric()
                            ->minValue(1)
                            ->prefix('₦'),
                        Forms\Components\TextInput::make('international_item_weight')
                            ->label('Typical weight of one item')
                            ->helperText('Products have no weights yet, so each overseas parcel is estimated as this × the number of items. Routely prices by weight: weigh a typical packed set and enter it here.')
                            ->numeric()
                            ->minValue(0.1)
                            ->maxValue(20)
                            ->step(0.1)
                            ->placeholder((string) config('store.international_item_weight_kg'))
                            ->suffix('kg'),
                        Forms\Components\TextInput::make('international_delivery_time')
                            ->label('Delivery time shown if Routely is unavailable')
                            ->placeholder('3–5 business days')
                            ->maxLength(100),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();

            // The country checklist is stored as {"GB": {"enabled": true}, ...}.
            $codes = array_values(array_intersect((array) ($data['international_country_codes'] ?? []), array_keys(config('store.countries'))));
            unset($data['international_country_codes']);
            $data['international_rates'] = collect($codes)->reject(fn ($code) => $code === 'NG')->mapWithKeys(fn ($code) => [$code => ['enabled' => true]])->all();

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
