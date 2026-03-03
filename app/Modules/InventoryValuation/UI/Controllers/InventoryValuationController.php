<?php

namespace App\Modules\InventoryValuation\UI\Controllers;

use App\Http\Controllers\Controller;

class InventoryValuationController extends Controller
{
    public function index()
    {
        return view('inventory-valuation::index');
    }
}

