<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/profile', [
            'name'  => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');
        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('updated@example.com', $user->email);
        // メール変更時は確認済みフラグがリセットされる
        $this->assertNull($user->email_verified_at);
    }

    public function test_nickname_and_bio_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/profile', [
            'name'     => $user->name,
            'email'    => $user->email,
            'nickname' => 'fisher123',
            'bio'      => 'バスフィッシング大好きです。',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');
        $user->refresh();
        $this->assertSame('fisher123', $user->nickname);
        $this->assertSame('バスフィッシング大好きです。', $user->bio);
    }

    public function test_email_verification_status_is_unchanged_when_email_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/profile', [
            'name'  => 'Test User',
            'email' => $user->email, // メールアドレスを変更しない
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');
        // メール変更なし → 確認済みフラグはそのまま
        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/profile', [
            'password' => 'password',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/');
        $this->assertGuest();

        // ソフトデリート: レコードは残るが deleted_at が入っている
        $this->assertSoftDeleted('users', ['id' => $user->id]);

        // 個人情報の匿名化確認
        $deleted = User::withTrashed()->find($user->id);
        $this->assertSame('退会済みユーザー', $deleted->name);
        $this->assertNull($deleted->nickname);
        $this->assertNull($deleted->bio);
        $this->assertStringStartsWith('deleted_', $deleted->email);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from('/profile')->delete('/profile', [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrorsIn('userDeletion', 'password')->assertRedirect('/profile');
        $this->assertNotNull($user->fresh()); // 削除されていない
    }

    // 他のユーザーのプロフィールは編集できない（認可テスト）
    public function test_user_cannot_access_another_users_profile_data(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // userA としてログインしても /profile はあくまで自分のプロフィール
        // 他ユーザーのデータを直接書き換えることはできない
        $response = $this->actingAs($userA)->patch('/profile', [
            'name'  => 'Hacked Name',
            'email' => $userB->email, // 他ユーザーのメールに変更しようとする
        ]);

        // バリデーションエラーになりリダイレクトされる（uniqueルール違反）
        $response->assertSessionHasErrors('email');
        // userB のメールアドレスは変わっていない
        $this->assertSame($userB->email, $userB->fresh()->email);
    }
}
