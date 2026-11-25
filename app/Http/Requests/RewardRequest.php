<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RewardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'cost' => ['required', 'integer', 'min:1', 'max:100000'],
            'icon' => ['nullable', 'string', 'max:16'],
        ];
    }

    public function rewardData(): array
    {
        return [...$this->validated(), 'active' => $this->boolean('active')];
    }
}
