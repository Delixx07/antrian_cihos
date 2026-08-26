<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * "/" butuh login (auth.antrian middleware) - tamu diarahkan ke /login,
     * bukan 200. Ini cuma smoke test bahwa routing/middleware ke-boot benar.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
