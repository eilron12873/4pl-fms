<?php

namespace App\Modules\CostingEngine\UI\Controllers;

use App\Http\Controllers\Controller;

class CostingEngineController extends Controller
{
    public function index()
    {
        return view('costing-engine::index');
    }
}

