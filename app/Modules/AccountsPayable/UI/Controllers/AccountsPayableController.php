<?php

namespace App\Modules\AccountsPayable\UI\Controllers;

use App\Http\Controllers\Controller;

class AccountsPayableController extends Controller
{
    public function index()
    {
        return view('accounts-payable::index');
    }
}

