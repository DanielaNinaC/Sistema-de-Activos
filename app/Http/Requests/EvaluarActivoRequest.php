<?php

namespace App\Http\Requests;

use App\Models\ActivoTecnologico;
use App\Models\ModoFalla;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Valida la ejecución del motor FMEA sobre un activo, con selección granular
 * de modos de falla.
 *
 * El contrato es: `modo_evaluacion = 'todos'` (por defecto, evalúa el catálogo
 * completo de la categoría) o `modo_evaluacion = 'especificos'` acompañado de
 * `modos_falla_ids`.
 */
class EvaluarActivoRequest extends FormRequest
{
    public const MODO_TODOS = 'todos';
    public const MODO_ESPECIFICOS = 'especificos';

    public function authorize(): bool
    {
        // La autorización la resuelven los middleware de la ruta
        // (auth + modulo:riesgos); aquí sólo se valida el payload.
        return true;
    }

    /**
     * Si el formulario no envía el modo (por ejemplo, una llamada antigua),
     * se asume 'todos' para no alterar el comportamiento previo.
     */
    protected function prepareForValidation(): void
    {
        if (!$this->filled('modo_evaluacion')) {
            $this->merge(['modo_evaluacion' => self::MODO_TODOS]);
        }
    }

    public function rules(): array
    {
        return [
            'fk_id_activo' => 'required|integer|exists:activos_tecnologicos,id_activo',
            'modo_evaluacion' => 'required|string|in:' . self::MODO_TODOS . ',' . self::MODO_ESPECIFICOS,
            'modos_falla_ids' => 'required_if:modo_evaluacion,' . self::MODO_ESPECIFICOS . '|array|min:1',
            'modos_falla_ids.*' => 'integer|exists:modos_falla,id_modo_falla',
        ];
    }

    public function messages(): array
    {
        return [
            'modos_falla_ids.required_if' => 'Selecciona al menos un modo de falla, o elige evaluar todos.',
            'modos_falla_ids.min' => 'Selecciona al menos un modo de falla, o elige evaluar todos.',
        ];
    }

    /**
     * Comprobación de pertenencia: todos los modos indicados deben ser de la
     * categoría del activo. Evita que un request manipulado haga evaluar un
     * activo contra modos de falla de otra categoría.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->modoSeleccionEspecifica() === false) {
                    return;
                }

                $ids = array_map('intval', (array) $this->input('modos_falla_ids', []));
                if ($ids === [] || $validator->errors()->hasAny(['fk_id_activo', 'modos_falla_ids'])) {
                    return;
                }

                $activo = ActivoTecnologico::find($this->input('fk_id_activo'));
                if (!$activo) {
                    return;
                }

                $idsDeLaCategoria = ModoFalla::where('fk_id_categoria', $activo->fk_id_categoria)
                    ->pluck('id_modo_falla')
                    ->all();

                $ajenos = array_diff($ids, $idsDeLaCategoria);

                if ($ajenos !== []) {
                    $validator->errors()->add(
                        'modos_falla_ids',
                        'Uno o más modos de falla seleccionados no pertenecen a la categoría del activo.'
                    );
                }
            },
        ];
    }

    /** ¿El usuario pidió evaluar sólo algunos modos? */
    public function modoSeleccionEspecifica(): bool
    {
        return $this->input('modo_evaluacion') === self::MODO_ESPECIFICOS;
    }

    /**
     * IDs a evaluar, o null para evaluar el catálogo completo de la categoría
     * (que es lo que MotorFmeaService::evaluarActivo() interpreta por defecto).
     */
    public function idsModosFalla(): ?array
    {
        if (!$this->modoSeleccionEspecifica()) {
            return null;
        }

        return array_map('intval', (array) $this->input('modos_falla_ids', []));
    }
}
