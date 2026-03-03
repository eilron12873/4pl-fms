<?php

namespace App\Modules\FinancialReporting\UI\Controllers;

use App\Http\Controllers\Controller;

class FinancialReportingController extends Controller
{
    public function index()
    {
        return view('financial-reporting::index');
    }
}

