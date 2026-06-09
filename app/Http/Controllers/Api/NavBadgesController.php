<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Nav\NavBadgesService;
use Illuminate\Http\Request;

class NavBadgesController extends Controller
{
    use ApiResponses;

    public function __invoke(Request $request, NavBadgesService $badges)
    {
        return $this->ok($badges->forUser($request->user()));
    }
}
