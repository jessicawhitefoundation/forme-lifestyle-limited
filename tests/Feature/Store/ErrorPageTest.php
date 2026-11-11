<?php

namespace Tests\Feature\Store;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A mistyped address (for example "/admin/login." with a trailing full stop)
     * matches no route, so the shared store details must come with the error page
     * or the store layout crashes in the browser.
     */
    public function test_not_found_page_has_the_details_the_store_layout_needs(): void
    {
        config(['app.debug' => false]);

        $this->get('/admin/login.')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Error')
                ->where('status', 404)
                ->has('store_contact.email')
                ->has('store_contact.whatsapp_number')
                ->has('store_setting')
                ->where('auth.user', null));
    }
}
