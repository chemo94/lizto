<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;700&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #090d16;
            --bg-sidebar: #0f172a;
            --bg-card: rgba(30, 41, 59, 0.4);
            --border-color: rgba(255, 255, 255, 0.08);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --primary: #10b981;
            --primary-glow: rgba(16, 185, 129, 0.15);
            --accent: #6366f1;
            --accent-glow: rgba(99, 102, 241, 0.15);
            --get-color: #10b981;
            --post-color: #6366f1;
            --put-color: #f59e0b;
            --delete-color: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-primary);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Sidebar styling */
        .sidebar {
            width: 360px;
            background-color: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            overflow-y: auto;
            padding: 2rem 1.25rem;
            z-index: 100;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #fff;
            font-size: 1.25rem;
            box-shadow: 0 0 20px var(--primary-glow);
        }

        .logo-text {
            font-size: 1.4rem;
            font-weight: 800;
            background: linear-gradient(to right, #ffffff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-section-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            margin: 1.25rem 0 0.5rem 0;
            font-weight: 800;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 0.25rem;
        }

        .nav-link {
            display: block;
            padding: 0.5rem 0.75rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            margin-bottom: 0.25rem;
            border-left: 2px solid transparent;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-link:hover {
            color: var(--text-primary);
            background-color: rgba(255, 255, 255, 0.03);
            padding-left: 1.1rem;
        }

        .nav-link.active {
            color: var(--primary);
            background-color: var(--primary-glow);
            border-left-color: var(--primary);
            font-weight: 500;
        }

        /* Main content layout */
        .content {
            margin-left: 360px;
            flex: 1;
            padding: 3rem 4rem;
            max-width: 1200px;
        }

        header {
            margin-bottom: 3.5rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 2rem;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
        }

        .header-meta {
            color: var(--text-secondary);
            font-size: 1.05rem;
            line-height: 1.6;
        }

        .api-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 1rem;
        }

        .api-badge.auth {
            background-color: rgba(99, 102, 241, 0.15);
            color: #818cf8;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        /* Documentation sections */
        .doc-section {
            margin-bottom: 5rem;
        }

        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary);
            padding-left: 1rem;
        }

        /* Endpoint Card */
        .endpoint-card {
            background-color: var(--bg-card);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            margin-bottom: 2.5rem;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .endpoint-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .endpoint-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .method-badge {
            font-family: 'Fira Code', monospace;
            font-weight: 700;
            padding: 0.35rem 0.85rem;
            border-radius: 6px;
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        .method-badge.get {
            background-color: rgba(16, 185, 129, 0.15);
            color: var(--get-color);
        }

        .method-badge.post {
            background-color: rgba(99, 102, 241, 0.15);
            color: var(--post-color);
        }

        .method-badge.delete {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--delete-color);
        }

        .endpoint-path {
            font-family: 'Fira Code', monospace;
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .endpoint-desc {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-left: auto;
        }

        .endpoint-body {
            padding: 1.5rem;
        }

        /* Tables for params */
        .params-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }

        .params-table th, .params-table td {
            text-align: left;
            padding: 0.75rem 1rem;
            font-size: 0.85rem;
            border-bottom: 1px solid var(--border-color);
        }

        .params-table th {
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .param-name {
            font-family: 'Fira Code', monospace;
            color: var(--primary);
            font-weight: 600;
        }

        .param-type {
            font-family: 'Fira Code', monospace;
            color: var(--accent);
            font-size: 0.8rem;
        }

        .param-req {
            font-size: 0.7rem;
            background-color: rgba(239, 68, 68, 0.15);
            color: #f87171;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            font-weight: 600;
        }

        .param-opt {
            font-size: 0.7rem;
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
        }

        /* JSON Code block formatting */
        .code-container {
            position: relative;
            background-color: #05070c;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            margin-top: 1rem;
            overflow: hidden;
        }

        .code-header {
            background-color: rgba(255, 255, 255, 0.02);
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            color: var(--text-secondary);
            font-family: 'Fira Code', monospace;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .code-block {
            font-family: 'Fira Code', monospace;
            font-size: 0.85rem;
            padding: 1.25rem;
            overflow-x: auto;
            color: #38bdf8;
            line-height: 1.5;
        }

        .pm-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            background-color: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: var(--bg-main);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            body {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
                border-right: none;
                border-bottom: 1px solid var(--border-color);
            }
            .content {
                margin-left: 0;
                padding: 2rem;
            }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="logo-container">
            <div class="logo-icon">L</div>
            <div class="logo-text">Lizto Seller API</div>
        </div>

        <nav>
            <div class="nav-section-title">General</div>
            <a href="#intro" class="nav-link active">Introducción</a>
            <a href="#auth-headers" class="nav-link">Autenticación</a>

            <div class="nav-section-title">1. Autenticación & Perfil</div>
            <a href="#auth-login" class="nav-link">Iniciar Sesión</a>
            <a href="#auth-device" class="nav-link">Guardar Token Fcm</a>
            <a href="#auth-dashboard" class="nav-link">Dashboard General</a>
            <a href="#auth-profile" class="nav-link">Ver Perfil</a>

            <div class="nav-section-title">2. Catálogo (Productos)</div>
            <a href="#prod-list" class="nav-link">Listar Productos</a>
            <a href="#prod-store" class="nav-link">Registrar Producto</a>
            <a href="#prod-update" class="nav-link">Actualizar Producto</a>
            <a href="#prod-delete" class="nav-link">Eliminar Producto</a>
            <a href="#prod-status" class="nav-link">Alternar Estado</a>

            <div class="nav-section-title">3. Tiendas & Categorías</div>
            <a href="#store-list" class="nav-link">Listar Tiendas</a>
            <a href="#store-create" class="nav-link">Registrar Tienda</a>
            <a href="#store-update" class="nav-link">Actualizar Tienda</a>
            <a href="#store-delete" class="nav-link">Eliminar Tienda</a>
            <a href="#store-subcats" class="nav-link">Listar Subcategorías</a>

            <div class="nav-section-title">4. Menú Categorías</div>
            <a href="#mcat-list" class="nav-link">Listar Menú Categorías</a>
            <a href="#mcat-store" class="nav-link">Crear Categoría Menú</a>
            <a href="#mcat-update" class="nav-link">Actualizar Categoría Menú</a>
            <a href="#mcat-delete" class="nav-link">Eliminar Categoría Menú</a>

            <div class="nav-section-title">5. Pedidos Delivery</div>
            <a href="#del-orders" class="nav-link">Listar Pedidos Delivery</a>
            <a href="#del-detail" class="nav-link">Detalle Pedido Delivery</a>
            <a href="#del-status" class="nav-link">Actualizar Estado Pedido</a>

            <div class="nav-section-title">6. Billetera & Retiros</div>
            <a href="#wallet-balance" class="nav-link">Ver Saldo</a>
            <a href="#wallet-txs" class="nav-link">Historial de Transacciones</a>
            <a href="#wallet-withdraw" class="nav-link">Solicitar Retiro</a>

            <div class="nav-section-title">7. Stories (Historias)</div>
            <a href="#story-list" class="nav-link">Listar Historias</a>
            <a href="#story-detail" class="nav-link">Ver Historia por ID</a>
            <a href="#story-store" class="nav-link">Subir Historia</a>
            <a href="#story-pause" class="nav-link">Pausar Historia</a>
            <a href="#story-resume" class="nav-link">Reanudar Historia</a>
            <a href="#story-delete" class="nav-link">Eliminar Historia</a>

            <div class="nav-section-title">8. Horarios (Schedules)</div>
            <a href="#sched-list" class="nav-link">Horarios de Tienda</a>
            <a href="#sched-store" class="nav-link">Guardar Horario</a>
            <a href="#sched-bulk" class="nav-link">Guardar Masivo (Bulk)</a>
            <a href="#sched-delete" class="nav-link">Eliminar Horario</a>
            <a href="#sched-clear" class="nav-link">Limpiar Día</a>

            <div class="nav-section-title">9. Paquetes & Analítica</div>
            <a href="#pack-list" class="nav-link">Lista de Paquetes</a>
            <a href="#pack-my" class="nav-link">Mis Paquetes Comprados</a>
            <a href="#pack-purchase" class="nav-link">Comprar Plan Premium</a>
            <a href="#pack-analytics" class="nav-link">Analítica de Ventas</a>
            <a href="#pack-notify" class="nav-link">Notificar Clientes</a>
            <a href="#pack-qr" class="nav-link">Generar Código QR</a>

            <div class="nav-section-title">10. Panel POS (Salones & Caja)</div>
            <a href="#pos-dashboard" class="nav-link">POS Dashboard</a>
            <a href="#pos-tables" class="nav-link">Salas & Mesas</a>
            <a href="#pos-table-store" class="nav-link">Crear Mesa</a>
            <a href="#pos-table-update" class="nav-link">Actualizar Mesa</a>
            <a href="#pos-table-delete" class="nav-link">Eliminar Mesa</a>
            <a href="#pos-table-position" class="nav-link">Posición Mesa (Drag)</a>
            <a href="#pos-areas" class="nav-link">Listar Áreas</a>
            <a href="#pos-area-store" class="nav-link">Crear Área</a>
            <a href="#pos-area-delete" class="nav-link">Eliminar Área</a>
            <a href="#pos-kitchen" class="nav-link">Monitor de Cocina</a>
            <a href="#pos-kitchen-status" class="nav-link">Estado Plato Cocina</a>
            <a href="#pos-orders" class="nav-link">Historial Pedidos POS</a>
            <a href="#pos-customers" class="nav-link">Historial Clientes</a>
            <a href="#pos-cash" class="nav-link">Caja Diaria</a>
            <a href="#pos-cash-open" class="nav-link">Aperturar Caja</a>
            <a href="#pos-cash-close" class="nav-link">Cerrar Caja</a>
            <a href="#pos-cash-tx" class="nav-link">Ingreso/Salida de Caja</a>
            <a href="#pos-expenses" class="nav-link">Gestión de Gastos</a>
            <a href="#pos-expense-store" class="nav-link">Registrar Gasto</a>
            <a href="#pos-expense-delete" class="nav-link">Eliminar Gasto</a>
            <a href="#pos-billing" class="nav-link">Cobros POS (Simple/Split)</a>
            <a href="#pos-pay" class="nav-link">Registrar Cobro</a>
            <a href="#pos-invoice" class="nav-link">Factura/Boleta SUNAT</a>
            <a href="#pos-invoicing-config" class="nav-link">Configurar Series</a>
            <a href="#pos-invoicing-store" class="nav-link">Crear Serie de Emisión</a>
            <a href="#pos-notifications-send" class="nav-link">Notificar Usuarios</a>
            <a href="#pos-qrmenu" class="nav-link">QR Menú Digital</a>
            <a href="#pos-reports" class="nav-link">Reporte Tributario</a>
            <a href="#pos-register" class="nav-link">Registro Vendedor</a>

            <div class="nav-section-title">11. Repartidores (Favors)</div>
            <a href="#favor-create" class="nav-link">Pedir Repartidor</a>
            <a href="#favor-list" class="nav-link">Mis Pedidos de Repartidores</a>
            <a href="#favor-detail" class="nav-link">Detalle de Favor</a>
            <a href="#favor-cancel" class="nav-link">Cancelar Favor</a>
            <a href="#favor-bids" class="nav-link">Listar Ofertas Conductores</a>
            <a href="#favor-accept-bid" class="nav-link">Aceptar Oferta Conductor</a>
        </nav>
    </aside>

    <main class="content">
        <header id="intro">
            <h1>Documentación Completa de la API de Seller</h1>
            <p class="header-meta">
                Esta es la especificación detallada de todos los endpoints disponibles bajo el prefijo <code>/api/seller</code>. Diseñada especialmente para integraciones móviles (Flutter, Android nativo, iOS).
            </p>
            <div>
                <span class="api-badge auth">Prefijo de Ruta: /api/seller/</span>
            </div>
        </header>

        <!-- 1. Autenticación & Perfil -->
        <section id="auth-section" class="doc-section">
            <h2 class="section-title">1. Autenticación & Perfil</h2>
            <div id="auth-login" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/login</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Registro exitoso",
  "result": {
    "seller": { "id": 1, "name": "Seller Lizto", "email": "seller@liztogo.com" },
    "store": { "id": 2, "name": "Tienda Central", "is_premium": true },
    "token": "3|b283920..."
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="auth-device" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/save-device-token</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Device token saved successfully"
}</div>
                    </div>
                </div>
            </div>

            <div id="auth-dashboard" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/dashboard</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "stats": {
      "pos_today_count": 12,
      "pos_today_sales": 340.50,
      "del_today_count": 4,
      "del_today_sales": 95.00
    }
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="auth-profile" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/profile</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "seller": { "id": 1, "name": "Seller Lizto", "phone": "999888777" }
  }
}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. Catálogo (Productos) -->
        <section id="prod-section" class="doc-section">
            <h2 class="section-title">2. Catálogo (Productos)</h2>
            <div id="prod-list" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/products/{storeId}</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "products": [
      { "id": 5, "name": "Inka Cola 1L", "price": 8.50, "status": 1 }
    ]
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="prod-store" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/products/{storeId}/store</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Product created successfully",
  "result": {
    "product": { "id": 15, "name": "Ceviche Mixto", "price": 35.00 }
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="prod-update" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/products/update/{id}</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Product updated successfully"
}</div>
                    </div>
                </div>
            </div>

            <div id="prod-delete" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/products/delete/{id}</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Product deleted successfully"
}</div>
                    </div>
                </div>
            </div>

            <div id="prod-status" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/products/toggle-status/{id}</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Status updated successfully",
  "result": { "id": 15, "status": 0 }
}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Tiendas -->
        <section id="store-section" class="doc-section">
            <h2 class="section-title">3. Tiendas & Categorías</h2>
            <div id="store-list" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/stores</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "stores": [
      { "id": 2, "name": "Tienda Central", "address": "Av. Larco 123", "status": 1 }
    ]
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="store-create" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/stores/store</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Store created successfully",
  "result": { "store": { "id": 3, "name": "Nueva Tienda", "status": 1 } }
}</div>
                    </div>
                </div>
            </div>

            <div id="store-update" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/stores/update/{id}</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Store updated successfully"
}</div>
                    </div>
                </div>
            </div>

            <div id="store-delete" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/stores/delete/{id}</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Store deleted successfully"
}</div>
                    </div>
                </div>
            </div>

            <div id="store-subcats" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/subcategories</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "subcategories": [
      { "id": 1, "name": "Restaurantes", "category_id": 1 }
    ]
  }
}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 4. Menú Categorías -->
        <section id="mcat-section" class="doc-section">
            <h2 class="section-title">4. Menú Categorías</h2>
            <div id="mcat-list" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/menu-categories/{storeId}</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "menu_categories": [
      { "id": 3, "name": "Bebidas", "status": 1 }
    ]
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="mcat-store" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/menu-categories/{storeId}/store</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Menu category created successfully",
  "result": { "category": { "id": 5, "name": "Postres" } }
}</div>
                    </div>
                </div>
            </div>

            <div id="mcat-update" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/menu-categories/update/{id}</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Menu category updated successfully"
}</div>
                    </div>
                </div>
            </div>

            <div id="mcat-delete" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/menu-categories/delete/{id}</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Menu category deleted successfully"
}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 5. Pedidos Delivery -->
        <section id="del-orders-section" class="doc-section">
            <h2 class="section-title">5. Pedidos Delivery</h2>
            <div id="del-orders" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/orders</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "orders": [
      { "id": 1, "order_no": "DL-1002", "total": 45.00, "status": "preparing" }
    ]
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="del-detail" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/orders/{id}</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "order": {
      "id": 1,
      "order_no": "DL-1002",
      "total": 45.00,
      "items": [
        { "product_name": "Combo Lizto", "quantity": 1, "price": 45.00 }
      ]
    }
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="del-status" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/orders/status/{id}</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Order status updated successfully",
  "result": { "id": 1, "status": "ready" }
}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6. Billetera -->
        <section id="wallet-section" class="doc-section">
            <h2 class="section-title">6. Billetera & Retiros</h2>
            <div id="wallet-balance" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/wallet/balance</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": { "balance": 182.50 }
}</div>
                    </div>
                </div>
            </div>

            <div id="wallet-txs" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/wallet/transactions</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "transactions": [
      { "id": 25, "amount": 45.00, "type": "credit", "description": "Venta pedido DL-1002" }
    ]
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="wallet-withdraw" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/wallet/withdraw</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Withdrawal request submitted successfully",
  "result": { "id": 5, "amount": 100.00, "status": "pending" }
}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 7. Stories -->
        <section id="stories-section" class="doc-section">
            <h2 class="section-title">7. Stories (Historias de Tiendas)</h2>
            <div id="story-list" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/stories</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "stories": [
      { "id": 1, "image": "history1.jpg", "status": "active" }
    ]
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="story-detail" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/stories/{id}</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "story": { "id": 1, "image": "history1.jpg", "status": "active" }
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="story-store" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/stories/store</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Story uploaded successfully",
  "result": { "story": { "id": 3, "image": "history3.jpg" } }
}</div>
                    </div>
                </div>
            </div>

            <div id="story-pause" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/stories/{id}/pause</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Story paused successfully"
}</div>
                    </div>
                </div>
            </div>

            <div id="story-resume" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/stories/{id}/resume</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Story resumed successfully"
}</div>
                    </div>
                </div>
            </div>

            <div id="story-delete" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge delete">DELETE</span>
                    <span class="endpoint-path">/stories/{id}</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Story deleted successfully"
}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 8. Horarios -->
        <section id="sched-section" class="doc-section">
            <h2 class="section-title">8. Horarios (Schedules)</h2>
            <div id="sched-list" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/schedules</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "schedules": [
      { "id": 1, "day": "Monday", "open": "08:00", "close": "22:00" }
    ]
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="sched-store" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/schedules/store</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Schedule stored successfully"
}</div>
                    </div>
                </div>
            </div>

            <div id="sched-bulk" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/schedules/bulk</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Bulk schedules stored successfully"
}</div>
                    </div>
                </div>
            </div>

            <div id="sched-delete" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge delete">DELETE</span>
                    <span class="endpoint-path">/schedules/{id}</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Schedule deleted successfully"
}</div>
                    </div>
                </div>
            </div>

            <div id="sched-clear" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/schedules/clear-day</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Day schedules cleared successfully"
}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 9. Paquetes -->
        <section id="pack-section" class="doc-section">
            <h2 class="section-title">9. Paquetes & Analítica</h2>
            <div id="pack-list" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/packages</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "packages": [
      { "id": 1, "name": "Plan Pro", "price": 29.90, "days": 30 }
    ]
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="pack-my" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/packages/my</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "my_packages": [
      { "id": 2, "package_name": "Plan Premium", "expires_at": "2026-12-31" }
    ]
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="pack-purchase" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/packages/purchase</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Purchase successful",
  "result": { "invoice": "PREM-99182" }
}</div>
                    </div>
                </div>
            </div>

            <div id="pack-analytics" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/packages/{storeId}/analytics</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "total_revenue": 5890.00,
    "order_count": 210,
    "top_products": [ { "name": "Pollo a la Brasa", "sold": 82 } ]
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="pack-notify" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/packages/{storeId}/notify</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Promotions broadcasted successfully",
  "recipients": 142
}</div>
                    </div>
                </div>
            </div>

            <div id="pack-qr" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/packages/{storeId}/qr</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": { "qr_svg_base64": "data:image/svg+xml;base64,PD..." }
}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 10. Panel POS -->
        <section id="panel-section" class="doc-section">
            <h2 class="section-title">10. Panel POS (Salones, Cocina, Facturas y Caja)</h2>
            <div id="pos-dashboard" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/panel/dashboard</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "stats": { "pos_today_count": 8, "pos_today_sales": 210.00 }
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-tables" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/panel/tables</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "tables": [ { "id": 1, "name": "Mesa 1", "status": "occupied" } ],
    "areas": [ { "id": 2, "name": "Terraza" } ]
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-table-store" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/panel/tables/store</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Table created successfully"
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-table-update" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/panel/tables/{id}/update</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Table updated successfully"
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-table-delete" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/panel/tables/{id}/delete</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Table deleted successfully"
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-table-position" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/panel/tables/position</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Position saved successfully"
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-areas" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/panel/areas</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "areas": [ { "id": 1, "name": "Principal" } ]
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-area-store" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/panel/areas/store</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Area created successfully"
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-area-delete" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/panel/areas/{id}/delete</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Area deleted successfully"
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-kitchen" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/panel/kitchen</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "orders": [
      { "id": 12, "order_no": "POS-0012", "status": "preparing", "items": [] }
    ]
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-kitchen-status" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/panel/kitchen/{id}/status</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Status updated successfully",
  "result": { "id": 12, "status": "ready" }
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-orders" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/panel/orders</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "orders": {
      "data": [
        { "id": 12, "order_no": "POS-0012", "total": 35.00 }
      ]
    }
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-customers" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/panel/customers</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "customers": [
      { "customer_name": "Juan Perez", "customer_phone": "999888777", "total_spent": 120.00 }
    ]
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-cash" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/panel/cash</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "openSession": { "id": 1, "opening_balance": 150.00, "status": "open" },
    "transactions": []
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-cash-open" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/panel/cash/open</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Caja abierta",
  "result": { "session": { "id": 4, "opening_balance": 100 } }
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-cash-close" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/panel/cash/close</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Caja cerrada"
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-cash-tx" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/panel/cash/transaction</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Movimiento registrado",
  "result": { "transaction": { "amount": 25.00, "type": "cash_in", "description": "Ingreso extra" } }
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-expenses" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/panel/expenses</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "expenses": {
      "data": [
        { "id": 1, "amount": 50.00, "category": "Limpieza", "description": "Compra detergente" }
      ]
    }
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-expense-store" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/panel/expenses/store</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Gasto registrado",
  "result": { "expense": { "id": 3, "amount": 35.00, "category": "Gas" } }
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-expense-delete" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/panel/expenses/{id}/delete</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Gasto eliminado"
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-billing" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/panel/billing</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "pending": [ { "id": 12, "order_no": "POS-0012", "total": 35.00 } ],
    "paid": []
  }
}</div>
                    </div>
                </div>
            </div>

            <!-- Cobros POS -->
            <div id="pos-pay" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/panel/billing/{id}/pay</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Pedido cobrado exitosamente",
  "result": {
    "order": {
      "id": 12,
      "order_no": "POS-0012",
      "payment_status": "paid",
      "payment_method": "split",
      "payment_details": { "yape": 25, "cash": 10 }
    },
    "invoice": "B001-00000082"
  }
}</div>
                    </div>
                </div>
            </div>

            <!-- Emitir Comprobante Directo -->
            <div id="pos-invoice" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/panel/billing/{id}/invoice</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Comprobante emitido",
  "result": {
    "invoice": "F001-00000012"
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-invoicing-config" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/panel/invoicing</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "types": [ { "id": 1, "name": "Boleta de Venta", "series": [] } ]
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-invoicing-store" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/panel/invoicing/series/store</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Serie agregada",
  "result": { "series": { "id": 5, "series": "B002", "current_number": 1 } }
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-notifications-send" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/panel/notifications/send</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Notificación enviada a 12 usuarios",
  "result": { "recipients": 12 }
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-qrmenu" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/panel/qrmenu</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "storeUrl": "http://localhost/delivery/tienda/1",
    "qrUrl": "https://api.qrserver.com/v1/create-qr-code/?size=400x400..."
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-reports" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/panel/reports</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "report": {
      "from": "2026-06-01",
      "to": "2026-06-19",
      "total_orders": 32,
      "total_sales": 840.00
    }
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="pos-register" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/panel/register</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Registro exitoso",
  "result": {
    "seller": { "id": 4, "name": "Vendedor Nuevo" },
    "token": "5|f8190d..."
  }
}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 11. Repartidores (Favors) -->
        <section id="favor-section" class="doc-section">
            <h2 class="section-title">11. Repartidores (Favors)</h2>
            <div id="favor-create" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/favors/create</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Favor requested successfully",
  "result": { "id": 1, "status": "pending_bids" }
}</div>
                    </div>
                </div>
            </div>

            <div id="favor-list" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/favors</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "favors": [
      { "id": 1, "pickup_address": "Av Larco 123", "status": "active" }
    ]
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="favor-detail" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/favors/{id}</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "favor": { "id": 1, "status": "active", "driver_id": 5 }
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="favor-cancel" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/favors/cancel/{id}</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Favor cancelled successfully"
}</div>
                    </div>
                </div>
            </div>

            <div id="favor-bids" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge get">GET</span>
                    <span class="endpoint-path">/favors/{id}/bids</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "result": {
    "bids": [
      { "id": 4, "driver_name": "Carlos Gomez", "bid_amount": 7.00 }
    ]
  }
}</div>
                    </div>
                </div>
            </div>

            <div id="favor-accept-bid" class="endpoint-card">
                <div class="endpoint-header">
                    <span class="method-badge post">POST</span>
                    <span class="endpoint-path">/favors/{favorId}/bids/{bidId}/accept</span>
                </div>
                <div class="endpoint-body">
                    <div class="code-container">
                        <div class="code-header"><span>Respuesta (200 OK)</span></div>
                        <div class="code-block">{
  "status": "success",
  "message": "Bid accepted successfully"
}</div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        // Simple interactive sidebar highlights
        const links = document.querySelectorAll('.nav-link');
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                links.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
            });
        });
    </script>
</body>
</html>
