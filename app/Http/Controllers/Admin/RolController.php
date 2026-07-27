<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rol;

class RolController extends Controller
{
    public function index()
    {
        $roles = Rol::orderBy('nombre_rol')->get();
        return view('admin.roles.index', compact('roles'));
    }
}
