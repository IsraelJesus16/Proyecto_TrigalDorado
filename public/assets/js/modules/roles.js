/**
 * roles.js — Módulo de gestión dinámica de roles y permisos
 * El Trigal Dorado TPS · Vanilla JS ES6+ Modules
 */

import { apiPost } from './api.js';

// SweetAlert2 Toast configuration
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
    }
});

function mostrarToast(mensaje, icon = 'info') {
    Toast.fire({ icon, title: mensaje });
}

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal-rol');
    const form = document.getElementById('form-rol');
    const btnNuevo = document.getElementById('btn-nuevo-rol');
    const tituloModal = document.getElementById('modal-rol-titulo');
    const btnGuardar = document.getElementById('btn-guardar-rol');

    if (!modal || !form) return;

    // ─── Abrir modal para NUEVO ───────────────────────────────────────────
    btnNuevo?.addEventListener('click', () => {
        form.reset();
        document.getElementById('id_rol_viejo').value = '';
        document.getElementById('id_rol').readOnly = false;
        tituloModal.textContent = 'Nuevo Rol';
        modal.classList.add('open');
    });

    // ─── Cerrar modal ─────────────────────────────────────────────────────
    document.querySelectorAll('[data-accion="cerrar-modal"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            modal.classList.remove('open');
        });
    });

    // ─── Cargar datos para EDITAR ─────────────────────────────────────────
    document.querySelectorAll('.btn-action--edit').forEach(btn => {
        btn.addEventListener('click', async () => {
            const idRol = btn.dataset.id;
            const fila = btn.closest('tr');

            try {
                fila.style.opacity = '0.5';

                const res = await apiPost(`${window.BASE_URL}?page=Rol&type=ajax`, {
                    peticion: 'obtener_rol',
                    id_rol: idRol
                });

                if (res.resultado === 200) {
                    const r = res.rol;
                    document.getElementById('id_rol_viejo').value = r.id_rol;
                    document.getElementById('id_rol').value = r.id_rol;
                    document.getElementById('id_rol').readOnly = (r.id_rol === 'ROL_SUPERADMIN');
                    document.getElementById('nombre').value = r.nombre;
                    document.getElementById('descripcion').value = r.descripcion;

                    form.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked = false);
                    r.permisos.forEach(p => {
                        const check = form.querySelector(`input[value="${p}"]`);
                        if (check) check.checked = true;
                    });

                    tituloModal.textContent = 'Editar Rol: ' + r.nombre;
                    modal.classList.add('open');
                } else {
                    mostrarToast(res.mensaje || 'Error al cargar el rol.', 'error');
                }
            } catch (e) {
                mostrarToast(`Error de conexión: ${e.message}`, 'error');
                console.error(e);
            } finally {
                fila.style.opacity = '1';
            }
        });
    });

    // ─── Guardar (Crear/Editar) ───────────────────────────────────────────
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const idRol = document.getElementById('id_rol').value.trim();
        const nombre = document.getElementById('nombre').value.trim();

        if (!idRol || !nombre) {
            mostrarToast('ID y Nombre son obligatorios.', 'error');
            return;
        }

        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<span class="spinner"></span> Guardando...';

        try {
            const permisos = Array.from(form.querySelectorAll('input[name="permisos[]"]:checked')).map(cb => cb.value);

            const res = await apiPost(`${window.BASE_URL}?page=Rol&type=ajax`, {
                peticion: 'guardar',
                id_rol_viejo: document.getElementById('id_rol_viejo').value,
                id_rol: idRol,
                nombre: nombre,
                descripcion: document.getElementById('descripcion').value,
                permisos: JSON.stringify(permisos)
            });

            if (res.resultado === 200) {
                mostrarToast(res.mensaje, 'success');
                modal.classList.remove('open');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                mostrarToast(res.mensaje || 'Error al guardar.', 'error');
            }
        } catch (e) {
            mostrarToast('Error de conexión.', 'error');
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '💾 Guardar Rol';
        }
    });

    // ─── Eliminar Rol ─────────────────────────────────────────────────────
    document.querySelectorAll('.btn-action--delete').forEach(btn => {
        btn.addEventListener('click', async () => {
            const idRol = btn.dataset.id;
            
            const confirmacion = await Swal.fire({
                title: '¿Eliminar Rol?',
                text: `Se eliminará el rol ${idRol}. Esto fallará si hay usuarios asignados a él.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--color-danger)',
                cancelButtonColor: 'var(--color-secondary)',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            });

            if (!confirmacion.isConfirmed) return;

            const fila = btn.closest('tr');
            fila.style.opacity = '0.5';

            try {
                const res = await apiPost(`${window.BASE_URL}?page=Rol&type=ajax`, {
                    peticion: 'eliminar',
                    id_rol: idRol
                });

                if (res.resultado === 200) {
                    mostrarToast(res.mensaje, 'success');
                    fila.remove();
                } else {
                    mostrarToast(res.mensaje || 'Error al eliminar.', 'error');
                    fila.style.opacity = '1';
                }
            } catch (e) {
                mostrarToast('Error de conexión.', 'error');
                fila.style.opacity = '1';
            }
        });
    });
});
