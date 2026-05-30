<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\PaymentLedger;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $totalClients    = $user->clients()->count();
        $activeProjects  = $user->projects()->where('status', 'Active')->count();
        $pendingInvoices = $user->invoices()->whereIn('status', ['Draft', 'Sent'])->count();
        $totalRevenue = app(PaymentLedger::class)->netCapturedForUser($user->id);

        return response()->json([
            'metrics' => [
                'total_clients'    => $totalClients,
                'active_projects'  => $activeProjects,
                'pending_invoices' => $pendingInvoices,
                'total_revenue'    => (float) $totalRevenue,
            ],
            'recent_activities' => [],
        ]);
    }
}
