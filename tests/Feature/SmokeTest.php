<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_sees_under_construction_before_onboarding(): void
    {
        $this->get('/')->assertSee('getting things ready', false);
    }

    public function test_secret_setup_url_is_reachable(): void
    {
        $this->get('/setup/'.config('onboarding.secret'))
            ->assertOk()
            ->assertSee('set up your portal');
    }

    public function test_wrong_secret_is_hidden(): void
    {
        $this->get('/setup/not-the-secret')->assertNotFound();
    }

    public function test_onboarded_flag_flips_visibility(): void
    {
        Setting::set('onboarding_completed', true);

        $this->assertTrue(Setting::isOnboarded());
        $this->get('/')->assertRedirect(route('login'));
    }
}
