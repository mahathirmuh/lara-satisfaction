<?php

namespace Tests\Feature\SystemLog;

use App\Models\Log\LogActivity;
use App\Models\Log\LogError;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class SystemLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_admin_browses_the_activity_log(): void
    {
        $superAdmin = $this->itAdmin(['name' => 'Root Admin']);
        $this->actingAs($superAdmin)->post(route('logout'));

        $this->actingAs($superAdmin)
            ->getJson(route('system-log.activity.list'))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.route_name', 'logout')
            ->assertJsonPath('data.0.user', 'Root Admin');

        $this->actingAs($superAdmin)
            ->getJson(route('system-log.activity.show', LogActivity::query()->sole()))
            ->assertOk()
            ->assertJsonPath('data.method', 'POST');
    }

    public function test_reported_exceptions_are_stored_with_a_redacted_payload(): void
    {
        Route::middleware('web')->post('exploding-endpoint', fn () => throw new RuntimeException('Something broke'));

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post('exploding-endpoint', ['password' => 'Secret-Value-123', 'note' => 'context'])
            ->assertInternalServerError();

        $error = LogError::query()->sole();

        $this->assertSame(RuntimeException::class, $error->exception);
        $this->assertSame('Something broke', $error->message);
        $this->assertStringEndsWith('exploding-endpoint', $error->url);
        $this->assertStringNotContainsString('Secret-Value-123', $error->data);
        $this->assertStringContainsString('context', $error->data);
    }

    public function test_error_log_is_restricted_to_granted_roles(): void
    {
        $this->actingAs($this->superAdmin())
            ->getJson(route('system-log.error.list'))
            ->assertForbidden();
    }
}
