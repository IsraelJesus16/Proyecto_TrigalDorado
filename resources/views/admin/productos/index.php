<?php require_once BASE_PATH . '/resources/views/layout/head-admin.php'; ?>
<?php require_once BASE_PATH . '/resources/views/layout/sidebar-admin.php'; ?>


    <div class="page-content">
        <header class="page-header">
            <div>
                <h1 class="page-header__title">Catálogo de Productos</h1>
                <p class="page-header__subtitle">Gestión de productos y existencias de "El Trigal Dorado".</p>
            </div>
            <div>
                <button class="btn--admin-primary" id="btn-nuevo-producto" style="display:flex; align-items:center; gap:5px;">
                    <box-icon name='plus' color='inherit' size='sm'></box-icon> Nuevo Producto
                </button>
            </div>
        </header>

        <section class="table-card">
            <header class="table-card__header">
                <h3 class="table-card__title">Lista de Productos</h3>
                <div style="display:flex; gap:8px;">
                    <select id="filtro-categoria" class="form-select" style="max-width:220px; padding:0.5rem 1rem; border-radius:8px; font-size:0.95rem; border:1px solid #cbd5e1; box-shadow:0 1px 2px rgba(0,0,0,0.05); color:#334155;">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias ?? [] as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['id_categoria']); ?>">
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </header>
            <div class="table-wrap">
                <table class="data-table" id="tabla-productos">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Precio (Bs.)</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-productos">
                        <!-- AJAX content -->
                        <tr>
                            <td colspan="6" style="text-align:center; padding:3rem;">
                                <div class="skeleton skeleton--row"></div>
                                <div class="skeleton skeleton--row"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

    </div><!-- /page-content -->
</main><!-- /admin-main -->

<!-- Modal Producto -->
<div class="admin-modal-overlay" id="modal-producto">
    <div class="admin-modal admin-modal--lg" style="max-width: 800px;">
        <header class="admin-modal__header">
            <h2 class="admin-modal__title" id="modal-titulo" style="display:flex; align-items:center; gap:8px;">
                <box-icon name='package' color='var(--color-primary)'></box-icon> Nuevo Producto
            </h2>
            <button class="admin-modal__close" data-accion="cerrar-modal">✕</button>
        </header>

        <form id="form-producto" class="admin-modal__body admin-form" style="display:grid; grid-template-columns: 1fr 2fr; gap:2rem;" enctype="multipart/form-data">
            <input type="hidden" name="id_producto" id="prod-id">
            <input type="hidden" name="imagen_actual" id="prod-imagen-actual">
            
            <!-- Columna Izquierda: Imagen -->
            <div style="display:flex; flex-direction:column; gap:1rem;">
                <div class="form-section-header" style="margin-bottom: 0.5rem; border-bottom: 2px solid var(--color-primary); padding-bottom: 0.5rem;">
                    <h4 style="color: var(--color-primary); margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;"><box-icon name='image' color='inherit' size='sm'></box-icon> Foto del Producto</h4>
                </div>
                
                <div class="image-upload-wrapper" style="position:relative; width:100%; aspect-ratio: 1; border: 2px dashed #cbd5e1; border-radius: 16px; display:flex; flex-direction:column; align-items:center; justify-content:center; background:#f8fafc; cursor:pointer; overflow:hidden; transition: 0.3s ease;" onclick="document.getElementById('prod-imagen').click()">
                    <img id="preview-imagen" src="" style="position:absolute; width:100%; height:100%; object-fit:cover; display:none; z-index:2;" onerror="this.onerror=null; this.src='<?php echo BASE_URL; ?>assets/img/placeholder.png';">
                    <div id="upload-placeholder" style="display:flex; flex-direction:column; align-items:center; gap:10px; color:#94a3b8; z-index:1;">
                        <box-icon name='cloud-upload' size='md' color='#94a3b8'></box-icon>
                        <span style="font-size:0.9rem; font-weight:600;">Haz clic para subir</span>
                        <span style="font-size:0.75rem;">PNG, JPG (Max 2MB)</span>
                    </div>
                </div>
                <input type="file" id="prod-imagen" name="imagen" accept="image/png, image/jpeg, image/webp" style="display:none;">
                <div style="display:flex; justify-content:center;">
                    <button type="button" id="btn-remover-imagen" style="display:none; background:none; border:none; color:var(--color-danger); font-size:0.85rem; font-weight:600; cursor:pointer; padding:5px;">Quitar imagen</button>
                </div>
            </div>

            <!-- Columna Derecha: Datos -->
            <div style="display:flex; flex-direction:column; gap:1.2rem;">
                <div class="form-section-header" style="margin-bottom: 0.5rem; border-bottom: 2px solid var(--color-primary); padding-bottom: 0.5rem;">
                    <h4 style="color: var(--color-primary); margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;"><box-icon name='package' color='inherit' size='sm'></box-icon> Datos del Producto</h4>
                </div>

                <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                    <label class="form-label" for="prod-nombre">Nombre del Producto <span class="required">*</span></label>
                    <input type="text" id="prod-nombre" name="nombre" class="form-control" required placeholder="Ej: Pan de Queso Tradicional">
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.2rem;">
                    <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                        <label class="form-label" for="prod-categoria">Categoría <span class="required">*</span></label>
                        <select id="prod-categoria" name="id_categoria" class="form-select" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($categorias ?? [] as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['id_categoria']); ?>">
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                        <label class="form-label" for="prod-precio">Precio Venta (Bs.) <span class="required">*</span></label>
                        <div style="position:relative;">
                            <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-weight:600;">Bs.</span>
                            <input type="number" step="0.01" id="prod-precio" name="precio_venta" class="form-control" required style="padding-left: 2.2rem;" placeholder="0.00">
                        </div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.2rem;">
                    <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                        <label class="form-label" for="prod-peso">Peso Neto (g) <span class="required">*</span></label>
                        <div style="position:relative;">
                            <input type="number" step="0.01" id="prod-peso" name="peso_neto" class="form-control" required placeholder="500">
                            <span style="position:absolute; right:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-weight:600;">g</span>
                        </div>
                    </div>

                    <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                        <label class="form-label" for="prod-unidad">Unidad de Venta <span class="required">*</span></label>
                        <select id="prod-unidad" name="unidad_venta" class="form-select" required>
                            <option value="Paquete">Paquete</option>
                            <option value="Unidad">Unidad</option>
                            <option value="Caja">Caja</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group" style="display:flex; flex-direction:column; gap:0.4rem;">
                    <label class="form-label" for="prod-descripcion">Descripción corta</label>
                    <textarea id="prod-descripcion" name="descripcion" class="form-control" rows="2" placeholder="Detalles del producto..."></textarea>
                </div>
                
                <div style="display:flex; align-items:center; gap:0.5rem; background:#f0f9ff; padding:12px 15px; border-radius:10px; border:1px solid #bae6fd; margin-top:0.5rem;">
                    <input type="checkbox" id="prod-destacado" name="destacado" value="1" style="width:20px; height:20px; accent-color:var(--color-primary);">
                    <label for="prod-destacado" class="form-label" style="margin:0; cursor:pointer; color:#0369a1; font-weight: 600;"><box-icon name='star' type='solid' color='#0284c7' size='xs' style="transform:translateY(2px);"></box-icon> Destacar en el catálogo de inicio</label>
                </div>
                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0;">
                    <h3 style="font-size: 1.1rem; color: #334155; margin-bottom: 1rem; display:flex; align-items:center; gap:8px;">
                        <box-icon name='receipt' color='var(--color-primary)' size='sm'></box-icon> Receta e Insumos
                    </h3>
                    
                    <div style="display:flex; gap: 10px; align-items: flex-end; margin-bottom: 1rem;">
                        <div style="flex:2; display:flex; flex-direction:column; gap:0.4rem;">
                            <label class="form-label" style="font-size:0.85rem; margin:0;">Insumo</label>
                            <select id="receta-materia" class="form-select">
                                <option value="">Seleccione insumo...</option>
                            </select>
                        </div>
                        <div style="flex:1; display:flex; flex-direction:column; gap:0.4rem;">
                            <label class="form-label" style="font-size:0.85rem; margin:0;">Cantidad</label>
                            <input type="number" id="receta-cantidad" class="form-control" step="0.001" placeholder="Ej: 1.5">
                        </div>
                        <div>
                            <button type="button" id="btn-add-ingrediente" class="btn--admin-secondary" style="padding: 0.6rem 1rem; height: 42px;">
                                Añadir
                            </button>
                        </div>
                    </div>

                    <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <tr>
                                    <th style="padding: 10px 15px; font-size:0.85rem; color:#475569; font-weight:600;">Ingrediente</th>
                                    <th style="padding: 10px 15px; font-size:0.85rem; color:#475569; font-weight:600; width: 120px;">Cantidad</th>
                                    <th style="padding: 10px 15px; font-size:0.85rem; color:#475569; font-weight:600; width: 50px; text-align:center;">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="lista-receta">
                                <tr><td colspan="3" style="text-align:center; color:#94a3b8; font-size:0.9rem; padding: 15px;">Sin ingredientes</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>

        <footer class="admin-modal__footer" id="modal-footer">
            <button class="btn--admin-secondary" data-accion="cerrar-modal">Cancelar</button>
            <button class="btn--admin-primary" form="form-producto" type="submit" style="display:flex; align-items:center; gap:5px;">
                <box-icon name='save' color='#fff' size='sm'></box-icon> Guardar Producto
            </button>
        </footer>
    </div>
