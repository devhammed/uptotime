<?php

declare(strict_types=1);

namespace App\Http\Controllers\Monitor;

use App\Http\Controllers\Controller;
use App\Http\Resources\MonitorResource;
use App\Http\Requests\Monitor\MonitorListRequest;
use App\Http\Requests\Monitor\CreateMonitorRequest;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MonitorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(MonitorListRequest $request): ResourceCollection
    {
        $perPage = $request->validated('per_page', 15);

        $page = $request->validated('page', 1);

        return $request->user()
            ->monitors()
            ->paginate(perPage: $perPage, page: $page)
            ->toResourceCollection()
            ->additional([
                'message' => __('monitors.index.success'),
            ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateMonitorRequest $request): MonitorResource
    {
        $url = $request->validated('url');

        $checkInterval = $request->validated('check_interval', 5);

        $threshold = $request->validated('threshold', 3);

        $monitor = $request->user()->monitors()->create([
            'url' => $url,
            'check_interval' => $checkInterval,
            'threshold' => $threshold,
            'next_check_at' => now()->addMinutes($checkInterval),
        ]);

        return $monitor
            ->toResource()
            ->additional([
                'message' => __('monitors.store.success'),
            ]);
    }
}
