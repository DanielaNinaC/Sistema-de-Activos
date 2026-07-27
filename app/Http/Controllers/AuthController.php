<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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
        Cache::put('login_cache_code', $code, now()->addMinutes(15));

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
            'correo.email' => 'Ingrese una dirección de correo válida.',
            'contrasena.required' => 'La contraseña es obligatoria.',
            'codigo_cache.required' => 'El código de seguridad en cache es obligatorio.',
        ]);

        $credentials = [
            'correo' => $request->correo,
            'password' => $request->contrasena,
        ];

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $sessionKey = 'login_cache_code_' . session()->getId();
            $cacheCode = Cache::get($sessionKey) ?? Cache::get('login_cache_code');

            if ($cacheCode === null || $cacheCode === '') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $this->generateNewCacheCode();

                return back()->withErrors([
                    'codigo_cache' => 'No hay un código de cache disponible o ha expirado. Intenta nuevamente.',
                ])->onlyInput('correo');
            }

            if (strtoupper(trim((string) $cacheCode)) !== strtoupper(trim((string) $request->codigo_cache))) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $this->generateNewCacheCode();

                return back()->withErrors([
                    'codigo_cache' => 'El código de seguridad / cache ingresado es incorrecto.',
                ])->onlyInput('correo');
            }

            // Éxito: limpiar el código de la cache y regenerar sesión
            Cache::forget($sessionKey);
            Cache::forget('login_cache_code');
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))
                ->with('success', '¡Bienvenido de nuevo!');
        }

        // Si fallan las credenciales, regenerar el código para seguridad
        $this->generateNewCacheCode();

        return back()->withErrors([
            'correo' => 'Las credenciales ingresadas no coinciden con nuestros registros.',
        ])->onlyInput('correo');
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
