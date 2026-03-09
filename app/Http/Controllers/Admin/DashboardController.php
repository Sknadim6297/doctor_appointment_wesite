<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Mock data for dashboard statistics
        $stats = [
            'enrollment_doctors' => 954,
            'money_receipts' => 3711,
            'doctor_cases' => 35,
            'lapse_list' => 428,
            'premium_amount' => 777,
            'doctor_posts' => 2653,
        ];

        $progress = [
            'with_documents' => ['count' => 38, 'total' => 954],
            'with_cases' => ['count' => 27, 'total' => 954],
            'with_premium' => ['count' => 777, 'total' => 954],
            'with_photo' => ['count' => 889, 'total' => 954],
            'renew_expired' => ['count' => 428, 'total' => 954],
        ];

        $plans = [
            'normal' => 119,
            'high' => 59,
            'combo' => 777,
        ];

        $payments = [
            'this_year' => 1313774,
            'previous_year' => 3628502,
            'all_time' => 25229792,
        ];

        $latest_doctors = [
            ['name' => 'DR. ARISTA LAHIRI', 'date' => '29/07/2015', 'image' => null],
            ['name' => 'DR. PALLAB BASU', 'date' => '26/08/2015', 'image' => null],
            ['name' => 'DR. MAGIMAIRAJ DAVID JAYAPAL', 'date' => '11/11/2015', 'image' => null],
        ];

        return view('admin.dashboard', compact('stats', 'progress', 'plans', 'payments', 'latest_doctors'));
    }
}

