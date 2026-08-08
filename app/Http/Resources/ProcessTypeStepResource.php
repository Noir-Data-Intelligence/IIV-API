<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessTypeStepResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'processTypeId' => $this->process_type_id,
            'orderIndex' => $this->order_index,
            'name' => $this->name,
            'defaultRole' => $this->default_role,
            'slaDays' => $this->sla_days,
        ];
    }
}
