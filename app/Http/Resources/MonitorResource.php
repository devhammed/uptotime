<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Monitor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Monitor */
class MonitorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'check_interval' => $this->check_interval,
            'threshold' => $this->threshold,
            'status' => $this->status,
            'last_checked_at' => $this->last_checked_at,
            'uptime_percentage' => $this->uptime_percentage,
            'created_at' => $this->created_at,
        ];
    }
}
