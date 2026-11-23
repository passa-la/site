<?php

namespace App\Http\Api\Requests;

use App\Domain\Geo\Circle;
use App\Domain\Geo\Point;
use App\Domain\Transit\Day;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class Located extends FormRequest
{
    protected bool $must = true;

    protected bool $start = false;

    public function rules(): array
    {
        $need = $this->must ? 'required' : 'nullable';
        $from = $this->start ? 'required' : 'nullable';

        return [
            'lat' => [$need, 'required_with:lon', 'numeric', 'between:-90,90'],
            'lon' => [$need, 'required_with:lat', 'numeric', 'between:-180,180'],
            'from_lat' => [$from, 'required_with:from_lon', 'numeric', 'between:-90,90'],
            'from_lon' => [$from, 'required_with:from_lat', 'numeric', 'between:-180,180'],
            'day' => ['nullable', Rule::enum(Day::class)],
            'stop' => ['nullable', 'string', 'max:40'],
            'end' => ['nullable', 'string', 'max:40'],
            'radius' => ['nullable', 'integer', 'between:'.Circle::MIN.','.Circle::WALK],
        ];
    }

    public function point(): ?Point
    {
        return $this->pair('lat', 'lon');
    }

    public function origin(): ?Point
    {
        return $this->pair('from_lat', 'from_lon');
    }

    public function day(): Day
    {
        return $this->enum('day', Day::class) ?? Day::of(now());
    }

    public function radius(): int
    {
        return $this->integer('radius', Circle::WALK);
    }

    private function pair(string $lat, string $lon): ?Point
    {
        return $this->filled([$lat, $lon])
            ? new Point($this->float($lat), $this->float($lon))
            : null;
    }
}
