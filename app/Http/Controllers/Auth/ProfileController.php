<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;

class ProfileController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Request $request): UserResource
    {
        return $request->user()
            ->toResource()
            ->additional([
                'message' => __('auth.profile.success'),
            ]);
    }
}
