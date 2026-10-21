<?php

namespace App\Http\Controllers;

use App\Services\DashboardDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardDataService $dashboard): View
    {
        $requestedYear = $request->query('tahun');
        $reportYear = $requestedYear === 'all'
            ? null
            : (filter_var($requestedYear, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2000, 'max_range' => 2100]]) ?: now()->year);

        return view('dashboard.index', [
            'dashboard' => $dashboard->data($reportYear),
        ]);
    }
}
