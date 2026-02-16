<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Http\Request;

class BroadcastingController extends Controller
{
    public function authenticate(Request $request, Broadcaster $broadcaster)
    {
        return $broadcaster->auth($request);
    }
}