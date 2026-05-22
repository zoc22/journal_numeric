<?php

namespace App\Http\Controllers;

use Modules\Creer\Models\Creer;

class ReadController extends Controller
{
    public function index()
    {
        $users = Creer::all();

        return view('welcome', compact('users'));
    }
}
