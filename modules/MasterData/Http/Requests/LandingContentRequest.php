<?php

namespace Modules\MasterData\Http\Requests;

use App\Helpers\FileApps;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Update the texts and hero image of the public landing page.
 */
class LandingContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request (RBAC is
     * enforced by the permission middleware).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'remove_hero_image' => $this->boolean('remove_hero_image'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'hero_title' => ['required', 'string', 'max:120'],
            'hero_intro' => ['required', 'string', 'max:600'],
            'hero_image' => FileApps::imageRules(),
            'hero_image_alt' => ['nullable', 'string', 'max:255'],
            'remove_hero_image' => ['boolean'],
            'feedback_title' => ['required', 'string', 'max:120'],
            'feedback_body' => ['required', 'string', 'max:2000'],
            'feedback_note' => ['nullable', 'string', 'max:120'],
            'closing_title' => ['required', 'string', 'max:160'],
            'closing_note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'hero_title' => 'headline',
            'hero_intro' => 'introduction',
            'hero_image' => 'hero image',
            'hero_image_alt' => 'image description',
            'feedback_title' => 'feedback title',
            'feedback_body' => 'feedback text',
            'feedback_note' => 'feedback note',
            'closing_title' => 'closing title',
            'closing_note' => 'closing note',
        ];
    }

    /**
     * Text attributes ready to persist (without the image fields).
     *
     * @return array<string, mixed>
     */
    public function contentAttributes(): array
    {
        return $this->safe()->except(['hero_image', 'remove_hero_image']);
    }
}
