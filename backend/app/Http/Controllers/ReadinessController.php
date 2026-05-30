<?php

namespace App\Http\Controllers;

use App\Support\DeploymentHealthChecker;
use Illuminate\Http\JsonResponse;

class ReadinessController extends Controller
{
    public function __invoke(DeploymentHealthChecker $checker): JsonResponse
    {
        $report = $checker->check();

        return response()->json($report, $report['status'] === 'fail' ? 503 : 200);
    }
}
