<?php

namespace App\Http\Requests\HeroSlide;

use Illuminate\Foundation\Http\FormRequest;

class HeroSlideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'kicker' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['required', 'string', 'max:255'],
            'ctaLabel' => ['required', 'string', 'max:100'],
            'ctaLink' => ['required', 'string', 'max:255'],
            'imageUrl' => ['nullable', 'string'],
            'sortOrder' => ['nullable', 'integer'],
            'published' => ['boolean'],
        ];
    }
}
