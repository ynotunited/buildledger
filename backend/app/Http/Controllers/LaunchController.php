<?php

namespace App\Http\Controllers;

use App\Support\InviteModeManager;
use Illuminate\Http\Request;

class LaunchController extends Controller
{
    public function show(Request $request)
    {
        $manager = app(InviteModeManager::class);

        return response()->json([
            'invite_only' => $manager->isInviteOnly(),
            'source' => $manager->source(),
            'updated_at' => $manager->updatedAt(),
        ]);
    }
}
