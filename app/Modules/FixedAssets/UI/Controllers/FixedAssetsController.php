<?php

namespace App\Modules\FixedAssets\UI\Controllers;

use App\Http\Controllers\Controller;

class FixedAssetsController extends Controller
{
    public function index()
    {
        return view('fixed-assets::index');
    }
}

