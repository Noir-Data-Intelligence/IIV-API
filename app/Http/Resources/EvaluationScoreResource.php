<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationScoreResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'evaluationId' => $this->evaluation_id,
            'criteriaId' => $this->criteria_id,
            'score' => (float) $this->score,
            'comment' => $this->comment,
        ];
    }
}
