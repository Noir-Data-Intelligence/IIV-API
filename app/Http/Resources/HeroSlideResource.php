<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HeroSlideResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kicker' => $this->kicker,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'ctaLabel' => $this->cta_label,
            'ctaLink' => $this->cta_link,
            'imageUrl' => $this->image_url,
            'sortOrder' => $this->sort_order,
            'published' => (bool) $this->published,
        ];
    }
}
