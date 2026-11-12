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

    public function test_the_admin_can_switch_on_delivery_to_the_uk(): void
    {
        StoreSetting::current();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        Livewire::test(StoreSettings::class)
            ->fillForm([
                'international_delivery_time' => '3–5 business days',
                'international_rates.GB.enabled' => true,
                'international_rates.GB.fee' => 45000,
                'international_rates.GB.rate' => 2100,
                'international_rates.US.enabled' => true,
                'international_rates.US.fee' => 60000,
                'international_rates.CA.enabled' => true,
                'international_rates.CA.fee' => 65000,
                'international_rates.CA.rate' => 1150,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = StoreSetting::current();
        $this->assertSame(['United Kingdom', 'United States', 'Canada'], array_column($settings->internationalCountries(), 'name'));
        $this->assertSame(45000.0, $settings->internationalCountry('GB')['fee']);
        // No exchange rate for the US: it's offered, just without the $ estimate.
        $this->assertNull($settings->internationalCountry('US')['rate']);
    }

    public function test_a_country_can_be_switched_on_without_a_backup_fee(): void
    {
        StoreSetting::current();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        // Routely prices it live, so a switch is all it takes.
        Livewire::test(StoreSettings::class)
            ->fillForm(['international_rates.US.enabled' => true, 'international_rates.US.fee' => null, 'international_item_weight' => 0.9])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = StoreSetting::current();
        $this->assertSame(['US'], array_keys($settings->internationalCountries()));
        $this->assertNull($settings->internationalCountry('US')['fee']);
        $this->assertSame(0.9, $settings->internationalItemWeight());
    }
}
