<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Log;

class LogController extends Controller
{
    public function index()
    {
        $logs = Log::with('usuario')->orderByDesc('fecha_hora')->paginate(25);
        return view('admin.logs.index', compact('logs'));
    }
}
