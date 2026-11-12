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
                'international_enabled' => true,
                'international_delivery_time' => '3–5 business days',
                'international_rates.GB.enabled' => true,
                'international_rates.GB.fee' => 45000,
                'international_rates.GB.rate' => 2100,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = StoreSetting::current();
        $this->assertTrue($settings->international_enabled);
        $this->assertSame(['United Kingdom'], array_column($settings->internationalCountries(), 'name'));
        $this->assertSame(45000.0, $settings->internationalCountry('GB')['fee']);
    }
}
