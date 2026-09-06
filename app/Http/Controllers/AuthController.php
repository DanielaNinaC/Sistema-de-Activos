<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Rules\ValidEmailDomain;

class AuthController extends Controller
{
    /**
     * Muestra la pantalla de inicio de sesión.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        // Generar o asegurar que exista un código de cache activo
        $sessionKey = 'login_cache_code_' . session()->getId();
        if (!Cache::has($sessionKey)) {
            $this->generateNewCacheCode();
        }

        return view('auth.login');
    }

    /**
     * Genera un nuevo código de seguridad en cache y retorna la imagen SVG dinámica.
     */
    public function generateCaptchaImage(Request $request)
    {
        $code = $this->generateNewCacheCode();
        $svg = $this->renderCaptchaSvg($code);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Helper para generar y guardar el código de seguridad en Cache.
     */
    private function generateNewCacheCode(): string
    {
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $code = '';
        $max = strlen($characters) - 1;
        for ($i = 0; $i < 5; $i++) {
            $code .= $characters[random_int(0, $max)];
        }

        $sessionKey = 'login_cache_code_' . session()->getId();
        Cache::put($sessionKey, $code, now()->addMinutes(15));

        return $code;
    }

    /**
     * Genera el SVG visual para el código de seguridad con distorsión y diseño estilizado.
     */
    private function renderCaptchaSvg(string $code): string
    {
        $width = 170;
        $height = 46;
        $chars = str_split($code);

        $charElements = '';
        $xOffset = 22;
        $colors = ['#0f172a', '#3b82f6', '#4f46e5', '#0d9488', '#1d4ed8', '#6366f1'];

        foreach ($chars as $char) {
            $angle = random_int(-14, 14);
            $yPos = 30 + random_int(-3, 3);
            $fontSize = random_int(22, 25);
            $color = $colors[array_rand($colors)];
            
            $charElements .= "<text x=\"{$xOffset}\" y=\"{$yPos}\" font-family=\"'Segoe UI', Roboto, sans-serif\" font-size=\"{$fontSize}\" font-weight=\"bold\" fill=\"{$color}\" transform=\"rotate({$angle}, {$xOffset}, {$yPos})\">{$char}</text>";
            $xOffset += 26;
        }

        $noiseLines = '';
        $lineColors = ['#94a3b8', '#cbd5e1', '#a5b4fc', '#99f6e4'];
        for ($i = 0; $i < 3; $i++) {
            $x1 = random_int(5, 30);
            $y1 = random_int(5, 40);
            $x2 = random_int(130, 165);
            $y2 = random_int(5, 40);
            $lc = $lineColors[array_rand($lineColors)];
            $noiseLines .= "<line x1=\"{$x1}\" y1=\"{$y1}\" x2=\"{$x2}\" y2=\"{$y2}\" stroke=\"{$lc}\" stroke-width=\"1.5\" stroke-dasharray=\"4,3\" opacity=\"0.8\" />";
        }

        $dots = '';
        for ($i = 0; $i < 20; $i++) {
            $cx = random_int(5, 165);
            $cy = random_int(5, 40);
            $r = random_int(1, 2);
            $dots .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$r}\" fill=\"#94a3b8\" opacity=\"0.5\" />";
        }

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border-radius: 0.75rem; border: 1px solid #cbd5e1; user-select: none;">
    {$noiseLines}
    {$dots}
    {$charElements}
</svg>
SVG;
    }

    /**
     * Procesa la solicitud de inicio de sesión.
     */
    public function login(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'contrasena' => 'required|string',
            'codigo_cache' => 'required|string',
        ], [
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email' => 'Ingrese una dirección de correo electrónico válida.',
            'contrasena.required' => 'La contraseña es obligatoria.',
            'codigo_cache.required' => 'El código de seguridad es obligatorio.',
        ]);

        // 1. Validar el código de seguridad (captcha/cache)
        $sessionKey = 'login_cache_code_' . session()->getId();
        $cacheCode = Cache::get($sessionKey);

        if ($cacheCode === null || $cacheCode === '') {
            $this->generateNewCacheCode();
            return back()->withErrors([
                'codigo_cache' => 'El código de seguridad ha expirado. Ingresa el nuevo código generado.',
            ])->withInput($request->only('correo'));
        }

        if (strtoupper(trim((string) $cacheCode)) !== strtoupper(trim((string) $request->codigo_cache))) {
            $this->generateNewCacheCode();
            return back()->withErrors([
                'codigo_cache' => 'El código de seguridad ingresado es incorrecto.',
            ])->withInput($request->only('correo'));
        }

        // 2. Verificar existencia del usuario en la base de datos
        $usuario = \App\Models\Usuario::where('correo', $request->correo)->first();

        if (!$usuario) {
            $this->generateNewCacheCode();
            return back()->withErrors([
                'correo' => 'No existe ninguna cuenta registrada con este correo electrónico.',
            ])->withInput($request->only('correo'));
        }

        // 3. Verificar estado del usuario (activo / inactivo)
        if (isset($usuario->estado) && (int)$usuario->estado === 0) {
            $this->generateNewCacheCode();
            return back()->withErrors([
                'correo' => 'Tu cuenta se encuentra desactivada. Contacta al administrador del sistema.',
            ])->withInput($request->only('correo'));
        }

        // 4. Intentar autenticación con credenciales
        $credentials = [
            'correo' => $request->correo,
            'password' => $request->contrasena,
        ];

        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            $this->generateNewCacheCode();
            return back()->withErrors([
                'contrasena' => 'La contraseña ingresada es incorrecta.',
            ])->withInput($request->only('correo'));
        }

        // Éxito: limpiar el código de la cache y regenerar sesión
        Cache::forget($sessionKey);
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        return redirect()->route('dashboard');
    }

    /**
     * Procesa el registro de un nuevo usuario.
     */
    public function register(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'correo' => ['required', 'email', 'max:255', 'unique:usuarios,correo', new ValidEmailDomain()],
            'contrasena' => 'required|string|min:6|confirmed',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido.required' => 'El apellido es obligatorio.',
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email' => 'Ingrese un correo electrónico válido.',
            'correo.unique' => 'Este correo electrónico ya se encuentra registrado.',
            'contrasena.required' => 'La contraseña es obligatoria.',
            'contrasena.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'contrasena.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $nombre = trim($request->nombre);
        $apellido = trim($request->apellido);

        // Separación inteligente de respaldo si apellido estuviese vacío pero el nombre contuviese espacios
        if (empty($apellido) && str_contains($nombre, ' ')) {
            $parts = explode(' ', $nombre, 2);
            $nombre = $parts[0];
            $apellido = $parts[1];
        }

        // Crear el usuario con estado deshabilitado (0) y rol por defecto (2 = Soporte)
        \App\Models\Usuario::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'correo' => $request->correo,
            'contrasena' => \Illuminate\Support\Facades\Hash::make($request->contrasena),
            'fk_id_rol' => 2,
            'estado' => 0,
        ]);

        $this->generateNewCacheCode();

        return redirect()->route('login')->with('registered_success', 'Usted ha sido registrado en el Sistema de Activos, espere a que el administrador habilite su cuenta.');
    }

    /**
     * Cierra la sesión activa.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Has cerrado sesión correctamente.');
    }
}
