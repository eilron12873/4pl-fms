<?php

namespace App\Modules\Treasury\UI\Controllers;

use App\Http\Controllers\Controller;

class TreasuryController extends Controller
{
    public function index()
    {
        return view('treasury::index');
    }
}

