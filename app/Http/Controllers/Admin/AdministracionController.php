<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AdministracionController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.usuarios.create');
    }
}
