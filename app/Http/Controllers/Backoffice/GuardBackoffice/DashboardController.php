<?php

namespace App\Http\Controllers\Backoffice\GuardBackoffice;
use App\Http\Controllers\Controller;
use App\Models\System\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{

    public string $publicTitle = 'Dashboard';

    public function index(Request $request)
    {
        $publicTitle = $this->publicTitle;

        return Inertia::render('GuardBackoffice/Dashboard/Index', [
            'publicTitle' => $publicTitle,
        ]);
    }


    public function test()
    {
        $publicTitle = $this->publicTitle;
        return Inertia::render('GuardBackoffice/Dashboard/Test', compact('publicTitle'));
    }

}
