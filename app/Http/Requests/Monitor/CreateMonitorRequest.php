<?php

declare(strict_types=1);

namespace App\Http\Requests\Monitor;

use App\Models\Monitor;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class CreateMonitorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'active_url', Rule::unique(Monitor::class)->where('user_id', $this->user()->getKey())],
            'check_interval' => ['sometimes', 'integer', 'min:1', 'max:60'],
            'threshold' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
