<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('farmer.dashboard');
    }
}