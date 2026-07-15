import { apiPost } from './api.js';

document.addEventListener('DOMContentLoaded', () => {
    const tablaBody = document.getElementById('tbody-productos');
    const modalProducto = document.getElementById('modal-producto');
    const formProducto = document.getElementById('form-producto');
    const btnNuevo = document.getElementById('btn-nuevo-producto');
    const selectCategoria = document.getElementById('filtro-categoria');
    
    // Elementos del Modal Ver
    const modalVerProducto = document.getElementById('modal-ver-producto');
    const verProductoBody = document.getElementById('ver-producto-body');
    
    // Elementos de Imagen
    const inputImagen = document.getElementById('prod-imagen');
    const previewImagen = document.getElementById('preview-imagen');
    const uploadPlaceholder = document.getElementById('upload-placeholder');
    const btnRemoverImagen = document.getElementById('btn-remover-imagen');
    const inputImagenActual = document.getElementById('prod-imagen-actual');
    
    let productos = [];
    let dataTableInstance = null;
    let materiasPrimas = [];
    let recetaTemporal = [];

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

    // Init
    cargarIngredientes();
    cargarProductos();

    // Event Listeners
    btnNuevo.addEventListener('click', () => abrirModal());
    formProducto.addEventListener('submit', guardarProducto);
    
    // Filtros manuales para datatables


    // Filtro por categoría (usando columna 1 que tiene el nombre de la categoría)
    document.getElementById('filtro-categoria').addEventListener('change', (e) => {
        if (dataTableInstance) {
            const select = e.target;
            const textToSearch = select.options[select.selectedIndex].text;
            if (e.target.value === '') {
                dataTableInstance.column(1).search('').draw();
            } else {
                dataTableInstance.column(1).search(textToSearch).draw();
            }
        }
    });

    // Lógica de Previsualización de Imagen
    inputImagen.addEventListener('change', function(e) {
        const file = this.files[0];
        if (file) {
            // Validar tamaño (2MB)
            if (file.size > 2 * 1024 * 1024) {
                Toast.fire({ icon: 'error', title: 'La imagen excede los 2MB permitidos' });
                this.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = function(event) {
                previewImagen.src = event.target.result;
                previewImagen.style.display = 'block';
                uploadPlaceholder.style.display = 'none';
                btnRemoverImagen.style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    });

    btnRemoverImagen.addEventListener('click', function(e) {
        e.stopPropagation(); // Evitar que abra el file input
        inputImagen.value = '';
        inputImagenActual.value = '';
        previewImagen.src = '';
        previewImagen.style.display = 'none';
        uploadPlaceholder.style.display = 'flex';
        this.style.display = 'none';
    });

    // Cargar productos
    async function cargarProductos() {
        try {
            const res = await apiPost(window.BASE_URL + '?page=Producto', { action: 'consultar' });
            if (res.estado === 1) {
                productos = res.response.datos || [];
                renderTabla();
            } else {
                tablaBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:var(--color-danger);">${res.response?.mensaje || 'Error'}</td></tr>`;
            }
        } catch (error) {
            console.error(error);
            tablaBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:var(--color-danger);">Error de conexión</td></tr>`;
        }
    }

    async function cargarIngredientes() {
        try {
            const res = await apiPost(window.BASE_URL + '?page=Producto', { action: 'listar_ingredientes' });
            if (res.estado === 1) {
                materiasPrimas = res.response.datos || [];
                const selectMateria = document.getElementById('receta-materia');
                if (selectMateria) {
                    selectMateria.innerHTML = '<option value="">Seleccione insumo...</option>' + 
                        materiasPrimas.map(m => `<option value="${m.id_materia}" data-unidad="${m.unidad_medida}">${m.nombre} (${m.unidad_medida})</option>`).join('');
                }
            }
        } catch (error) {
            console.error(error);
        }
    }

    // Renderizar tabla con Simple-DataTables
    function renderTabla() {
        if ($.fn.DataTable.isDataTable('#tabla-productos')) {
            $('#tabla-productos').DataTable().destroy();
        }

        if (productos.length === 0) {
            tablaBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:var(--color-text-muted); padding:3rem;">No se encontraron productos registrados.</td></tr>`;
            return;
        }

        tablaBody.innerHTML = productos.map(p => {
            const badgeCat = p.color_badge ? `background: ${p.color_badge}15; color: ${p.color_badge}; border: 1px solid ${p.color_badge}40;` : '';
            let imgSrc = `${window.BASE_URL}assets/img/placeholder.png`;
            if (p.imagen_url) {
                if (p.imagen_url.includes('assets/')) {
                    imgSrc = `${window.BASE_URL}${p.imagen_url.replace('/assets/', 'assets/')}`;
                } else {
                    imgSrc = `${window.BASE_URL}assets/img/productos/${p.imagen_url}`;
                }
            }
            
            return `
            <tr>
                <td>
                    <div style="display:flex; align-items:center; gap:12px;">
                        <img src="${imgSrc}" alt="${p.nombre}" style="width:48px; height:48px; object-fit:cover; border-radius:10px; border:1px solid #e2e8f0; box-shadow: 0 2px 5px rgba(0,0,0,0.05);" onerror="this.onerror=null; this.src='${window.BASE_URL}assets/img/placeholder.png';">
                        <div style="display:flex; flex-direction:column;">
                            <strong style="color:#1e293b; font-size:0.95rem;">${p.nombre}</strong>
                            ${p.destacado == 1 ? '<span style="font-size:0.75rem; color:#d4af37; font-weight:700; display:flex; align-items:center; gap:3px;"><box-icon name="star" type="solid" color="#d4af37" size="xs"></box-icon> Destacado</span>' : ''}
                        </div>
                    </div>
                </td>
                <td><span class="badge" style="${badgeCat} padding:0.4rem 0.8rem; font-weight:600;">${p.nombre_categoria}</span></td>
                <td><strong style="color:#0f172a; font-size:1rem;">${parseFloat(p.precio_venta).toLocaleString('es-VE', {minimumFractionDigits:2})}</strong></td>
                <td>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <div style="width:8px; height:8px; border-radius:50%; background:${p.stock <= p.stock_minimo ? '#ef4444' : '#22c55e'};"></div>
                        <span style="font-weight:700; color:${p.stock <= p.stock_minimo ? '#ef4444' : '#334155'};">${p.stock}</span>
                    </div>
                </td>
                <td>
                    <span class="badge ${p.estatus == 1 ? 'badge--confirmado' : 'badge--cancelado'}" style="padding:0.4rem 0.8rem;">
                        ${p.estatus == 1 ? 'ACTIVO' : 'INACTIVO'}
                    </span>
                </td>
                <td>
                    <div style="display:flex; gap:0.4rem; flex-wrap:nowrap;">
                        <button class="btn-action btn-action--success btn-ver" data-id="${p.id_producto}" aria-label="Ver Detalles" title="Ver Detalles">
                            <box-icon name='show' size='xs' animation='tada-hover' color='var(--color-success)'></box-icon>
                        </button>
                        <button class="btn-action btn-action--view btn-editar" data-id="${p.id_producto}" aria-label="Editar" title="Editar">
                            <box-icon name='edit' size='xs' animation='tada-hover' color='var(--color-info)'></box-icon>
                        </button>
                        ${p.estatus == 1 ? `
                        <button class="btn-action btn-action--delete btn-eliminar" data-id="${p.id_producto}" aria-label="Deshabilitar" title="Deshabilitar">
                            <box-icon name='trash' size='xs' animation='tada-hover' color='var(--color-danger)'></box-icon>
                        </button>` : ''}
                    </div>
                </td>
            </tr>
        `}).join('');

        // Inicializar DataTables.net usando jQuery
        dataTableInstance = $('#tabla-productos').DataTable({
            language: { url: window.BASE_URL + 'assets/js/vendor/es-ES.json' },
            destroy: true,
            info: true,
            ordering: true,
            paging: true,
            drawCallback: function() {
                // Re-vincular eventos después de que DataTables redibuja la tabla
                document.querySelectorAll('.btn-ver').forEach(btn => {
                    btn.addEventListener('click', (e) => verDetallesProducto(e.currentTarget.dataset.id));
                });
                document.querySelectorAll('.btn-editar').forEach(btn => {
                    btn.addEventListener('click', (e) => cargarProducto(e.currentTarget.dataset.id));
                });
                document.querySelectorAll('.btn-eliminar').forEach(btn => {
                    btn.addEventListener('click', (e) => eliminarProducto(e.currentTarget.dataset.id));
                });
            }
        });

        // Eliminamos el listener manual de DataTables aquí, porque se usa drawCallback

    }

    // Modal behavior
    function abrirModal(datos = null) {
        formProducto.reset();
        
        // Reset imagen
        inputImagen.value = '';
        inputImagenActual.value = '';
        previewImagen.src = '';
        previewImagen.style.display = 'none';
        uploadPlaceholder.style.display = 'flex';
        btnRemoverImagen.style.display = 'none';
        
        // Reset receta
        recetaTemporal = [];
        document.getElementById('receta-materia').value = '';
        document.getElementById('receta-cantidad').value = '';
        
        const titulo = document.getElementById('modal-titulo');
        if (datos) {
            titulo.innerHTML = "<box-icon name='edit' color='var(--color-primary)'></box-icon> Editar Producto";
            document.getElementById('prod-id').value = datos.id_producto;
            document.getElementById('prod-nombre').value = datos.nombre;
            document.getElementById('prod-categoria').value = datos.id_categoria;
            document.getElementById('prod-precio').value = datos.precio_venta;
            document.getElementById('prod-peso').value = datos.peso_neto;
            document.getElementById('prod-unidad').value = datos.unidad_venta;
            document.getElementById('prod-descripcion').value = datos.descripcion;
            document.getElementById('prod-destacado').checked = datos.destacado == 1;
            
            if (datos.imagen_url) {
                inputImagenActual.value = datos.imagen_url;
                let previewSrc = '';
                if (datos.imagen_url.includes('assets/')) {
                    previewSrc = `${window.BASE_URL}${datos.imagen_url.replace('/assets/', 'assets/')}`;
                } else {
                    previewSrc = `${window.BASE_URL}assets/img/productos/${datos.imagen_url}`;
                }
                previewImagen.src = previewSrc;
                previewImagen.style.display = 'block';
                uploadPlaceholder.style.display = 'none';
                btnRemoverImagen.style.display = 'block';
            }
        } else {
            titulo.innerHTML = "<box-icon name='package' color='var(--color-primary)'></box-icon> Nuevo Producto";
            document.getElementById('prod-id').value = '';
        }

        if (datos && datos.ingredientes) {
            recetaTemporal = datos.ingredientes.map(ing => ({
                id_materia: ing.id_materia,
                nombre: ing.nombre,
                unidad_medida: ing.unidad_medida,
                cantidad: parseFloat(ing.cantidad)
            }));
        }
        renderReceta();

        modalProducto.classList.add('open');
    }

    // Funciones de Receta
    const btnAddIngrediente = document.getElementById('btn-add-ingrediente');
    if (btnAddIngrediente) {
        btnAddIngrediente.addEventListener('click', () => {
            const selectMateria = document.getElementById('receta-materia');
            const inputCantidad = document.getElementById('receta-cantidad');
            const idMateria = selectMateria.value;
            const cantidad = parseFloat(inputCantidad.value);
            
            if (!idMateria || isNaN(cantidad) || cantidad <= 0) {
                Toast.fire({ icon: 'warning', title: 'Seleccione un insumo y cantidad válida' });
                return;
            }
            
            const idx = recetaTemporal.findIndex(x => x.id_materia === idMateria);
            if (idx !== -1) {
                recetaTemporal[idx].cantidad = cantidad;
            } else {
                const mat = materiasPrimas.find(m => m.id_materia === idMateria);
                recetaTemporal.push({
                    id_materia: idMateria,
                    nombre: mat.nombre,
                    unidad_medida: mat.unidad_medida,
                    cantidad: cantidad
                });
            }
            
            selectMateria.value = '';
            inputCantidad.value = '';
            renderReceta();
        });
    }

    window.quitarIngrediente = function(idMateria) {
        recetaTemporal = recetaTemporal.filter(x => x.id_materia !== idMateria);
        renderReceta();
    };

    function renderReceta() {
        const lista = document.getElementById('lista-receta');
        if (!lista) return;
        
        if (recetaTemporal.length === 0) {
            lista.innerHTML = '<tr><td colspan="3" style="text-align:center; color:#94a3b8; font-size:0.9rem; padding: 15px;">Sin ingredientes</td></tr>';
            return;
        }
        
        lista.innerHTML = recetaTemporal.map(item => `
            <tr style="border-bottom: 1px solid #f8fafc;">
                <td style="padding: 10px 15px; font-size:0.9rem;">${item.nombre}</td>
                <td style="padding: 10px 15px; font-size:0.9rem; font-weight:600; color:#334155;">${item.cantidad} ${item.unidad_medida}</td>
                <td style="padding: 10px 15px; text-align:center;">
                    <button type="button" onclick="quitarIngrediente('${item.id_materia}')" style="background:transparent; border:none; cursor:pointer; padding:4px; border-radius:50%; display:flex; align-items:center; justify-content:center; transition:0.2s;" onmouseover="this.style.background='#fee2e2';" onmouseout="this.style.background='transparent';">
                        <box-icon name='trash' color='#ef4444' size='xs'></box-icon>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    async function verDetallesProducto(id) {
        const row = productos.find(x => x.id_producto === id);
        if (!row) return;

        let p = { ...row };
        try {
            const res = await apiPost(window.BASE_URL + '?page=Producto', { action: 'buscar', id_producto: id });
            if (res.estado === 1) {
                p = { ...p, ...res.response.datos };
            }
        } catch(e) {
            console.error(e);
        }

        let imgSrc = `${window.BASE_URL}assets/img/placeholder.png`;
        if (p.imagen_url) {
            if (p.imagen_url.includes('assets/')) {
                imgSrc = `${window.BASE_URL}${p.imagen_url.replace('/assets/', 'assets/')}`;
            } else {
                imgSrc = `${window.BASE_URL}assets/img/productos/${p.imagen_url}`;
            }
        }
        const badgeCat = p.color_badge ? `background: ${p.color_badge}15; color: ${p.color_badge}; border: 1px solid ${p.color_badge}40;` : '';

        verProductoBody.innerHTML = `
            <!-- Columna Izquierda: Imagen del producto -->
            <div style="flex: 1 1 300px; display: flex; flex-direction: column; gap: 1.5rem; align-items: center;">
                <div style="width: 100%; max-width: 300px; aspect-ratio: 1; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 4px solid white; position: relative;">
                    <img src="${imgSrc}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null; this.src='${window.BASE_URL}assets/img/placeholder.png';">
                    ${p.destacado == 1 ? '<div style="position:absolute; top:10px; right:10px; background:rgba(255,255,255,0.9); padding:5px 10px; border-radius:20px; color:#d4af37; font-weight:700; font-size:0.8rem; display:flex; align-items:center; gap:5px; box-shadow:0 4px 6px rgba(0,0,0,0.1);"><box-icon name="star" type="solid" color="#d4af37" size="xs"></box-icon> Destacado</div>' : ''}
                </div>
                
                <div style="width:100%; display: flex; justify-content: center; gap: 0.6rem;">
                    <span class="badge" style="${badgeCat} padding: 0.5rem 1rem; font-size:0.9rem;">${p.nombre_categoria}</span>
                    <span class="badge ${p.estatus == 1 ? 'badge--confirmado' : 'badge--cancelado'}" style="padding: 0.5rem 1rem; font-size:0.9rem;">${p.estatus == 1 ? 'ACTIVO' : 'INACTIVO'}</span>
                </div>
            </div>
            
            <!-- Columna Derecha: Detalles -->
            <div style="flex: 2 1 400px; display: flex; flex-direction: column; justify-content: center;">
                <h3 style="margin: 0 0 0.5rem 0; color: #0f172a; font-size: 2rem; font-weight: 800; line-height: 1.2;">
                    ${p.nombre}
                </h3>
                
                <p style="color: #64748b; font-size: 1.05rem; line-height: 1.6; margin-top:0; margin-bottom: 2rem;">
                    ${p.descripcion || 'Sin descripción detallada.'}
                </p>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                    
                    <div style="background: white; padding: 1.2rem; border-radius: 16px; border: 1px solid #e2e8f0; display:flex; align-items:center; gap:15px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                        <div style="width: 48px; height: 48px; border-radius: 12px; background: #f8fafc; display: flex; align-items: center; justify-content: center; color: var(--color-primary);">
                            <box-icon name='dollar-circle' color='inherit' size='md'></box-icon>
                        </div>
                        <div>
                            <span style="display:block; font-size: 0.8rem; text-transform: uppercase; color: #94a3b8; font-weight: 700;">Precio de Venta</span>
                            <strong style="color: #0f172a; font-size: 1.4rem;">Bs. ${parseFloat(p.precio_venta).toLocaleString('es-VE', {minimumFractionDigits:2})}</strong>
                        </div>
                    </div>

                    <div style="background: white; padding: 1.2rem; border-radius: 16px; border: 1px solid #e2e8f0; display:flex; align-items:center; gap:15px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                        <div style="width: 48px; height: 48px; border-radius: 12px; background: #f8fafc; display: flex; align-items: center; justify-content: center; color: #64748b;">
                            <box-icon name='trending-up' color='inherit' size='md'></box-icon>
                        </div>
                        <div>
                            <span style="display:block; font-size: 0.8rem; text-transform: uppercase; color: #94a3b8; font-weight: 700;">Ventas del Mes</span>
                            <strong style="color: #0f172a; font-size: 1.2rem;">${p.ventas_mes ?? 0} ${p.unidad_venta}(s)</strong>
                        </div>
                    </div>
                </div>
                
                <div style="background: white; padding: 1.5rem; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                    <h4 style="margin: 0 0 1rem 0; color: #334155; font-size: 1.1rem; display:flex; align-items:center; gap:8px;">
                        <box-icon name='receipt' color='var(--color-primary)' size='sm'></box-icon> Ingredientes (Receta)
                    </h4>
                    ${p.ingredientes && p.ingredientes.length > 0 ? `
                        <ul style="list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem;">
                            ${p.ingredientes.map(ing => `
                                <li style="display:flex; align-items:center; gap:8px; font-size:0.95rem; color:#475569;">
                                    <box-icon name='check-circle' type='solid' color='#10b981' size='xs'></box-icon>
                                    <span><strong>${ing.nombre}</strong> <span style="color:#94a3b8;">(${parseFloat(ing.cantidad)} ${ing.unidad_medida})</span></span>
                                </li>
                            `).join('')}
                        </ul>
                    ` : `
                        <div style="color: #94a3b8; font-size: 0.95rem; font-style: italic;">No hay ingredientes registrados para este producto.</div>
                    `}
                </div>

                <!-- Bloque de Inventario Visual -->
                <div style="background: ${p.stock <= p.stock_minimo ? '#fef2f2' : '#f0fdf4'}; border: 1px solid ${p.stock <= p.stock_minimo ? '#fecaca' : '#bbf7d0'}; padding: 1.5rem; border-radius: 16px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px;">
                        <span style="font-size: 0.9rem; text-transform: uppercase; color: ${p.stock <= p.stock_minimo ? '#ef4444' : '#16a34a'}; font-weight: 800;">
                            ${p.stock <= p.stock_minimo ? '⚠️ Stock Crítico' : '✅ Inventario Saludable'}
                        </span>
                        <strong style="font-size: 1.5rem; color: ${p.stock <= p.stock_minimo ? '#991b1b' : '#166534'};">${p.stock} en existencia</strong>
                    </div>
                    <div style="width: 100%; height: 8px; background: ${p.stock <= p.stock_minimo ? '#fca5a5' : '#86efac'}; border-radius: 4px; overflow: hidden;">
                        <div style="height: 100%; width: ${Math.min(100, (p.stock / (p.stock_minimo * 3 || 1)) * 100)}%; background: ${p.stock <= p.stock_minimo ? '#ef4444' : '#22c55e'}; border-radius: 4px;"></div>
                    </div>
                    <div style="margin-top: 8px; display:flex; justify-content:space-between; font-size: 0.8rem; color: ${p.stock <= p.stock_minimo ? '#b91c1c' : '#15803d'};">
                        <span>Peso/Unidad: <strong>${p.peso_neto}g</strong></span>
                        <span>Umbral mínimo de alerta: ${p.stock_minimo}</span>
                    </div>
                </div>
            </div>
        `;
        
        modalVerProducto.classList.add('open');
    }

    document.querySelectorAll('[data-accion="cerrar-modal"]').forEach(btn => {
        btn.addEventListener('click', () => modalProducto.classList.remove('open'));
    });
    
    document.querySelectorAll('[data-accion="cerrar-modal-ver"]').forEach(btn => {
        btn.addEventListener('click', () => modalVerProducto.classList.remove('open'));
    });

    // Cargar para editar
    async function cargarProducto(id) {
        let p = productos.find(x => x.id_producto === id);
        if (!p) return;
        
        try {
            const res = await apiPost(window.BASE_URL + '?page=Producto', { action: 'buscar', id_producto: id });
            if (res.estado === 1) {
                p = { ...p, ...res.response.datos };
            }
        } catch(e) {
            console.error(e);
        }
        
        abrirModal(p);
    }

    // Guardar
    async function guardarProducto(e) {
        e.preventDefault();
        const submitBtn = document.querySelector('button[form="form-producto"][type="submit"]');
        let originalBtnText = 'Guardar Producto';
        if (submitBtn) {
            originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Guardando...';
        }

        try {
            const formData = new FormData(formProducto);
            formData.append('action', 'guardar');
            formData.append('csrf_token', window.CSRF_TOKEN ?? ''); // Add CSRF token
            formData.append('receta', JSON.stringify(recetaTemporal));
            
            // Note: input type="file" is automatically appended by FormData
            
            const respuesta = await fetch(window.BASE_URL + '?page=Producto', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const res = await respuesta.json();
            
            if (res.estado === 1) {
                modalProducto.classList.remove('open');
                Toast.fire({ icon: 'success', title: 'Producto guardado exitosamente' });
                cargarProductos();
            } else {
                Toast.fire({ icon: 'error', title: res.response?.mensaje || 'Error al guardar' });
            }
        } catch (error) {
            console.error(error);
            Toast.fire({ icon: 'error', title: 'Error de comunicación' });
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }
    }

    // Eliminar
    function eliminarProducto(id) {
        Swal.fire({
            title: '¿Deshabilitar Producto?',
            text: "El producto ya no aparecerá en el catálogo de ventas.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Sí, deshabilitar',
            cancelButtonText: 'Cancelar'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'eliminar');
                    formData.append('id_producto', id);
                    
                    const res = await apiPost(BASE_URL + 'app/Controllers/ProductoController.php', formData, true);
                    if (res.estado === 1) {
                        Toast.fire({ icon: 'success', title: 'Producto deshabilitado' });
                        cargarProductos();
                    } else {
                        Toast.fire({ icon: 'error', title: res.response?.mensaje || 'Error' });
                    }
                } catch (error) {
                    console.error(error);
                    Toast.fire({ icon: 'error', title: 'Error al comunicarse con el servidor' });
                }
            }
        });
    }
});