</div>

<!-- Modal Ver Detalles de Producto -->
<div class="admin-modal-overlay" id="modal-ver-producto">
    <div class="admin-modal admin-modal--lg" style="max-width: 850px; background: #f8fafc; padding: 0; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
        <header style="display: flex; justify-content: space-between; align-items: center; padding: 1.2rem 1.8rem; background: white; border-bottom: 1px solid rgba(0,0,0,0.05); position: relative; z-index: 10;">
            <h2 style="margin: 0; font-size: 1.25rem; color: #1e293b; font-weight: 700; display:flex; align-items:center; gap:10px;">
                <box-icon name='info-circle' color='#94a3b8'></box-icon> Detalles del Producto
            </h2>
            <button data-accion="cerrar-modal-ver" style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; transition: 0.2s;" onmouseover="this.style.background='#e2e8f0'; this.style.color='#0f172a';" onmouseout="this.style.background='#f1f5f9'; this.style.color='#64748b';">✕</button>
        </header>

        <div id="ver-producto-body" style="display: flex; flex-wrap: wrap; padding: 2rem; gap: 2rem; overflow-y: auto;">
            <!-- Contenido Inyectado por JS -->
        </div>
    </div>
</div>

<script>
    window.addEventListener('load', () => {
        const loader = document.getElementById('admin-loader');
        if(loader) loader.style.display = 'none';
    });
</script>

<!-- SweetAlert2 (local) -->
<script src="<?php echo BASE_URL; ?>assets/js/vendor/sweetalert2.min.js"></script>
<!-- jQuery + DataTables (local) -->
<script src="<?php echo BASE_URL; ?>assets/js/vendor/jquery.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/vendor/dataTables.min.js"></script>

<script type="module" src="<?php echo BASE_URL; ?>assets/js/modules/productos.js"></script>

</body>
</html>
