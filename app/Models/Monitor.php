<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use App\Enums\MonitorStatus;
use Database\Factories\MonitorFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Monitor extends Model
{
    /** @use HasFactory<MonitorFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => MonitorStatus::Pending,
    ];

    protected function casts(): array
    {
        return [
            'status' => MonitorStatus::class,
            'uptime_percentage' => 'float',
            'next_check_at' => 'datetime',
            'last_checked_at' => 'datetime',
        ];
    }

    #[Scope]
    public function due(Builder $query, ?CarbonInterface $date = null): Builder
    {
        return $query->where(function ($query) use ($date) {
            $query
                ->whereNotNull('next_check_at')
                ->where('next_check_at', '<=', $date ?? now());
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(MonitorCheck::class);
    }
}
