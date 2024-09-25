<?php

namespace App\Http\Controllers\Pos;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PosController extends Controller
{
    public function index()
    {
        return Inertia::render('Pos/Pos');
    }
}
