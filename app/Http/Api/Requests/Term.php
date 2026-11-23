<?php

namespace App\Http\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class Term extends FormRequest
{
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'max:60'],
        ];
    }

    public function text(): string
    {
        return $this->string('q')->squish()->toString();
    }
}
