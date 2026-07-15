import { apiPost } from './api.js';

document.addEventListener('DOMContentLoaded', () => {
    const tablaBody = document.getElementById('tbody-clientes');
    // Elementos del Modal Principal
    const modalCliente = document.getElementById('modal-cliente');
    const formCliente = document.getElementById('form-cliente');
    const btnNuevo = document.getElementById('btn-nuevo-cliente');
    
    // Elementos del Modal de Ver Detalles
    const modalVerCliente = document.getElementById('modal-ver-cliente');
    const verClienteBody = document.getElementById('ver-cliente-body');
    
    let dataTableInstance = null;
    const cliNombre = document.getElementById('cli-nombre');
    const cliApellido = document.getElementById('cli-apellido');
    const cliTelefono = document.getElementById('cli-telefono');
    const cliCedulaNum = document.getElementById('cli-cedula-num');
    const cliCedulaPrefijo = document.getElementById('cli-cedula-prefijo');
    const cliCedulaHidden = document.getElementById('cli-cedula');
    
    const cliRifNum = document.getElementById('cli-rif-num');
    const cliRifPrefijo = document.getElementById('cli-rif-prefijo');
    const cliRifHidden = document.getElementById('cli-rif');
    
    // UI logic for form
    const selectTipo = document.getElementById('cli-tipo');
    const selectCondicion = document.getElementById('cli-condicion');
    
    let clientes = [];

    // Init
    cargarClientes();

    // Event Listeners
    btnNuevo.addEventListener('click', () => abrirModal());
    formCliente.addEventListener('submit', guardarCliente);
    
    // SweetAlert2 Toast configuration
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    // Capitalize names and block numbers
    const capitalize = (str) => {
        return str.toLowerCase().replace(/(^|\s)[a-záéíóúñü]/g, l => l.toUpperCase());
    };
    
    cliNombre.addEventListener('input', (e) => {
        e.target.value = capitalize(e.target.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]/g, ''));
    });
    
    cliApellido.addEventListener('input', (e) => {
        e.target.value = capitalize(e.target.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]/g, ''));
    });
    
    // Phone mask (0414-1234567)
    cliTelefono.addEventListener('input', (e) => {
        let x = e.target.value.replace(/\D/g, '').match(/(\d{0,4})(\d{0,7})/);
        e.target.value = !x[2] ? x[1] : x[1] + '-' + x[2];
    });
    
    // Cedula numbers only
    cliCedulaNum.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '');
    });
    
    // RIF numbers and dash only
    cliRifNum.addEventListener('input', (e) => {
        // Allows numbers and a single dash near the end (e.g. 12345678-9)
        e.target.value = e.target.value.replace(/[^0-9-]/g, '');
    });
    
    selectTipo.addEventListener('change', (e) => {
        const isB2B = e.target.value === 'JURIDICO';
        document.querySelectorAll('.b2b-field').forEach(el => el.style.display = isB2B ? 'flex' : 'none');
    });

    selectCondicion.addEventListener('change', (e) => {
        const isCredito = e.target.value !== 'CONTADO';
        document.querySelectorAll('.credito-field').forEach(el => el.style.display = isCredito ? 'block' : 'none');
    });

    // Cargar clientes
    async function cargarClientes() {
        try {
            const res = await apiPost(window.BASE_URL + '?page=Cliente', { action: 'consultar' });
            if (res.estado === 1) {
                clientes = res.response.datos || [];
                renderTabla();
            } else {
                tablaBody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:var(--color-danger);">${res.response?.mensaje || 'Error'}</td></tr>`;
            }
        } catch (error) {
            console.error(error);
            tablaBody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:var(--color-danger);">Error de conexión</td></tr>`;
        }
    }

    // Renderizar tabla con Simple-DataTables
    function renderTabla() {
        if (dataTableInstance) {
            dataTableInstance.destroy();
        }
        
        if (clientes.length === 0) {
            tablaBody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:var(--color-text-muted); padding:3rem;">No se encontraron clientes.</td></tr>`;
            return;
        }

        // Generar filas puras
        tablaBody.innerHTML = clientes.map(c => {
            const isB2B = c.tipo_cliente === 'JURIDICO';
            return `
            <tr>
                <td><strong>${c.cedula}</strong></td>
                <td>
                    <div style="font-weight:bold;">${isB2B ? c.razon_social : `${c.nombre} ${c.apellido}`}</div>
                    ${isB2B ? `<div style="font-size:0.75rem; color:var(--color-text-muted);">Rep: ${c.nombre} ${c.apellido}</div>` : ''}
                </td>
                <td>
                    <div>${c.telefono || 'Sin teléfono'}</div>
                    <div style="font-size:0.75rem; color:var(--color-text-muted);">${c.correo || ''}</div>
                </td>
                <td><span class="badge" style="background:${isB2B ? 'rgba(52, 152, 219, 0.1)' : 'rgba(46, 204, 113, 0.1)'}; color:${isB2B ? '#2980b9' : '#27ae60'};">${c.tipo_cliente}</span></td>
                <td>
                    <span class="badge ${c.condicion_pago.includes('CREDITO') ? 'badge--pendiente' : 'badge--confirmado'}">
                        ${c.condicion_pago.replace('_', ' ')}
                    </span>
                </td>
                <td>
                    <span class="badge ${c.estatus == 1 ? 'badge--confirmado' : 'badge--cancelado'}">
                        ${c.estatus == 1 ? 'Activo' : 'Inactivo'}
                    </span>
                </td>
                <td>
                    <div style="display:flex; gap:0.4rem; flex-wrap:nowrap;">
                        <button class="btn-action btn-action--success btn-ver" data-cedula="${c.cedula}" aria-label="Ver Detalles" title="Ver Detalles">
                            <box-icon name='show' size='xs' animation='tada-hover' color='var(--color-success)'></box-icon>
                        </button>
                        <button class="btn-action btn-action--view btn-editar" data-cedula="${c.cedula}" aria-label="Editar" title="Editar">
                            <box-icon name='edit' size='xs' animation='tada-hover' color='var(--color-info)'></box-icon>
                        </button>
                        ${c.estatus == 1 ? `<button class="btn-action btn-action--delete btn-eliminar" data-cedula="${c.cedula}" aria-label="Deshabilitar" title="Deshabilitar"><box-icon name='trash' size='xs' animation='tada-hover' color='var(--color-danger)'></box-icon></button>` : ''}
                    </div>
                </td>
            </tr>
        `}).join('');

        // Inicializar DataTables.net usando jQuery
        dataTableInstance = $('#tabla-clientes').DataTable({
            language: { url: window.BASE_URL + 'assets/js/vendor/es-ES.json' },
            destroy: true,
            info: true,
            ordering: true,
            paging: true,
            drawCallback: function() {
                bindEvents();
            }
        });
        
        bindEvents();
    }

    function bindEvents() {
        document.querySelectorAll('.btn-ver').forEach(btn => {
            btn.replaceWith(btn.cloneNode(true));
        });
        document.querySelectorAll('.btn-editar').forEach(btn => {
            btn.replaceWith(btn.cloneNode(true));
        });
        document.querySelectorAll('.btn-eliminar').forEach(btn => {
            btn.replaceWith(btn.cloneNode(true));
        });
        
        document.querySelectorAll('.btn-ver').forEach(btn => {
            btn.addEventListener('click', (e) => verCliente(e.currentTarget.dataset.cedula));
        });
        document.querySelectorAll('.btn-editar').forEach(btn => {
            btn.addEventListener('click', (e) => cargarCliente(e.currentTarget.dataset.cedula));
        });
        document.querySelectorAll('.btn-eliminar').forEach(btn => {
            btn.addEventListener('click', (e) => eliminarCliente(e.currentTarget.dataset.cedula));
        });
    }

    // Modal behavior
    function abrirModal(cedula = null) {
        formCliente.reset();
        document.getElementById('modal-titulo').textContent = cedula ? 'Editar Cliente' : 'Nuevo Cliente';
        
        if (cedula) {
            const cliente = clientes.find(c => c.cedula === cedula);
            if (!cliente) return;
            
            // Split cedula for the UI
            const prefijo = cliente.cedula.substring(0, 2);
            const numero = cliente.cedula.substring(2);
            if (cliCedulaPrefijo.querySelector(`option[value="${prefijo}"]`)) {
                cliCedulaPrefijo.value = prefijo;
            }
            cliCedulaNum.value = numero;
            cliCedulaHidden.value = cliente.cedula;

            document.getElementById('cli-es-edicion').value = "1";
            document.getElementById('cli-correo').value = cliente.correo;
            document.getElementById('cli-nombre').value = cliente.nombre;
            document.getElementById('cli-apellido').value = cliente.apellido;
            document.getElementById('cli-telefono').value = cliente.telefono;
            document.getElementById('cli-direccion').value = cliente.direccion;
            
            document.getElementById('cli-tipo').value = cliente.tipo_cliente;
            document.getElementById('cli-tipo').value = cliente.tipo_cliente;
            if (cliente.tipo_cliente === 'JURIDICO') {
                if (cliente.rif) {
                    const rifPrefijo = cliente.rif.substring(0, 2);
                    const rifNumero = cliente.rif.substring(2);
                    if (cliRifPrefijo.querySelector(`option[value="${rifPrefijo}"]`)) {
                        cliRifPrefijo.value = rifPrefijo;
                    }
                    cliRifNum.value = rifNumero;
                    cliRifHidden.value = cliente.rif;
                } else {
                    cliRifNum.value = '';
                    cliRifHidden.value = '';
                }
                document.getElementById('cli-razon-social').value = cliente.razon_social;
            } else {
                cliRifNum.value = '';
                cliRifHidden.value = '';
                document.getElementById('cli-razon-social').value = '';
            }
            
            document.getElementById('cli-condicion').value = cliente.condicion_pago;
            document.getElementById('cli-limite').value = cliente.limite_credito;
        } else {
            document.getElementById('cli-es-edicion').value = "";
        }

        // Triggers events
        selectTipo.dispatchEvent(new Event('change'));
        selectCondicion.dispatchEvent(new Event('change'));

        modalCliente.classList.add('open');
    }

    document.querySelectorAll('[data-accion="cerrar-modal"]').forEach(btn => {
        btn.addEventListener('click', () => modalCliente.classList.remove('open'));
    });
    
    document.querySelectorAll('[data-accion="cerrar-modal-ver"]').forEach(btn => {
        btn.addEventListener('click', () => modalVerCliente.classList.remove('open'));
    });

    // Copiar al portapapeles en modal
    verClienteBody.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-copiar');
        if (btn) {
            const texto = btn.dataset.texto;
            if (texto && texto.trim() !== '') {
                navigator.clipboard.writeText(texto).then(() => {
                    Toast.fire({ icon: 'success', title: 'Copiado al portapapeles' });
                }).catch(err => {
                    console.error('Error al copiar: ', err);
                });
            }
        }
    });

    // Ver Detalles
    function verCliente(cedula) {
        const cliente = clientes.find(c => c.cedula === cedula);
        if (!cliente) return;
        
        const isB2B = cliente.tipo_cliente === 'JURIDICO';
        
        verClienteBody.innerHTML = `
            <div style="display: flex; flex-direction: row; gap: 2rem; flex-wrap: wrap;">
                
                <!-- Columna Izquierda: Perfil y Crédito (35%) -->
                <div style="flex: 1 1 300px; display: flex; flex-direction: column; gap: 1.5rem;">
                    
                    <!-- Tarjeta de Perfil Principal -->
                    <div style="background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); padding: 2.5rem 1.5rem; text-align: center; border: 1px solid rgba(0,0,0,0.02); position: relative; overflow: hidden;">
                        
                        <!-- Fondo decorativo -->
                        <div style="position: absolute; top: 0; left: 0; right: 0; height: 100px; background: linear-gradient(135deg, rgba(181,142,58,0.1), rgba(181,142,58,0)); z-index: 0;"></div>
                        
                        <!-- Avatar -->
                        <div style="width: 86px; height: 86px; border-radius: 50%; background: linear-gradient(135deg, var(--color-primary), #d4af37); color: white; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; font-weight: 700; margin: 0 auto 1.2rem auto; box-shadow: 0 8px 20px rgba(181, 142, 58, 0.35); position: relative; z-index: 1;">
                            ${cliente.nombre.charAt(0)}${cliente.apellido.charAt(0)}
                        </div>
                        
                        <h3 style="margin: 0.5rem 0 1.2rem 0; color: #1e293b; font-size: 1.5rem; font-weight: 800; line-height: 1.2; position: relative; z-index: 1;">
                            ${cliente.nombre} ${cliente.apellido}
                        </h3>
                        
                        <span style="display: inline-block; background: #f1f5f9; color: #475569; padding: 0.4rem 1rem; border-radius: 30px; font-size: 0.85rem; font-weight: 700; letter-spacing: 0.5px; border: 1px solid #e2e8f0; position: relative; z-index: 1;">
                            CI: ${cliente.cedula}
                        </span>
                        
                        <div style="margin-top: 1.5rem; display: flex; justify-content: center; gap: 0.6rem; position: relative; z-index: 1;">
                            <span class="badge" style="background:${isB2B ? 'rgba(52, 152, 219, 0.1)' : 'rgba(46, 204, 113, 0.1)'}; color:${isB2B ? '#2980b9' : '#27ae60'}; padding: 0.4rem 0.8rem;">${cliente.tipo_cliente}</span>
                            <span class="badge ${cliente.estatus == 1 ? 'badge--confirmado' : 'badge--cancelado'}" style="padding: 0.4rem 0.8rem;">${cliente.estatus == 1 ? 'ACTIVO' : 'INACTIVO'}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Columna Derecha: Detalles (65%) -->
                <div style="flex: 2 1 400px; display: flex; flex-direction: column; gap: 1.5rem;">
                    
                    <!-- Información de Contacto -->
                    <div style="background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); padding: 2rem; border: 1px solid rgba(0,0,0,0.03);">
                        <h4 style="color: #334155; font-size: 1.15rem; font-weight: 700; margin-top: 0; margin-bottom: 1.8rem; display:flex; align-items:center; gap:10px;">
                            <box-icon name='contact' color='#94a3b8' size='sm'></box-icon> Información de Contacto
                        </h4>
                        
                        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                            <!-- Item Correo -->
                            <div style="display: flex; align-items: flex-start; gap: 1.2rem;">
                                <div style="width: 48px; height: 48px; border-radius: 12px; background: #f8fafc; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #f1f5f9;">
                                    <box-icon name='envelope' color='#64748b'></box-icon>
                                </div>
                                <div style="padding-top: 0.2rem; flex: 1;">
                                    <span style="display:block; font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 0.2rem;">Correo Electrónico</span>
                                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                                        <span style="font-size: 1.05rem; color: #0f172a; font-weight: 600; word-break: break-all;">${cliente.correo || '<span style="color:#cbd5e1; font-style:italic; font-weight:400;">No registrado</span>'}</span>
                                        ${cliente.correo ? `<button class="btn-copiar" data-texto="${cliente.correo}" style="background:transparent; border:none; cursor:pointer; color:#94a3b8; padding:5px; border-radius:5px; display:flex; align-items:center; transition:0.2s;" onmouseover="this.style.background='#f1f5f9'; this.style.color='#1e293b';" onmouseout="this.style.background='transparent'; this.style.color='#94a3b8';" title="Copiar"><box-icon name='copy' size='xs' color='inherit'></box-icon></button>` : ''}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Item Teléfono -->
                            <div style="display: flex; align-items: flex-start; gap: 1.2rem;">
                                <div style="width: 48px; height: 48px; border-radius: 12px; background: #f8fafc; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #f1f5f9;">
                                    <box-icon name='phone' color='#64748b'></box-icon>
                                </div>
                                <div style="padding-top: 0.2rem; flex: 1;">
                                    <span style="display:block; font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 0.2rem;">Teléfono Móvil / Fijo</span>
                                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                                        <span style="font-size: 1.05rem; color: #0f172a; font-weight: 600;">${cliente.telefono || '<span style="color:#cbd5e1; font-style:italic; font-weight:400;">No registrado</span>'}</span>
                                        ${cliente.telefono ? `<button class="btn-copiar" data-texto="${cliente.telefono}" style="background:transparent; border:none; cursor:pointer; color:#94a3b8; padding:5px; border-radius:5px; display:flex; align-items:center; transition:0.2s;" onmouseover="this.style.background='#f1f5f9'; this.style.color='#1e293b';" onmouseout="this.style.background='transparent'; this.style.color='#94a3b8';" title="Copiar"><box-icon name='copy' size='xs' color='inherit'></box-icon></button>` : ''}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Item Dirección -->
                            <div style="display: flex; align-items: flex-start; gap: 1.2rem;">
                                <div style="width: 48px; height: 48px; border-radius: 12px; background: #f8fafc; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #f1f5f9;">
                                    <box-icon name='map' color='#64748b'></box-icon>
                                </div>
                                <div style="padding-top: 0.2rem;">
                                    <span style="display:block; font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 0.2rem;">Dirección Física</span>
                                    <span style="font-size: 1.05rem; color: #0f172a; font-weight: 500; line-height: 1.5;">${cliente.direccion || '<span style="color:#cbd5e1; font-style:italic; font-weight:400;">No registrada en el sistema</span>'}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos B2B (Si aplica) -->
                    ${isB2B ? `
                    <div style="background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); padding: 2rem; border: 1px solid rgba(0,0,0,0.03);">
                        <h4 style="color: #334155; font-size: 1.15rem; font-weight: 700; margin-top: 0; margin-bottom: 1.8rem; display:flex; align-items:center; gap:10px;">
                            <box-icon name='buildings' color='#94a3b8' size='sm'></box-icon> Datos Fiscales Empresariales
                        </h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div>
                                <span style="display:block; font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 0.3rem;">Razón Social</span>
                                <span style="font-size: 1.05rem; color: #0f172a; font-weight: 700;">${cliente.razon_social}</span>
                            </div>
                            <div>
                                <span style="display:block; font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 0.3rem;">Registro de Info. Fiscal (RIF)</span>
                                <span style="font-size: 1.05rem; color: #0f172a; font-weight: 700;">${cliente.rif}</span>
                            </div>
                        </div>
                    </div>` : ''}

                    <!-- Tarjeta de Crédito Billetera (Al Final) -->
                    <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 16px; padding: 1.8rem; color: white; box-shadow: 0 15px 35px rgba(15, 23, 42, 0.25); position: relative; overflow: hidden; margin-top: auto;">
                        
                        <!-- Icono decorativo fondo -->
                        <div style="position: absolute; top: -15px; right: -15px; opacity: 0.05; transform: rotate(-15deg);">
                            <box-icon name='wallet' size='140px' color='white'></box-icon>
                        </div>
                        
                        <div style="position: relative; z-index: 1;">
                            <span style="display:block; font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 1px; margin-bottom: 0.5rem;">Límite de Crédito Disponible</span>
                            <span style="font-size: 2rem; font-weight: 800; color: #f8fafc; letter-spacing: -0.5px; display: block; margin-bottom: 0.2rem;">Bs. ${parseFloat(cliente.limite_credito).toFixed(2)}</span>
                            
                            <div style="margin-top: 1.8rem; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.2rem;">
                                <span style="font-size: 0.85rem; color: #cbd5e1; font-weight: 500;">Condición pactada</span>
                                <span style="font-size: 0.85rem; font-weight: 700; color: #fbbf24; background: rgba(251, 191, 36, 0.15); padding: 0.3rem 0.8rem; border-radius: 6px;">${cliente.condicion_pago.replace('_', ' ')}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pie de Meta-datos -->
                    <div style="display: flex; justify-content: flex-end; padding: 0.5rem 1rem 0 0; color: #94a3b8; font-size: 0.8rem; font-weight: 500;">
                        <span style="display:flex; align-items:center; gap:5px;">
                            <box-icon name='time' size='xs' color='#cbd5e1'></box-icon>
                            Registrado en el sistema el ${cliente.fecha_registro}
                        </span>
                    </div>
                </div>
            </div>
        `;
        
        modalVerCliente.classList.add('open');
    }

    // Cargar para editar
    async function cargarCliente(cedula) {
        abrirModal(cedula);
    }

    // Guardar
    async function guardarCliente(e) {
        e.preventDefault();
        
        // Assemble cedula and RIF
        cliCedulaHidden.value = cliCedulaPrefijo.value + cliCedulaNum.value;
        
        if (selectTipo.value === 'JURIDICO' && cliRifNum.value.trim() !== '') {
            cliRifHidden.value = cliRifPrefijo.value + cliRifNum.value;
        } else {
            cliRifHidden.value = '';
        }
        
        const submitBtn = document.querySelector('button[form="form-cliente"]');
        if(submitBtn) submitBtn.disabled = true;
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.innerHTML = 'Guardando...';

        try {
            const formData = new FormData(formCliente);
            const datos = { action: 'guardar' };
            formData.forEach((val, key) => datos[key] = val);
            
            const res = await apiPost(window.BASE_URL + '?page=Cliente', datos);
            if (res.estado === 1) {
                modalCliente.classList.remove('open');
                Toast.fire({ icon: 'success', title: 'Cliente guardado exitosamente' });
                cargarClientes();
            } else {
                Toast.fire({ icon: 'error', title: res.response.mensaje || 'Error al guardar' });
            }
        } catch (error) {
            Toast.fire({ icon: 'error', title: 'Error de comunicación' });
            console.error(error);
        } finally {
            if(submitBtn) {
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
            }
        }
    }

    // Eliminar
    async function eliminarCliente(cedula) {
        const result = await Swal.fire({
            title: '¿Está seguro?',
            text: "Esta acción deshabilitará al cliente",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, deshabilitar'
        });

        if (!result.isConfirmed) return;
        
        try {
            const res = await apiPost(window.BASE_URL + '?page=Cliente', { action: 'eliminar', cedula: cedula });
            if (res.estado === 1) {
            } else {
                alert(res.response.mensaje);
            }
        } catch (error) {
            console.error(error);
            alert('Error al eliminar');
        }
    }
});
