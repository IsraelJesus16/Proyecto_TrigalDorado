import { apiPost } from './api.js';

document.addEventListener('DOMContentLoaded', () => {
    // ─── Referencias DOM ──────────────────────────────────────────────
    const tablaBody      = document.getElementById('tbody-usuarios');
    const modalUsuario   = document.getElementById('modal-usuario');
    const formUsuario    = document.getElementById('form-usuario');
    const btnNuevo       = document.getElementById('btn-nuevo-usuario');
    const inputPwd       = document.getElementById('usu-password');
    const hintPwd        = document.getElementById('hint-pwd');

    let usuarios         = [];
    let dataTableInstance = null;

    // ─── Toast SweetAlert2 ────────────────────────────────────────────
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3200,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    // ─── Escape helper ───────────────────────────────────────────────
    const escHtml = (str) => {
        if (str == null) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    };

    const formatFecha = (isoStr) => {
        if (!isoStr) return '<span style="color:var(--color-text-muted);">Nunca</span>';
        const d = new Date(isoStr);
        return d.toLocaleString('es-VE', { dateStyle: 'short', timeStyle: 'short', hour12: true });
    };

    // ─── Init ─────────────────────────────────────────────────────────
    cargarUsuarios();

    // ─── Cerrar modales ───────────────────────────────────────────────
    document.querySelectorAll('[data-accion="cerrar-modal-usuario"]').forEach(btn =>
        btn.addEventListener('click', () => modalUsuario.classList.remove('open'))
    );

    // ─── Abrir modal nuevo ────────────────────────────────────────────
    btnNuevo.addEventListener('click', () => abrirModal());

    // ─── Submit ───────────────────────────────────────────────────────
    formUsuario.addEventListener('submit', guardarUsuario);

    // ═══════════════════════════════════════════════════════════════════
    // CARGAR DATOS
    // ═══════════════════════════════════════════════════════════════════
    async function cargarUsuarios() {
        try {
            const res = await apiPost(window.BASE_URL + '?page=Usuario', { action: 'consultar' });
            if (res.estado === 1) {
                usuarios = res.response.datos || [];
                renderTabla();
            } else {
                tablaBody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--color-danger);">
                    ${escHtml(res.response?.mensaje || 'Error al cargar usuarios')}</td></tr>`;
            }
        } catch (err) {
            console.error(err);
            tablaBody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--color-danger);">Error de conexión</td></tr>`;
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // RENDER TABLA
    // ═══════════════════════════════════════════════════════════════════
    function renderTabla() {
        if ($.fn.DataTable.isDataTable('#tabla-usuarios')) {
            $('#tabla-usuarios').DataTable().destroy();
        }

        if (!usuarios || usuarios.length === 0) {
            tablaBody.innerHTML = `<tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--color-text-muted);">
                <box-icon name='user-x' size='lg' color='var(--color-text-muted)'></box-icon>
                <p style="margin-top:8px;">No hay usuarios registrados.</p></td></tr>`;
            return;
        }

        tablaBody.innerHTML = usuarios.map(u => {
            const iniciales  = (u.nombre?.charAt(0) || '') + (u.apellido?.charAt(0) || '');
            const badgeClass = u.estatus == 1 ? 'badge--entregado' : 'badge--cancelado';
            const badgeText  = u.estatus == 1 ? 'Activo' : 'Inactivo';

            // Protección: usuario actual o root no se pueden tocar
            const esProtegido = (u.id_usuario === window.CURRENT_USER_ID) ||
                                 (u.id_usuario === window.ROOT_USER_ID);

            const accionesHTML = esProtegido
                ? `<span title="Usuario protegido del sistema" style="display:inline-flex;align-items:center;gap:4px;
                       font-size:0.75rem;color:var(--color-text-muted);padding:4px 8px;
                       border:1px dashed var(--border-color);border-radius:20px;">
                       <box-icon name='lock' size='xs' color='var(--color-text-muted)'></box-icon>
                       Protegido
                   </span>`
                : `<button class="btn-action btn-action--edit btn-editar-usu"
                            data-id="${escHtml(u.id_usuario)}"
                            aria-label="Editar usuario" title="Editar">
                       <box-icon name='edit' size='xs' animation='tada-hover' color='var(--color-warning)'></box-icon>
                   </button>
                   ${u.estatus == 1
                       ? `<button class="btn-action btn-action--delete btn-deshabilitar-usu"
                                  data-id="${escHtml(u.id_usuario)}" data-nombre="${escHtml(u.nombre + ' ' + u.apellido)}"
                                  aria-label="Deshabilitar" title="Deshabilitar">
                              <box-icon name='user-x' size='xs' animation='tada-hover' color='var(--color-danger)'></box-icon>
                          </button>`
                       : `<button class="btn-action btn-action--view btn-habilitar-usu"
                                  data-id="${escHtml(u.id_usuario)}" data-nombre="${escHtml(u.nombre + ' ' + u.apellido)}"
                                  aria-label="Habilitar" title="Habilitar usuario">
                              <box-icon name='user-check' size='xs' animation='tada-hover' color='var(--color-success)'></box-icon>
                          </button>`
                   }`;

            return `<tr ${esProtegido ? 'style="background:rgba(181,142,58,0.04);"' : ''}>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:36px;height:36px;border-radius:50%;
                                    background:${esProtegido ? 'var(--color-primary)' : 'var(--color-secondary)'};
                                    color:#fff;display:flex;align-items:center;justify-content:center;
                                    font-weight:700;font-size:0.9rem;flex-shrink:0;letter-spacing:0.5px;">
                            ${escHtml(iniciales.toUpperCase())}
                        </div>
                        <div>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <span style="font-weight:700;color:var(--color-secondary);">${escHtml(u.username)}</span>
                                ${esProtegido ? '<box-icon name="lock-alt" size="xs" color="var(--color-primary)" title="Protegido"></box-icon>' : ''}
                            </div>
                            <div style="font-size:0.75rem;color:var(--color-text-muted);">${escHtml(u.cedula)}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div style="font-weight:600;">${escHtml(u.nombre)} ${escHtml(u.apellido)}</div>
                    <div style="font-size:0.75rem;color:var(--color-text-muted);">${escHtml(u.correo || '—')}</div>
                </td>
                <td>
                    <span class="badge" style="background:rgba(181,142,58,0.1);color:var(--color-primary-dark);border:1px solid rgba(181,142,58,0.2);">
                        <box-icon name='shield' size='xs' color='inherit' style='transform:translateY(2px);margin-right:3px;'></box-icon>
                        ${escHtml(u.rol)}
                    </span>
                </td>
                <td style="font-size:0.82rem;color:var(--color-text-muted);">${formatFecha(u.ultimo_acceso)}</td>
                <td><span class="badge ${badgeClass}">${badgeText}</span></td>
                <td style="text-align:center;">${accionesHTML}</td>
            </tr>`;
        }).join('');

        dataTableInstance = $('#tabla-usuarios').DataTable({
            language: { url: window.BASE_URL + 'assets/js/vendor/es-ES.json' },
            destroy: true,
            columnDefs: [{ orderable: false, targets: 5 }],
            drawCallback: bindEventos
        });

        bindEventos();
    }

    function bindEventos() {
        document.querySelectorAll('.btn-editar-usu').forEach(btn => {
            const clone = btn.cloneNode(true);
            btn.replaceWith(clone);
            clone.addEventListener('click', () => {
                const u = usuarios.find(x => x.id_usuario === clone.dataset.id);
                if (u) abrirModal(u);
            });
        });

        document.querySelectorAll('.btn-deshabilitar-usu').forEach(btn => {
            const clone = btn.cloneNode(true);
            btn.replaceWith(clone);
            clone.addEventListener('click', () => cambiarEstatus(clone.dataset.id, clone.dataset.nombre, 0));
        });

        document.querySelectorAll('.btn-habilitar-usu').forEach(btn => {
            const clone = btn.cloneNode(true);
            btn.replaceWith(clone);
            clone.addEventListener('click', () => cambiarEstatus(clone.dataset.id, clone.dataset.nombre, 1));
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    // MODAL
    // ═══════════════════════════════════════════════════════════════════
    function abrirModal(datos = null) {
        formUsuario.reset();
        const titulo = modalUsuario.querySelector('#modal-titulo-usuario');

        if (datos) {
            titulo.textContent = 'Editar Usuario';
            document.getElementById('usu-id').value        = datos.id_usuario;
            document.getElementById('usu-cedula').value    = datos.cedula;
            document.getElementById('usu-rol').value       = datos.id_rol;
            document.getElementById('usu-nombre').value    = datos.nombre;
            document.getElementById('usu-apellido').value  = datos.apellido;
            document.getElementById('usu-correo').value    = datos.correo || '';
            document.getElementById('usu-username').value  = datos.username;
            // En edición la contraseña es opcional
            inputPwd.required = false;
            hintPwd.style.display = 'inline';
        } else {
            titulo.textContent = 'Nuevo Usuario';
            document.getElementById('usu-id').value = '';
            inputPwd.required = true;
            hintPwd.style.display = 'none';
        }

        modalUsuario.classList.add('open');
    }

    // ═══════════════════════════════════════════════════════════════════
    // GUARDAR
    // ═══════════════════════════════════════════════════════════════════
    async function guardarUsuario(e) {
        e.preventDefault();

        const btn = modalUsuario.querySelector('button[type="submit"]');
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Guardando...';

        try {
            const datos = Object.fromEntries(new FormData(formUsuario).entries());
            datos.action = 'guardar';

            const res = await apiPost(window.BASE_URL + '?page=Usuario', datos);

            if (res.estado === 1) {
                modalUsuario.classList.remove('open');
                Toast.fire({ icon: 'success', title: res.response?.mensaje || 'Usuario guardado correctamente' });
                await cargarUsuarios();
            } else {
                Toast.fire({ icon: 'error', title: res.response?.mensaje || 'Error al guardar' });
            }
        } catch (err) {
            console.error(err);
            Toast.fire({ icon: 'error', title: 'Error de conexión al guardar' });
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // CAMBIAR ESTATUS (HABILITAR / DESHABILITAR)
    // ═══════════════════════════════════════════════════════════════════
    async function cambiarEstatus(id, nombre, estatus) {
        const accion = estatus === 1 ? 'habilitar' : 'deshabilitar';
        const icono  = estatus === 1 ? 'question' : 'warning';

        const confirm = await Swal.fire({
            title: `¿${accion.charAt(0).toUpperCase() + accion.slice(1)} usuario?`,
            text: `El usuario "${nombre}" será ${accion === 'habilitar' ? 'habilitado' : 'deshabilitado'}.`,
            icon: icono,
            showCancelButton: true,
            confirmButtonText: `Sí, ${accion}`,
            cancelButtonText: 'Cancelar',
            confirmButtonColor: estatus === 1 ? 'var(--color-success)' : 'var(--color-danger)',
        });

        if (!confirm.isConfirmed) return;

        try {
            const res = await apiPost(window.BASE_URL + '?page=Usuario', {
                action: 'cambiar_estatus',
                id_usuario: id,
                estatus: estatus
            });

            if (res.estado === 1) {
                Toast.fire({ icon: 'success', title: res.response?.mensaje || 'Estatus actualizado' });
                await cargarUsuarios();
            } else {
                Toast.fire({ icon: 'error', title: res.response?.mensaje || 'Error al actualizar' });
            }
        } catch (err) {
            console.error(err);
            Toast.fire({ icon: 'error', title: 'Error de conexión' });
        }
    }
});
