<?php

declare(strict_types=1);

namespace App\Http\Controllers\Monitor;

use App\Models\Monitor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Monitor\MonitorHistoryRequest;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MonitorHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(MonitorHistoryRequest $request, Monitor $monitor): ResourceCollection
    {
        $perPage = $request->validated('per_page', 15);

        $page = $request->validated('page', 1);

        return $monitor->checks()
            ->latest('checked_at')
            ->paginate(perPage: $perPage, page: $page)
            ->toResourceCollection()
            ->additional([
                'message' => __('monitors.history.success'),
            ]);
    }
}
