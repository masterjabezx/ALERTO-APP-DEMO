<?php

declare(strict_types=1);

namespace App\Http\Requests\Messenger;

use Illuminate\Foundation\Http\FormRequest;

class CreateGroupConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:255'],
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'A group conversation title is required.',
            'participant_ids.required' => 'At least one participant must be selected.',
            'participant_ids.min' => 'At least one participant must be selected.',
        ];
    }
}
