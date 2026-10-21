/**
 * app.js — Interacciones del Sistema Escolar
 * Menú lateral, transición de entrada/salida, contadores animados,
 * buscador de tablas, toasts, modales y login.
 */
(() => {
    const $ = (s, c = document) => c.querySelector(s);
    const $$ = (s, c = document) => [...c.querySelectorAll(s)];
    const cortina = $('#cortina');

    // Los modales se mueven al <body> para que no queden atrapados bajo la animación de la página
    $$('.modal').forEach((m) => document.body.appendChild(m));

    // ---- Cortina: cubre la pantalla y luego navega (efecto de salida) ----
    function cubrir() {
        if (!cortina) return;
        cortina.style.animation = 'none';
        cortina.style.visibility = 'visible';
        cortina.style.opacity = '0';
        cortina.style.pointerEvents = 'all';
        void cortina.offsetWidth;                 // fuerza el reflow para que la transición arranque
        cortina.style.transition = 'opacity .5s ease';
        cortina.style.opacity = '1';
    }
    function salirConTransicion(url) { cubrir(); setTimeout(() => { location.href = url; }, 520); }

    // Si el navegador restaura la página con "atrás", se oculta la cortina
    window.addEventListener('pageshow', (e) => {
        if (e.persisted && cortina) {
            cortina.style.transition = 'none'; cortina.style.opacity = '0';
            cortina.style.visibility = 'hidden'; cortina.style.pointerEvents = 'none';
        }
    });

    // ---- Menú lateral: colapsa en escritorio, cajón en móvil ----
    const body = document.body;
    $('#btnMenu')?.addEventListener('click', () => {
        if (window.matchMedia('(max-width: 991.98px)').matches) {
            body.classList.toggle('menu-abierto');
        } else {
            body.classList.toggle('menu-colapsado');
            localStorage.setItem('menuColapsado', body.classList.contains('menu-colapsado') ? '1' : '0');
        }
    });
    $('#overlayMenu')?.addEventListener('click', () => body.classList.remove('menu-abierto'));

    // ---- Cerrar sesión (confirmación + transición) ----
    const btnSalir = $('#confirmarLogout');
    btnSalir?.addEventListener('click', () => {
        bootstrap.Modal.getInstance($('#modalLogout'))?.hide();
        salirConTransicion(btnSalir.dataset.url);
    });

    // ---- Contadores animados ----
    $$('[data-count]').forEach((el) => {
        const fin = parseFloat(el.dataset.count) || 0, dec = parseInt(el.dataset.dec || '0', 10), t0 = performance.now(), dur = 1000;
        const paso = (t) => {
            const p = Math.min((t - t0) / dur, 1), v = fin * (1 - Math.pow(1 - p, 3));
            el.textContent = v.toFixed(dec);
            if (p < 1) requestAnimationFrame(paso);
        };
        requestAnimationFrame(paso);
    });

    // ---- Filas de tabla con entrada escalonada + buscador en vivo ----
    $$('table.tabla tbody').forEach((tb) => $$('tr', tb).forEach((tr, i) => tr.style.setProperty('--r', Math.min(i, 14))));
    $$('[data-filtro]').forEach((inp) => {
        const tabla = $(inp.dataset.filtro);
        inp.addEventListener('input', () => {
            const q = inp.value.trim().toLowerCase();
            $$('tbody tr', tabla).forEach((tr) => {
                if (tr.querySelector('.vacio')) return;
                tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    });

    // ---- Formularios: estado "cargando" para evitar doble envío ----
    $$('form.form-modal').forEach((f) => f.addEventListener('submit', () => {
        const b = $('button[type=submit]', f);
        if (b) setTimeout(() => b.classList.add('cargando'), 0);
    }));

    // ---- Toasts ----
    $$('.toast').forEach((t) => new bootstrap.Toast(t).show());

    // ---- Reabre el modal si el formulario falló (conserva lo escrito) ----
    const reabrir = body.dataset.reabrir;
    if (reabrir && $('#' + reabrir)) new bootstrap.Modal($('#' + reabrir)).show();

    // ---- LOGIN ----
    const loginForm = $('#loginForm');
    if (loginForm) {
        // El acento de color cambia en vivo según el rol elegido
        $$('input[name=rol]', loginForm).forEach((r) => r.addEventListener('change', () => {
            body.classList.remove('tema-admin', 'tema-profesor', 'tema-alumno');
            body.classList.add('tema-' + r.value);
        }));
        // Mostrar / ocultar contraseña
        $('#verPass')?.addEventListener('click', () => {
            const inp = $('#contrasena'), ico = $('#verPass i'), ver = inp.type === 'password';
            inp.type = ver ? 'text' : 'password';
            ico.className = ver ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
        // Animación de salida de la tarjeta y luego se envía el formulario
        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const btn = $('button[type=submit]', loginForm);
            btn.classList.add('cargando');
            $('.login-glass').classList.add('saliendo');
            setTimeout(cubrir, 250);
            setTimeout(() => loginForm.submit(), 620);
        });
    }
})();
