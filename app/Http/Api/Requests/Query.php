<?php

namespace App\Http\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class Query extends FormRequest
{
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:3', 'max:120'],
        ];
    }

    public function text(): string
    {
        return $this->string('q')->squish()->toString();
    }
}
