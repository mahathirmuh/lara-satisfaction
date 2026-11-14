<?php

namespace Tests\Feature\Account;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_password_page_is_displayed(): void
    {
        $this->actingAs($this->employee())
            ->get(route('account.password.edit'))
            ->assertOk()
            ->assertSee('Change Password');
    }

    public function test_password_is_changed_with_the_current_password(): void
    {
        $employee = $this->employee();

        $this->actingAs($employee)
            ->put(route('account.password.update'), [
                'current_password' => 'password',
                'password' => 'NewPassword2026',
                'password_confirmation' => 'NewPassword2026',
            ])
            ->assertRedirect(route('account.password.edit'))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NewPassword2026', $employee->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $employee = $this->employee();

        $this->actingAs($employee)
            ->put(route('account.password.update'), [
                'current_password' => 'not-my-password',
                'password' => 'NewPassword2026',
                'password_confirmation' => 'NewPassword2026',
            ])
            ->assertSessionHasErrorsIn('updatePassword', ['current_password']);

        $this->assertTrue(Hash::check('password', $employee->fresh()->password));
    }

    public function test_new_password_must_follow_the_password_policy(): void
    {
        $this->actingAs($this->employee())
            ->put(route('account.password.update'), [
                'current_password' => 'password',
                'password' => 'lettersonly',
                'password_confirmation' => 'lettersonly',
            ])
            ->assertSessionHasErrorsIn('updatePassword', ['password']);
    }
}
