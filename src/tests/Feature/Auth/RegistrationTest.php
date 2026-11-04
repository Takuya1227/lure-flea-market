<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_fails_with_invalid_email(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'Test User',
            'email'                 => 'not-an-email', // 不正なメールアドレス
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest(); // 登録されていない
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        // 同じメールアドレスで登録済みのユーザーを作成
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->post('/register', [
            'name'                  => 'Another User',
            'email'                 => 'taken@example.com', // 重複メール
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_fails_with_mismatched_password(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'different-password', // 確認用パスワードが不一致
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }
}
