<?php

namespace App\Http\Controllers;

use App\Services\Visits\VisitStatsBuilder;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(VisitStatsBuilder $stats): View
    {
        return view('dashboard', [
            'stats' => $stats->build(),
        ]);
    }
}
