/**
 * Convierte un <select data-searchable> con muchas opciones en un combo con
 * caja de búsqueda. El <select> original se mantiene oculto en el DOM (mismo
 * name, mismos <option data-*>, mismos eventos "change") para no romper el
 * resto de scripts de cada página ni el envío normal del formulario.
 */

const READY_ATTR = 'data-searchable-ready';

export function enhanceSearchableSelects(root = document) {
    root.querySelectorAll(`select[data-searchable]:not([${READY_ATTR}])`).forEach(enhanceSelect);
}

function enhanceSelect(select) {
    select.setAttribute(READY_ATTR, 'true');

    const placeholder = select.getAttribute('data-search-placeholder') || 'Buscar...';
    const baseClass = select.getAttribute('class') ||
        'w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition';

    const wrapper = document.createElement('div');
    wrapper.className = 'relative';

    const input = document.createElement('input');
    input.type = 'text';
    input.autocomplete = 'off';
    input.placeholder = placeholder;
    input.className = baseClass;
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('aria-autocomplete', 'list');
    if (select.hasAttribute('required')) {
        input.required = true;
        select.removeAttribute('required');
    }

    const list = document.createElement('ul');
    list.className = 'absolute z-30 mt-1 w-full max-h-64 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg hidden';
    list.setAttribute('role', 'listbox');

    select.classList.add('hidden');
    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(input);
    wrapper.appendChild(list);
    wrapper.appendChild(select);

    let highlighted = -1;

    // option.hidden permite que otro script filtre dinámicamente qué opciones
    // son elegibles (ej. modos de falla filtrados por la categoría del activo).
    const optionsData = () => Array.from(select.options).filter((o) => o.value !== '' && !o.hidden);

    function renderList(term) {
        const needle = (term || '').trim().toLowerCase();
        list.innerHTML = '';
        highlighted = -1;

        const matches = optionsData().filter((o) => o.text.toLowerCase().includes(needle));

        if (matches.length === 0) {
            const li = document.createElement('li');
            li.className = 'px-3.5 py-2.5 text-sm text-slate-400 italic';
            li.textContent = 'Sin resultados';
            list.appendChild(li);
            return;
        }

        matches.forEach((opt) => {
            const li = document.createElement('li');
            li.className = 'px-3.5 py-2 text-sm text-slate-700 cursor-pointer hover:bg-blue-50 hover:text-blue-700';
            li.textContent = opt.text.trim();
            li.dataset.value = opt.value;
            li.setAttribute('role', 'option');
            li.addEventListener('mousedown', (e) => {
                e.preventDefault(); // evita perder el foco antes del click
                selectOption(opt);
            });
            list.appendChild(li);
        });
    }

    function openList(term) {
        renderList(term !== undefined ? term : '');
        list.classList.remove('hidden');
        input.setAttribute('aria-expanded', 'true');
    }

    function closeList() {
        list.classList.add('hidden');
        input.setAttribute('aria-expanded', 'false');
    }

    function selectOption(opt) {
        select.value = opt.value;
        input.value = opt.text.trim();
        closeList();
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function syncFromSelect() {
        const opt = select.options[select.selectedIndex];
        input.value = opt && opt.value !== '' ? opt.text.trim() : '';
    }

    function updateHighlight(items) {
        items.forEach((li, i) => {
            li.classList.toggle('bg-blue-50', i === highlighted);
            li.classList.toggle('text-blue-700', i === highlighted);
        });
        if (items[highlighted]) items[highlighted].scrollIntoView({ block: 'nearest' });
    }

    input.addEventListener('focus', () => {
        input.select();
        openList('');
    });

    input.addEventListener('input', () => openList(input.value));

    input.addEventListener('keydown', (e) => {
        if (list.classList.contains('hidden') && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
            openList(input.value);
            return;
        }

        const items = Array.from(list.querySelectorAll('li[data-value]'));
        if (items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            highlighted = Math.min(highlighted + 1, items.length - 1);
            updateHighlight(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            highlighted = Math.max(highlighted - 1, 0);
            updateHighlight(items);
        } else if (e.key === 'Enter') {
            if (highlighted >= 0 && items[highlighted]) {
                e.preventDefault();
                const opt = optionsData().find((o) => o.value === items[highlighted].dataset.value);
                if (opt) selectOption(opt);
            }
        } else if (e.key === 'Escape') {
            closeList();
        }
    });

    input.addEventListener('blur', () => {
        // Se retrasa para permitir que el mousedown de una opción se procese primero
        setTimeout(() => {
            syncFromSelect();
            closeList();
        }, 100);
    });

    document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) closeList();
    });

    select.addEventListener('change', syncFromSelect);

    syncFromSelect();
}
