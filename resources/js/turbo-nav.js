/**
 * Turbo Navigation — SPA-like navigation for Laravel Blade
 * 
 * Intercepts link clicks within the sidebar, fetches the new page via AJAX,
 * and swaps ONLY the main content area. The sidebar, header, and all shell
 * elements remain untouched — no re-render, no flicker, instant feel.
 * 
 * Also prefetches links on hover for even faster perceived navigation.
 */
import { enhanceSearchableSelects } from './searchable-select.js';

(function () {
    'use strict';

    const CONTENT_SELECTOR = '#main-content';
    const SIDEBAR_SELECTOR = 'aside';
    const PREFETCH_DELAY = 65; // ms before prefetching on hover

    // --- CSRF Token helper ---
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function updateCsrfToken(doc) {
        const newMeta = doc.querySelector('meta[name="csrf-token"]');
        if (newMeta) {
            const currentMeta = document.querySelector('meta[name="csrf-token"]');
            if (currentMeta) {
                currentMeta.setAttribute('content', newMeta.getAttribute('content'));
            }
            // Also update any CSRF hidden inputs in the current page
            document.querySelectorAll('input[name="_token"]').forEach(input => {
                input.value = newMeta.getAttribute('content');
            });
        }
    }

    // --- In-memory page cache ---
    const pageCache = new Map();
    const MAX_CACHE = 20;

    // --- Progress bar ---
    let progressBar = null;
    function createProgressBar() {
        progressBar = document.createElement('div');
        progressBar.id = 'turbo-progress';
        progressBar.innerHTML = '<div class="turbo-progress-bar"></div>';
        document.body.appendChild(progressBar);
    }

    function showProgress() {
        if (!progressBar) createProgressBar();
        const bar = progressBar.querySelector('.turbo-progress-bar');
        bar.style.transition = 'none';
        bar.style.width = '0%';
        bar.style.opacity = '1';
        // Force reflow
        bar.offsetWidth;
        bar.style.transition = 'width 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
        bar.style.width = '80%';
        progressBar.classList.add('active');
    }

    function completeProgress() {
        if (!progressBar) return;
        const bar = progressBar.querySelector('.turbo-progress-bar');
        bar.style.transition = 'width 0.15s ease-out, opacity 0.3s ease 0.15s';
        bar.style.width = '100%';
        setTimeout(() => {
            bar.style.opacity = '0';
            progressBar.classList.remove('active');
        }, 150);
    }

    // --- Core navigation ---
    async function navigateTo(url, pushState = true) {
        // Don't navigate to the same page
        if (url === window.location.href) return;

        showProgress();

        try {
            let html;
            if (pageCache.has(url)) {
                html = pageCache.get(url);
            } else {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'Accept': 'text/html',
                    },
                    credentials: 'same-origin',
                });

                // Handle 419 (CSRF token mismatch) / 401 — session expired
                if (response.status === 419 || response.status === 401) {
                    pageCache.clear();
                    window.location.href = '/login?inactividad=1';
                    return;
                }

                if (!response.ok) {
                    // Fallback to normal navigation on error
                    window.location.href = url;
                    return;
                }

                // If redirected to login (session expired), do full navigation
                if (response.redirected && response.url.includes('/login')) {
                    pageCache.clear();
                    window.location.href = response.url;
                    return;
                }

                html = await response.text();
                
                // Cache the page
                if (pageCache.size >= MAX_CACHE) {
                    const firstKey = pageCache.keys().next().value;
                    pageCache.delete(firstKey);
                }
                pageCache.set(url, html);
            }

            // Parse the new HTML and extract the main content
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // Keep CSRF token fresh from the new page
            updateCsrfToken(doc);

            const newContent = doc.querySelector(CONTENT_SELECTOR);
            const currentContent = document.querySelector(CONTENT_SELECTOR);

            if (newContent && currentContent) {
                // Swap content with a subtle fade
                currentContent.style.opacity = '0';
                
                setTimeout(() => {
                    currentContent.innerHTML = newContent.innerHTML;
                    currentContent.style.opacity = '1';
                    
                    // Scroll to top of content
                    currentContent.scrollTop = 0;

                    // Execute any inline scripts in the new content
                    currentContent.querySelectorAll('script').forEach(oldScript => {
                        const newScript = document.createElement('script');
                        if (oldScript.src) {
                            newScript.src = oldScript.src;
                        } else {
                            newScript.textContent = oldScript.textContent;
                        }
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    });

                    enhanceSearchableSelects(currentContent);

                    // Dispatch lifecycle events for components on the newly swapped page
                    document.dispatchEvent(new CustomEvent('turbo:load', { detail: { url } }));
                    document.dispatchEvent(new CustomEvent('page:load', { detail: { url } }));
                }, 80);

                // Update the page title
                const newTitle = doc.querySelector('title');
                if (newTitle) {
                    document.title = newTitle.textContent;
                }

                // Update browser history
                if (pushState) {
                    history.pushState({ turboNav: true }, '', url);
                }

                // Update active link styling in sidebar
                updateActiveLink(url);
            } else {
                // Fallback: full page navigation
                window.location.href = url;
            }
        } catch (err) {
            console.warn('Turbo nav failed, falling back:', err);
            window.location.href = url;
        } finally {
            completeProgress();
        }
    }

    // --- Active link highlighting ---
    function updateActiveLink(url) {
        const sidebar = document.querySelector(SIDEBAR_SELECTOR);
        if (!sidebar) return;

        const path = new URL(url, window.location.origin).pathname;

        sidebar.querySelectorAll('a').forEach(link => {
            const linkPath = new URL(link.href, window.location.origin).pathname;
            if (linkPath === path) {
                link.classList.add('bg-white/10', 'text-white', 'font-medium');
            } else {
                link.classList.remove('bg-white/10', 'text-white', 'font-medium');
            }
        });

        // Auto-open the correct submenu section
        const parents = sidebar.querySelectorAll('.has-submenu');
        parents.forEach(p => {
            const key = p.getAttribute('data-submenu-key');
            const submenu = p.querySelector('.submenu');
            const arrow = p.querySelector('.submenu-arrow');
            if (!key || !submenu) return;

            const shouldOpen = (
                (key === 'activos' && path.indexOf('/admin/activos/') !== -1) ||
                (key === 'incidentes' && path.indexOf('/admin/incidentes/') !== -1) ||
                (key === 'mantenimiento' && path.indexOf('/admin/mantenimiento/') !== -1) ||
                (key === 'riesgos' && path.indexOf('/admin/riesgos/') !== -1) ||
                (key === 'informes' && path.indexOf('/admin/informes/') !== -1)
            );

            if (shouldOpen) {
                submenu.classList.remove('hidden');
                p.setAttribute('aria-expanded', 'true');
                if (arrow) arrow.classList.add('rotate-90');
                sessionStorage.setItem('openSubmenuKey', key);
            }
        });
    }

    // --- Prefetch on hover (disabled to avoid server thread exhaustion) ---
    function setupPrefetch() {
        // Disabled prefetching to ensure instant response on user clicks
    }

    // --- Link interception ---
    function shouldIntercept(link) {
        // Only intercept same-origin, non-special links
        if (link.origin !== window.location.origin) return false;
        if (link.hasAttribute('data-turbo-disabled') || link.hasAttribute('download')) return false;
        if (link.target === '_blank') return false;
        if (link.pathname === '/login' || link.pathname === '/') return false;
        // Don't intercept links that are just anchors
        if (link.pathname === window.location.pathname && link.hash) return false;
        // Do NOT intercept PDF, Excel or file exports/downloads
        if (/\/pdf(\/|$|\?)/i.test(link.href) || /\/excel(\/|$|\?)/i.test(link.href)) return false;
        if (/\.(pdf|xlsx|xls|csv|zip|rar)$/i.test(link.pathname)) return false;
        return true;
    }

    function setupLinkInterception() {
        document.addEventListener('click', function (e) {
            // Don't intercept if modifier keys are pressed
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

            const link = e.target.closest('a[href]');
            if (!link || !shouldIntercept(link)) return;

            e.preventDefault();
            navigateTo(link.href);
        });
    }

    // --- Form handling: intercept forms to maintain SPA navigation ---
    function setupFormInterception() {
        document.addEventListener('submit', function (e) {
            const form = e.target.closest('form');
            if (!form) return;

            // Only intercept POST forms (not logout or special forms)
            if (form.method.toUpperCase() !== 'POST') return;
            // Don't intercept login or logout form
            if (form.action.includes('/logout') || form.action.includes('/login') || form.hasAttribute('data-turbo-disabled')) return;

            e.preventDefault();
            showProgress();

            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'text/html',
                },
                credentials: 'same-origin',
                redirect: 'follow',
            })
                .then(response => {
                    // Handle 419 o 401 — CSRF o sesión expirada por inactividad
                    if (response.status === 419 || response.status === 401) {
                        pageCache.clear();
                        window.location.href = '/login?inactividad=1';
                        return;
                    }

                    if (response.redirected && response.url.includes('/login')) {
                        pageCache.clear();
                        window.location.href = response.url;
                        return;
                    }

                    if (!response.ok && response.status !== 302) {
                        // Validation errors — re-render with errors
                        return response.text().then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            updateCsrfToken(doc);
                            const newContent = doc.querySelector(CONTENT_SELECTOR);
                            const currentContent = document.querySelector(CONTENT_SELECTOR);
                            if (newContent && currentContent) {
                                currentContent.innerHTML = newContent.innerHTML;
                                // Re-execute inline scripts
                                currentContent.querySelectorAll('script').forEach(oldScript => {
                                    const newScript = document.createElement('script');
                                    newScript.textContent = oldScript.textContent;
                                    oldScript.parentNode.replaceChild(newScript, oldScript);
                                });
                                enhanceSearchableSelects(currentContent);
                            }
                        });
                    }

                    // Clear cache for the target page (data changed)
                    pageCache.clear();

                    // Follow the redirect
                    const finalUrl = response.url;
                    return response.text().then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newContent = doc.querySelector(CONTENT_SELECTOR);
                        const currentContent = document.querySelector(CONTENT_SELECTOR);
                        if (newContent && currentContent) {
                            currentContent.innerHTML = newContent.innerHTML;
                            currentContent.scrollTop = 0;
                            // Re-execute inline scripts
                            currentContent.querySelectorAll('script').forEach(oldScript => {
                                const newScript = document.createElement('script');
                                newScript.textContent = oldScript.textContent;
                                oldScript.parentNode.replaceChild(newScript, oldScript);
                            });
                            const newTitle = doc.querySelector('title');
                            if (newTitle) document.title = newTitle.textContent;
                            history.pushState({ turboNav: true }, '', finalUrl);
                            updateActiveLink(finalUrl);
                            enhanceSearchableSelects(currentContent);
                        } else {
                            window.location.href = finalUrl;
                        }
                    });
                })
                .catch(() => {
                    // Fallback: submit normally
                    form.submit();
                })
                .finally(() => {
                    completeProgress();
                });
        });
    }

    // --- Browser back/forward ---
    function setupPopState() {
        window.addEventListener('popstate', function (e) {
            if (e.state && e.state.turboNav) {
                navigateTo(window.location.href, false);
            } else {
                // First page load or external navigation
                navigateTo(window.location.href, false);
            }
        });
    }

    // --- Periodic CSRF token refresh & session verification ---
    function setupCsrfRefresh() {
        const REFRESH_INTERVAL = 10 * 60 * 1000; // 10 minutos

        setInterval(function () {
            fetch('/csrf-token', {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            })
                .then(r => r.json())
                .then(data => {
                    // Si el servidor detectó que la sesión expiró (usuario ya no está autenticado)
                    if (data && data.authenticated === false && !window.location.pathname.includes('/login')) {
                        pageCache.clear();
                        window.location.href = '/login?inactividad=1';
                        return;
                    }

                    if (data && data.token) {
                        const meta = document.querySelector('meta[name="csrf-token"]');
                        if (meta) meta.setAttribute('content', data.token);
                        document.querySelectorAll('input[name="_token"]').forEach(input => {
                            input.value = data.token;
                        });
                    }
                })
                .catch(() => { /* silent */ });
        }, REFRESH_INTERVAL);
    }

    // --- Initialize ---
    function init() {
        // Mark initial state
        history.replaceState({ turboNav: true }, '', window.location.href);

        setupLinkInterception();
        setupFormInterception();
        setupPopState();
        setupPrefetch();
        setupCsrfRefresh();
        updateActiveLink(window.location.href);
        enhanceSearchableSelects();
    }

    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
