// Muestra los errores de validación de Laravel junto al campo que los causó.
// El servidor los entrega como JSON en data-errores de #errores-validacion, dentro
// de #main-content (ver layouts/principal)
// y se vuelve a ejecutar tras cada navegación (turbo:load).
(function () {
    const ID_DATOS = 'errores-validacion';
    const MARCA = 'data-error-campo';

    function nombresPosibles(clave) {
        const partes = clave.split('.');
        const nombres = [clave, clave + '[]'];

        if (partes.length > 1) {
            nombres.push(partes[0] + partes.slice(1).map((p) => '[' + p + ']').join(''));
            nombres.push(partes[0] + '[]');
        }

        return nombres;
    }

    function buscarCampo(contenedor, clave) {
        const candidatos = [];

        nombresPosibles(clave).forEach((nombre) => {
            const seguro = nombre.replace(/"/g, '\\"');
            contenedor.querySelectorAll('[name="' + seguro + '"]').forEach((el) => candidatos.push(el));
            // Campo visible que representa a uno oculto (p. ej. el nombre que llena el id del usuario).
            contenedor.querySelectorAll('[data-error-de="' + seguro + '"]').forEach((el) => candidatos.push(el));
        });

        // Un input type=hidden no tiene dónde mostrar el mensaje.
        return candidatos.find((el) => el.type !== 'hidden') || null;
    }

    // Dónde resaltar y dónde insertar el mensaje según el tipo de control.
    function ancla(campo) {
        const padre = campo.parentElement;

        // Select con buscador: el <select> real está oculto dentro de un .relative con un input visible.
        if (campo.classList.contains('hidden') && padre && padre.classList.contains('relative')) {
            return { visible: padre.querySelector('input'), despuesDe: padre };
        }

        if (campo.type === 'checkbox' || campo.type === 'radio') {
            return { visible: null, despuesDe: campo.closest('label') || campo };
        }

        if (padre && (padre.classList.contains('relative') || padre.classList.contains('flex'))) {
            return { visible: campo, despuesDe: padre };
        }

        return { visible: campo, despuesDe: campo };
    }

    function marcar(campo, mensaje) {
        const { visible, despuesDe } = ancla(campo);
        let aviso = null;

        // El resaltado lo da la regla [aria-invalid="true"] de app.css.
        if (visible) {
            visible.setAttribute('aria-invalid', 'true');
        }

        // Si la vista ya imprimió el mensaje justo después del campo (@error), no se repite.
        const siguiente = despuesDe.nextElementSibling;
        if (!siguiente || siguiente.textContent.trim() !== mensaje) {
            aviso = document.createElement('p');
            aviso.setAttribute(MARCA, '');
            aviso.setAttribute('role', 'alert');
            aviso.className = 'mt-1 text-xs font-medium text-rose-600';
            aviso.textContent = mensaje;
            despuesDe.insertAdjacentElement('afterend', aviso);
        }

        // Al corregir el campo se retira el error.
        const limpiar = () => {
            if (aviso) aviso.remove();
            if (visible) {
                visible.removeAttribute('aria-invalid');
            }
        };
        [campo, visible].filter(Boolean).forEach((el) => {
            el.addEventListener('input', limpiar, { once: true });
            el.addEventListener('change', limpiar, { once: true });
        });

        return visible || campo;
    }

    // Reabre el modal que contiene el campo. Los modales cuyo formulario no tiene
    // destino propio (lo fija un script al abrirlos para una fila concreta) no se
    // pueden reabrir de forma segura.
    function revelar(campo) {
        const capa = campo.closest('.fixed.inset-0');

        if (!capa || !capa.classList.contains('hidden')) {
            return true;
        }

        const formulario = campo.closest('form');
        if (!formulario || !formulario.getAttribute('action')) {
            return false;
        }

        capa.classList.remove('hidden');
        const tarjeta = (capa.id && document.getElementById(capa.id + 'Card')) || capa.firstElementChild;
        if (tarjeta) {
            tarjeta.classList.remove('scale-95', 'opacity-0');
            tarjeta.classList.add('scale-100', 'opacity-100');
        }

        return true;
    }

    // Respaldo para el caso poco común de un error sin campo visible donde mostrarse
    // (p. ej. un dato oculto o un modal que no se puede reabrir): una línea de texto
    // rojo por mensaje, sin caja ni título, para que no se pierda en silencio.
    function mensajesSinCampo(contenedor, mensajes) {
        mensajes.slice().reverse().forEach((mensaje) => {
            const linea = document.createElement('p');
            linea.setAttribute(MARCA, '');
            linea.setAttribute('role', 'alert');
            linea.className = 'mb-2 text-xs font-medium text-rose-600';
            linea.textContent = mensaje;
            contenedor.insertAdjacentElement('afterbegin', linea);
        });
    }

    function aplicar() {
        const datos = document.getElementById(ID_DATOS);
        const contenedor = document.getElementById('main-content');
        if (!datos || !contenedor) return;

        // Cada respuesta del servidor trae su propio elemento de datos: se procesa una sola vez.
        if (datos.dataset.procesado) return;
        datos.dataset.procesado = '1';

        document.querySelectorAll('[' + MARCA + ']').forEach((el) => el.remove());

        let errores;
        try {
            errores = JSON.parse(datos.dataset.errores);
        } catch (e) {
            return;
        }

        const sinCampo = [];
        let primero = null;

        Object.keys(errores).forEach((clave) => {
            const mensaje = errores[clave][0];
            const campo = buscarCampo(contenedor, clave);

            if (!campo || !revelar(campo)) {
                sinCampo.push(mensaje);
                return;
            }

            const marcado = marcar(campo, mensaje);
            primero = primero || marcado;
        });

        if (sinCampo.length) {
            mensajesSinCampo(contenedor, sinCampo);
        }

        if (primero) {
            primero.scrollIntoView({ block: 'center', behavior: 'smooth' });
            if (typeof primero.focus === 'function') primero.focus({ preventScroll: true });
        }
    }

    // Se difiere un instante para que otros scripts (p. ej. los selects con
    // buscador) terminen de armar el DOM antes de ubicar los campos.
    function programar() {
        setTimeout(aplicar, 60);
    }

    // turbo-nav reemplaza el contenido sin avisar al enviar formularios (solo
    // dispara turbo:load al navegar por enlaces), así que se observa #main-content:
    // cuando llega contenido nuevo con errores, se pintan.
    function observar() {
        const principal = document.getElementById('main-content');
        if (!principal) return;

        new MutationObserver((mutaciones) => {
            const llegaronErrores = mutaciones.some((m) =>
                Array.from(m.addedNodes).some((n) => n.nodeType === 1 && n.id === ID_DATOS)
            );
            if (llegaronErrores) programar();
        }).observe(principal, { childList: true });
    }

    function iniciar() {
        observar();
        programar();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
    document.addEventListener('turbo:load', programar);
})();
