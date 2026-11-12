<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\StoreSettings;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StoreSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_admin_can_pick_overseas_countries(): void
    {
        StoreSetting::current();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Livewire::test(StoreSettings::class)
            ->fillForm([
                'international_country_codes' => ['GB', 'US', 'CA', 'GH'],
                'international_backup_fee' => 45000,
                'international_delivery_time' => '3–5 business days',
                'international_item_weight' => 0.9,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = StoreSetting::current();
        $this->assertEqualsCanonicalizing(['GB', 'US', 'CA', 'GH'], $settings->enabledCountryCodes());
        $this->assertSame(['United Kingdom', 'United States', 'Canada', 'Ghana'], array_column($settings->internationalCountries(), 'name'));
        // One backup fee for every country.
        $this->assertSame(45000.0, $settings->internationalCountry('GH')['fee']);
        $this->assertSame(0.9, $settings->internationalItemWeight());

        // The checklist shows what's saved when the page is opened again.
        Livewire::test(StoreSettings::class)->assertFormSet(['international_country_codes' => ['GB', 'US', 'CA', 'GH']]);
    }

    public function test_every_country_can_be_switched_on_and_off(): void
    {
        StoreSetting::current();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $all = array_keys(config('countries'));

        Livewire::test(StoreSettings::class)->fillForm(['international_country_codes' => $all])->call('save')->assertHasNoFormErrors();
        $this->assertCount(76, StoreSetting::current()->internationalCountries());
        $this->assertNull(StoreSetting::current()->internationalCountry('GB')['fee']);

        Livewire::test(StoreSettings::class)->fillForm(['international_country_codes' => []])->call('save');
        $this->assertSame([], StoreSetting::current()->internationalCountries());
    }
}
