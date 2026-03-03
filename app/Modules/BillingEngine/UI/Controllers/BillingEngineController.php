<?php

namespace App\Modules\BillingEngine\UI\Controllers;

use App\Http\Controllers\Controller;

class BillingEngineController extends Controller
{
    public function index()
    {
        return view('billing-engine::index');
    }
}

