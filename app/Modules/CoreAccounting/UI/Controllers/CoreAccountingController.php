<?php

namespace App\Modules\CoreAccounting\UI\Controllers;

use App\Http\Controllers\Controller;

class CoreAccountingController extends Controller
{
    public function index()
    {
        return view('core-accounting::index');
    }
}

