import { apiPost } from './api.js';

document.addEventListener('DOMContentLoaded', () => {

    // ── Tabs ──────────────────────────────────────────────────────────
    const tabBtns    = document.querySelectorAll('.inv-tab');
    const tabPanels  = document.querySelectorAll('.inv-tab-panel');
    const indicator  = document.getElementById('inv-tab-indicator');

    function updateTabIndicator(btn) {
        if (!indicator) return;
        indicator.style.width = `${btn.offsetWidth}px`;
        indicator.style.transform = `translateX(${btn.offsetLeft}px)`;
    }

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanels.forEach(p => {
                p.classList.remove('active');
                p.style.display = 'none';
            });
            btn.classList.add('active');
            updateTabIndicator(btn);

            const panel = document.getElementById(`panel-${target}`);
            if (panel) {
                panel.classList.add('active');
                panel.style.display = '';
            }
        });
    });

    // Set initial position
    setTimeout(() => {
        const activeTab = document.querySelector('.inv-tab.active');
        if (activeTab) updateTabIndicator(activeTab);
    }, 50);

    // ── Referencias DOM ───────────────────────────────────────────────
    const tablaBodyProd  = document.getElementById('tbody-inventario');
    const tablaBodyMP    = document.getElementById('tbody-mp');
    const statProd       = document.getElementById('stat-productos');
    const statMP         = document.getElementById('stat-mp');

    // Modales Producto
    const modalProd      = document.getElementById('modal-ajuste');
    const formProd       = document.getElementById('form-ajuste');
    const btnAjuste      = document.getElementById('btn-ajuste');

    // Modales Materia Prima
    const modalMP        = document.getElementById('modal-ajuste-mp');
    const formMP         = document.getElementById('form-ajuste-mp');
    const btnAjusteMP    = document.getElementById('btn-ajuste-mp');
    const mpMateria      = document.getElementById('mp-materia');
    const mpUnidadLabel  = document.getElementById('mp-unidad-label');

    let inventario     = [];
    let materiasPrimas = [];
    let dtProd         = null;
    let dtMP           = null;

    // ── SweetAlert2 Toast ─────────────────────────────────────────────
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (t) => {
            t.addEventListener('mouseenter', Swal.stopTimer);
            t.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    // ── Init ──────────────────────────────────────────────────────────
    cargarInventarioProductos();
    cargarInventarioMP();

    // ── Mostrar unidad al seleccionar insumo ──────────────────────────
    mpMateria.addEventListener('change', () => {
        const opt = mpMateria.options[mpMateria.selectedIndex];
        mpUnidadLabel.textContent = opt.dataset.unidad || '';
    });

    // ── Eventos botones principales ───────────────────────────────────
    btnAjuste.addEventListener('click', () => {
        formProd.reset();
        // Quitar id de fila si venía de un botón de tabla
        delete formProd.dataset.idProductoRow;
        modalProd.classList.add('open');
    });

    btnAjusteMP.addEventListener('click', () => {
        formMP.reset();
        mpUnidadLabel.textContent = '';
        delete formMP.dataset.idMateriaRow;
        modalMP.classList.add('open');
    });

    // ── Cierre modales ─────────────────────────────────────────────────
    document.querySelectorAll('[data-accion="cerrar-modal-prod"]').forEach(btn =>
        btn.addEventListener('click', () => modalProd.classList.remove('open'))
    );
    document.querySelectorAll('[data-accion="cerrar-modal-mp"]').forEach(btn =>
        btn.addEventListener('click', () => modalMP.classList.remove('open'))
    );

    // ── Envío de formularios ──────────────────────────────────────────
    formProd.addEventListener('submit', guardarAjusteProducto);
    formMP.addEventListener('submit', guardarAjusteMP);

    // ═════════════════════════════════════════════════════════════════
    // CARGA DE DATOS
    // ═════════════════════════════════════════════════════════════════

    async function cargarInventarioProductos() {
        try {
            const res = await apiPost(window.BASE_URL + '?page=Inventario', { action: 'consultar' });
            if (res.estado === 1) {
                inventario = res.response.datos || [];
                renderTablaProductos();
            } else {
                tablaBodyProd.innerHTML = errorRow(7, res.response?.mensaje || 'Error al cargar');
            }
        } catch (err) {
            console.error(err);
            tablaBodyProd.innerHTML = errorRow(7, 'Error de conexión');
        }
    }

    async function cargarInventarioMP() {
        try {
            const res = await apiPost(window.BASE_URL + '?page=Inventario', { action: 'consultar_mp' });
            if (res.estado === 1) {
                materiasPrimas = res.response.datos || [];
                renderTablaMP();
            } else {
                tablaBodyMP.innerHTML = errorRow(7, res.response?.mensaje || 'Error al cargar');
            }
        } catch (err) {
            console.error(err);
            tablaBodyMP.innerHTML = errorRow(7, 'Error de conexión');
        }
    }

    // ═════════════════════════════════════════════════════════════════
    // RENDER — PRODUCTOS TERMINADOS
    // ═════════════════════════════════════════════════════════════════

    function renderTablaProductos() {
        if ($.fn.DataTable.isDataTable('#tabla-inventario')) {
            $('#tabla-inventario').DataTable().destroy();
        }

        if (inventario.length === 0) {
            tablaBodyProd.innerHTML = emptyRow(7, 'No hay stock de productos registrado.');
            return;
        }

        let criticos = 0;
        tablaBodyProd.innerHTML = inventario.map(i => {
            const stockActual  = parseInt(i.cantidad_actual);
            const stockMinimo  = parseInt(i.cantidad_minima);
            const isCritico    = stockActual <= stockMinimo;
            const isWarning    = !isCritico && (stockActual <= stockMinimo * 1.3);
            if (isCritico) criticos++;

            const badgeClass = isCritico
                ? 'badge--cancelado'
                : isWarning
                    ? 'badge--pendiente'
                    : 'badge--entregado';
            const badgeText = isCritico ? 'Stock Crítico' : isWarning ? 'Stock Bajo' : 'Óptimo';

            const imgTag = (i.imagen_url && i.imagen_url !== 'placeholder.jpg')
                ? `<img src="${BASE_URL}assets/img/productos/${i.imagen_url}" alt="${escHtml(i.nombre)}" class="inv-prod-img" onerror="this.src='${BASE_URL}assets/img/placeholder.png'">`
                : `<img src="${BASE_URL}assets/img/placeholder.png" alt="Sin imagen" class="inv-prod-img">`;

            const fechaStr = i.fecha_update ? formatFechaCorta(i.fecha_update) : '—';

            return `
            <tr>
                <td>
                    <div style="display:flex; align-items:center; gap:10px;">
                        ${imgTag}
                        <div>
                            <div style="font-weight:700; color:var(--color-secondary);">${escHtml(i.nombre)}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge" style="background:rgba(181,142,58,0.1); color:var(--color-primary-dark); border:1px solid rgba(181,142,58,0.2);">
                        ${escHtml(i.categoria)}
                    </span>
                </td>
                <td>
                    <strong style="font-size:1.15rem; color:${isCritico ? 'var(--color-danger)' : 'var(--color-secondary)'};">
                        ${stockActual}
                    </strong>
                    <span style="font-size:0.75rem; color:var(--color-text-muted); margin-left:3px;">un.</span>
                </td>
                <td style="color:var(--color-text-muted);">${stockMinimo}</td>
                <td><span class="badge ${badgeClass}">${badgeText}</span></td>
                <td style="font-size:0.82rem; color:var(--color-text-muted);">${fechaStr}</td>
                <td style="text-align:center;">
                    <button class="btn-action btn-action--edit btn-ajuste-fila" data-id="${escHtml(i.id_producto)}" data-nombre="${escHtml(i.nombre)}" aria-label="Ajustar" title="Ajuste manual">
                        <box-icon name='slider' size='xs' animation='tada-hover' color='var(--color-warning)'></box-icon>
                    </button>
                </td>
            </tr>`;
        }).join('');

        // Estadística rápida en header
        statProd.innerHTML = criticos > 0
            ? `<span style="background:rgba(231,76,60,0.1); color:var(--color-danger); padding:3px 10px; border-radius:20px; font-weight:700;">
                   <box-icon name='error-circle' size='xs' color='inherit' style="transform:translateY(2px);"></box-icon>
                   ${criticos} en stock crítico
               </span>`
            : `<span style="background:rgba(46,204,113,0.1); color:var(--color-success); padding:3px 10px; border-radius:20px; font-weight:700;">
                   <box-icon name='check-circle' size='xs' color='inherit' style="transform:translateY(2px);"></box-icon>
                   Todo en óptimas condiciones
               </span>`;

        dtProd = $('#tabla-inventario').DataTable({
            language: { url: window.BASE_URL + 'assets/js/vendor/es-ES.json' },
            destroy: true,
            columnDefs: [{ orderable: false, targets: 6 }],
            drawCallback: bindEventosProd
        });

        bindEventosProd();
    }

    function bindEventosProd() {
        document.querySelectorAll('.btn-ajuste-fila').forEach(btn => {
            const clone = btn.cloneNode(true);
            btn.replaceWith(clone);
            clone.addEventListener('click', () => {
                formProd.reset();
                document.getElementById('ajuste-producto').value = clone.dataset.id;
                modalProd.classList.add('open');
            });
        });
    }

    // ═════════════════════════════════════════════════════════════════
    // RENDER — MATERIA PRIMA / INSUMOS
    // ═════════════════════════════════════════════════════════════════

    function renderTablaMP() {
        if ($.fn.DataTable.isDataTable('#tabla-mp')) {
            $('#tabla-mp').DataTable().destroy();
        }

        if (materiasPrimas.length === 0) {
            tablaBodyMP.innerHTML = emptyRow(7, 'No hay stock de materia prima registrado.');
            return;
        }

        let criticos = 0;
        tablaBodyMP.innerHTML = materiasPrimas.map(m => {
            const stock    = parseFloat(m.cantidad_actual);
            const minimo   = parseFloat(m.cantidad_minima);
            const isCrit   = stock <= minimo;
            const isWarn   = !isCrit && (stock <= minimo * 1.3);
            if (isCrit) criticos++;

            const badgeClass = isCrit ? 'badge--cancelado' : isWarn ? 'badge--pendiente' : 'badge--entregado';
            const badgeText  = isCrit ? 'Stock Crítico'    : isWarn ? 'Stock Bajo'       : 'Óptimo';

            // Fecha compacta sin "p. m." partido: dd/MM/yy HH:mm
            const ultimaEntrada = m.ultima_entrada
                ? formatFechaCorta(m.ultima_entrada)
                : '—';

            return `
            <tr>
                <td style="min-width:160px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div class="inv-mp-icon">
                            <box-icon name='leaf' color='var(--color-primary)' size='sm'></box-icon>
                        </div>
                        <span style="font-weight:700; color:var(--color-secondary); white-space:nowrap;">${escHtml(m.nombre)}</span>
                    </div>
                </td>
                <td style="white-space:nowrap;">
                    <span class="badge" style="background:rgba(52,152,219,0.1); color:var(--color-info); border:1px solid rgba(52,152,219,0.2); text-transform:uppercase;">
                        ${escHtml(m.unidad_medida)}
                    </span>
                </td>
                <td style="white-space:nowrap;">
                    <span style="display:inline-flex; align-items:baseline; gap:4px;">
                        <strong style="font-size:1.05rem; color:${isCrit ? 'var(--color-danger)' : 'var(--color-secondary)'}; font-variant-numeric:tabular-nums;">${parseFloat(stock).toFixed(3)}</strong>
                        <span style="font-size:0.72rem; color:var(--color-text-muted); font-weight:600;">${escHtml(m.unidad_medida)}</span>
                    </span>
                </td>
                <td style="white-space:nowrap; color:var(--color-text-muted);">
                    <span style="display:inline-flex; align-items:baseline; gap:4px;">
                        <span style="font-weight:600;">${parseFloat(minimo).toFixed(3)}</span>
                        <span style="font-size:0.72rem; font-weight:600;">${escHtml(m.unidad_medida)}</span>
                    </span>
                </td>
                <td style="white-space:nowrap; font-size:0.82rem; color:var(--color-text-muted);">${ultimaEntrada}</td>
                <td><span class="badge ${badgeClass}">${badgeText}</span></td>
                <td style="text-align:center;">
                    <button class="btn-action btn-action--edit btn-ajuste-mp-fila" data-id="${escHtml(m.id_materia)}" data-nombre="${escHtml(m.nombre)}" data-unidad="${escHtml(m.unidad_medida)}" aria-label="Ajustar" title="Ajuste manual">
                        <box-icon name='slider' size='xs' animation='tada-hover' color='var(--color-warning)'></box-icon>
                    </button>
                </td>
            </tr>`;
        }).join('');

        statMP.innerHTML = criticos > 0
            ? `<span style="background:rgba(231,76,60,0.1); color:var(--color-danger); padding:3px 10px; border-radius:20px; font-weight:700;">
                   <box-icon name='error-circle' size='xs' color='inherit' style="transform:translateY(2px);"></box-icon>
                   ${criticos} insumos en stock crítico
               </span>`
            : `<span style="background:rgba(46,204,113,0.1); color:var(--color-success); padding:3px 10px; border-radius:20px; font-weight:700;">
                   <box-icon name='check-circle' size='xs' color='inherit' style="transform:translateY(2px);"></box-icon>
                   Insumos en niveles adecuados
               </span>`;

        dtMP = $('#tabla-mp').DataTable({
            language: { url: window.BASE_URL + 'assets/js/vendor/es-ES.json' },
            destroy: true,
            columnDefs: [{ orderable: false, targets: 6 }],
            drawCallback: bindEventosMP
        });

        bindEventosMP();
    }

    function bindEventosMP() {
        document.querySelectorAll('.btn-ajuste-mp-fila').forEach(btn => {
            const clone = btn.cloneNode(true);
            btn.replaceWith(clone);
            clone.addEventListener('click', () => {
                formMP.reset();
                mpUnidadLabel.textContent = clone.dataset.unidad || '';
                document.getElementById('mp-materia').value = clone.dataset.id;
                modalMP.classList.add('open');
            });
        });
    }

    // ═════════════════════════════════════════════════════════════════
    // GUARDAR AJUSTES
    // ═════════════════════════════════════════════════════════════════

    async function guardarAjusteProducto(e) {
        e.preventDefault();
        const btn = e.target.closest('.admin-modal').querySelector('button[type="submit"]');
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Procesando...';

        try {
            const datos = Object.fromEntries(new FormData(formProd).entries());
            datos.action = 'ajustar';
            const res = await apiPost(window.BASE_URL + '?page=Inventario', datos);

            if (res.estado === 1) {
                modalProd.classList.remove('open');
                Toast.fire({ icon: 'success', title: 'Inventario de producto ajustado' });
                await cargarInventarioProductos();
            } else {
                Toast.fire({ icon: 'error', title: res.response?.mensaje || 'Error al ajustar' });
            }
        } catch (err) {
            console.error(err);
            Toast.fire({ icon: 'error', title: 'Error de comunicación con el servidor' });
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    }

    async function guardarAjusteMP(e) {
        e.preventDefault();
        const btn = e.target.closest('.admin-modal').querySelector('button[type="submit"]');
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Procesando...';

        try {
            const datos = Object.fromEntries(new FormData(formMP).entries());
            datos.action = 'ajustar_mp';
            const res = await apiPost(window.BASE_URL + '?page=Inventario', datos);

            if (res.estado === 1) {
                modalMP.classList.remove('open');
                Toast.fire({ icon: 'success', title: 'Inventario de insumo ajustado' });
                await cargarInventarioMP();
            } else {
                Toast.fire({ icon: 'error', title: res.response?.mensaje || 'Error al ajustar insumo' });
            }
        } catch (err) {
            console.error(err);
            Toast.fire({ icon: 'error', title: 'Error de comunicación con el servidor' });
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────
    function errorRow(cols, msg) {
        return `<tr><td colspan="${cols}" style="text-align:center; color:var(--color-danger); padding:3rem; font-weight:600;">${msg}</td></tr>`;
    }
    function emptyRow(cols, msg) {
        return `<tr><td colspan="${cols}" style="text-align:center; color:var(--color-text-muted); padding:3rem;">${msg}</td></tr>`;
    }
    function escHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // Fecha corta en formato 12h — DD/MM/YY HH:mm AM/PM
    function formatFechaCorta(isoStr) {
        const d = new Date(isoStr);
        if (isNaN(d)) return '—';
        const pad = n => String(n).padStart(2, '0');
        const h    = d.getHours();
        const h12  = h % 12 || 12;           // 0 → 12, resto normal
        const ampm = h < 12 ? 'AM' : 'PM';
        return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${String(d.getFullYear()).slice(-2)} ${pad(h12)}:${pad(d.getMinutes())} ${ampm}`;
    }
});
