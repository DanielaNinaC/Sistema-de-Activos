<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InicioController extends Controller
{
    /**
     * Muestra el portal principal con las tarjetas de sistemas.
     */
    public function index()
    {
        $usuario = auth()->user();

        return view('inicio.index', compact('usuario'));
    }
}
