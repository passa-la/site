<?php

namespace App\Http\Api\Requests;

use App\Domain\Geo\Point;
use Illuminate\Foundation\Http\FormRequest;

abstract class Located extends FormRequest
{
    protected bool $must = true;

    public function rules(): array
    {
        $need = $this->must ? 'required' : 'nullable';

        return [
            'lat' => [$need, 'required_with:lon', 'numeric', 'between:-90,90'],
            'lon' => [$need, 'required_with:lat', 'numeric', 'between:-180,180'],
        ];
    }

    public function point(): ?Point
    {
        return $this->filled(['lat', 'lon'])
            ? new Point($this->float('lat'), $this->float('lon'))
            : null;
    }
}
