@php
    $currentRoute = Route::currentRouteName();
    $storePlanType = $store?->getPlanType() ?? 'free';
    $canAccessReports = in_array($storePlanType, ['featured', 'premium']);
    $canAccessNotifications = $storePlanType === 'premium';
    $canAccessInventory = in_array($storePlanType, ['featured', 'premium']);
    $canAccessInvoicing = in_array($storePlanType, ['basic', 'featured', 'premium']);

    $sellerId = session('seller_id');
    $currentStore = $store;
    if (!$currentStore && $sellerId) {
        $currentStore = \App\Models\Store::where('seller_id', $sellerId)->first();
    }

    $storeActivePackage = null;
    $showAmberAlert = false;
    $showCriticalAlert = false;
    $daysRemaining = null;

    if ($currentStore) {
        $storeActivePackage = $currentStore->storePackages->filter(fn($sp) => $sp->isActive())->first(fn($sp) => $sp->package && in_array($sp->package->type, ['basic', 'featured', 'premium']));
        if ($storeActivePackage && $storeActivePackage->expires_at) {
            $expiresAtDate = $storeActivePackage->expires_at instanceof \Carbon\Carbon ? $storeActivePackage->expires_at : \Carbon\Carbon::parse($storeActivePackage->expires_at);
            $daysRemaining = (int) now()->startOfDay()->diffInDays($expiresAtDate->startOfDay(), false);
            if ($daysRemaining === 0) {
                $showCriticalAlert = true; // Vence HOY — banner rojo urgente
            } elseif ($daysRemaining > 0 && $daysRemaining <= 7) {
                $showAmberAlert = true;    // Vence pronto — banner ámbar de advertencia
            }
        }
    }

    $manuals = [
        'seller.dashboard' => [
            'title' => 'Manual de Dashboard',
            'icon' => 'la-chart-pie',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-chart-pie mr-1"></i> Resumen del Dashboard</h5>
                <p>El Dashboard es tu centro de control principal. Aquí verás un resumen del rendimiento de tu negocio en tiempo real.</p>
                <div class="mb-3">
                    <strong><i class="las la-check text-success mr-1"></i> Métricas Clave:</strong>
                    <ul class="pl-3 mt-1 text-muted">
                        <li><strong>Ventas de Hoy:</strong> Total acumulado de pedidos procesados hoy.</li>
                        <li><strong>Cuentas por Cobrar:</strong> Pedidos entregados que aún no se registran como pagados.</li>
                        <li><strong>Mesas Ocupadas:</strong> Estado actual del salón (solo restaurantes).</li>
                    </ul>
                </div>
                <div class="mb-3">
                    <strong><i class="las la-check text-success mr-1"></i> Gráfico de Ventas:</strong>
                    <p class="text-muted mt-1">Muestra la tendencia de ventas de los últimos 30 días, dividida entre ventas por POS y ventas por Delivery.</p>
                </div>
            '
        ],
        'seller.pos' => [
            'title' => 'Manual del Terminal (POS)',
            'icon' => 'la-cash-register',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-cash-register mr-1"></i> ¿Cómo usar el POS?</h5>
                <p>El Punto de Venta (POS) te permite registrar pedidos rápidos, gestionar comandas de mesas y enviar solicitudes a cocina.</p>
                <div class="mb-3">
                    <strong>Pasos para registrar un pedido:</strong>
                    <ol class="pl-3 mt-1 text-muted">
                        <li>Selecciona el tipo de servicio (Mesa, Para Llevar, Delivery, etc.).</li>
                        <li>Si es para mesa, haz clic en la mesa correspondiente.</li>
                        <li>Haz clic en los productos para agregarlos a la comanda.</li>
                        <li>Si el producto tiene variaciones o adicionales, selecciona las opciones deseadas y confirma.</li>
                        <li>Opcional: Busca el cliente por DNI/RUC usando el buscador SUNAT.</li>
                        <li>Haz clic en <strong>Enviar Comanda</strong> para mandar el pedido a cocina.</li>
                    </ol>
                </div>
                <div class="mb-3">
                    <strong>Cobrar desde el POS:</strong>
                    <p class="text-muted mt-1">En la barra derecha verás las "Cuentas por Cobrar". Haz clic en cualquier comanda activa para abrir el modal de cobro y emitir comprobante directamente.</p>
                </div>
            '
        ],
        'seller.pos.billing' => [
            'title' => 'Manual de Cobros y Caja',
            'icon' => 'la-hand-holding-usd',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-hand-holding-usd mr-1"></i> Registro de Pagos y Facturación</h5>
                <p>Aquí gestionas el cobro de todas las comandas entregadas o listas que están pendientes de registrar pago.</p>
                <div class="mb-3">
                    <strong>¿Cómo procesar un cobro?</strong>
                    <ol class="pl-3 mt-1 text-muted">
                        <li>Ubica el pedido en la lista y haz clic en <strong>Cobrar y Emitir</strong>.</li>
                        <li>Selecciona el comprobante a emitir (Boleta, Factura, Nota de Venta, etc.).</li>
                        <li>Ingresa los montos correspondientes en los métodos de pago (puedes dividir el pago entre varios métodos).</li>
                        <li>Si es un pago electrónico (POS, Yape, Plin, Transferencia), puedes destinarlo a una cuenta bancaria específica.</li>
                        <li>Haz clic en <strong>Confirmar Cobro</strong> para finalizar la transacción.</li>
                    </ol>
                </div>
            '
        ],
        'seller.pos.kitchen' => [
            'title' => 'Manual de Monitor de Cocina',
            'icon' => 'la-utensils',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-utensils mr-1"></i> Monitor de Cocina</h5>
                <p>Pantalla diseñada para cocineros y preparadores para ver y actualizar el estado de los platos pedidos.</p>
                <div class="mb-3">
                    <strong>Cambiar estados de preparación:</strong>
                    <ul class="pl-3 mt-1 text-muted">
                        <li>Los pedidos ingresan en estado <strong>Pendiente</strong> o <strong>En Cocina</strong>.</li>
                        <li>Haz clic en el botón de estado para cambiarlo a <strong>Listo</strong> cuando la preparación termine.</li>
                        <li>El personal de salón recibirá una alerta visual de que el plato está listo para servirse.</li>
                    </ul>
                </div>
            '
        ],
        'seller.orders' => [
            'title' => 'Manual de Pedidos',
            'icon' => 'la-receipt',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-receipt mr-1"></i> Historial y Control de Pedidos</h5>
                <p>Visualiza y administra todos los pedidos registrados en tu negocio (tanto de salón como de delivery).</p>
                <div class="mb-3">
                    <strong>Funcionalidades clave:</strong>
                    <ul class="pl-3 mt-1 text-muted">
                        <li>Filtra por fecha, estado (Pendiente, Entregado, Cancelado) o tipo de pedido.</li>
                        <li>Imprime pre-cuentas o tickets térmicos directamente.</li>
                        <li>Cancela pedidos si es necesario (se registrará la anulación).</li>
                    </ul>
                </div>
            '
        ],
        'seller.products' => [
            'title' => 'Manual de Productos',
            'icon' => 'la-boxes',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-boxes mr-1"></i> Catálogo de Productos</h5>
                <p>Administra la carta o el inventario de artículos disponibles para la venta.</p>
                <div class="mb-3">
                    <strong>Acciones:</strong>
                    <ul class="pl-3 mt-1 text-muted">
                        <li><strong>Nuevo Producto:</strong> Añade nombre, precio, categoría, imagen y stock inicial.</li>
                        <li><strong>Variaciones:</strong> Configura tamaños, sabores o porciones con sus respectivos precios extras.</li>
                        <li><strong>Adicionales:</strong> Agrega toppings, cremas o guarniciones adicionales.</li>
                    </ul>
                </div>
            '
        ],
        'seller.products.bulk' => [
            'title' => 'Manual de Carga Masiva',
            'icon' => 'la-cloud-upload-alt',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-cloud-upload-alt mr-1"></i> Carga Masiva de Productos</h5>
                <p>Importa o actualiza productos masivamente mediante un archivo de Excel o CSV.</p>
                <div class="mb-3">
                    <strong>Pasos recomendados:</strong>
                    <ol class="pl-3 mt-1 text-muted">
                        <li>Descarga la plantilla oficial en la parte superior.</li>
                        <li>Rellena los datos de tus productos (SKU, nombre, categoría, precio, stock).</li>
                        <li>Arrastra y suelta tu archivo y haz clic en "Subir archivo".</li>
                        <li>Verifica que los datos cargados no contengan errores antes de guardar.</li>
                    </ol>
                </div>
            '
        ],
        'seller.categories' => [
            'title' => 'Manual de Categorías',
            'icon' => 'la-tags',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-tags mr-1"></i> Categorías del Menú</h5>
                <p>Organiza tus productos por categorías para facilitar la búsqueda en el POS y en la tienda en línea.</p>
                <div class="mb-3">
                    <strong>Consejos:</strong>
                    <ul class="pl-3 mt-1 text-muted">
                        <li>Crea categorías descriptivas (ej: "Entradas", "Bebidas", "Postres").</li>
                        <li>Define el orden de clasificación para que las más importantes aparezcan primero.</li>
                    </ul>
                </div>
            '
        ],
        'seller.customers' => [
            'title' => 'Manual de Clientes',
            'icon' => 'la-users',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-users mr-1"></i> Gestión de Clientes</h5>
                <p>Base de datos de tus clientes registrados. Permite conocer sus datos de contacto e historial de compras.</p>
                <div class="mb-3">
                    <strong>Acciones:</strong>
                    <ul class="pl-3 mt-1 text-muted">
                        <li>Registra un nuevo cliente con sus datos de contacto y dirección para envíos rápidos.</li>
                        <li>Busca clientes registrados directamente durante el proceso de compra.</li>
                    </ul>
                </div>
            '
        ],
        'seller.pos.tables' => [
            'title' => 'Manual de Gestión de Mesas',
            'icon' => 'la-th',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-th mr-1"></i> Configuración de Mesas</h5>
                <p>Define la distribución de tus áreas físicas (Salón Principal, Terraza, etc.) y las mesas disponibles en cada una.</p>
                <div class="mb-3">
                    <strong>Administración de salón:</strong>
                    <ul class="pl-3 mt-1 text-muted">
                        <li>Crea nuevas áreas de atención.</li>
                        <li>Agrega mesas indicando su capacidad y orden de prioridad.</li>
                    </ul>
                </div>
            '
        ],
        'seller.pos.floorplan' => [
            'title' => 'Manual de Plano 2D',
            'icon' => 'la-border-all',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-border-all mr-1"></i> Plano Visual de Mesas</h5>
                <p>Dibuja la disposición visual de las mesas para que los mozos identifiquen fácilmente cuáles están ocupadas o libres.</p>
                <div class="mb-3">
                    <strong>Instrucciones:</strong>
                    <ul class="pl-3 mt-1 text-muted">
                        <li>Arrastra y coloca las mesas según la distribución física de tu local.</li>
                        <li>Guarda los cambios para que se reflejen de inmediato en la pantalla del POS.</li>
                    </ul>
                </div>
            '
        ],
        'seller.pos.staff' => [
            'title' => 'Manual de Personal / RR.HH',
            'icon' => 'la-users-cog',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-users-cog mr-1"></i> Gestión de Empleados</h5>
                <p>Registra a tu equipo de trabajo (mozos, cajeros, administradores) y define sus accesos al sistema.</p>
                <div class="mb-3">
                    <strong>Configuración:</strong>
                    <ul class="pl-3 mt-1 text-muted">
                        <li>Asigna un rol (Mozos, Cajeros) y contraseña de acceso.</li>
                        <li>Define el porcentaje de comisión para calcular sus incentivos de venta automáticamente.</li>
                    </ul>
                </div>
            '
        ],
        'seller.pos.hr.attendance' => [
            'title' => 'Manual de Asistencia',
            'icon' => 'la-calendar-check',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-calendar-check mr-1"></i> Control de Asistencia</h5>
                <p>Registra las horas de entrada y salida de tu personal para controlar la puntualidad y horas trabajadas.</p>
            '
        ],
        'seller.pos.hr.payroll' => [
            'title' => 'Manual de Planillas',
            'icon' => 'la-wallet',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-wallet mr-1"></i> Planilla de Sueldos y Comisiones</h5>
                <p>Calcula y registra los pagos periódicos a tus empleados, sumando su sueldo base y comisiones acumuladas.</p>
            '
        ],
        'seller.pos.staff.reports' => [
            'title' => 'Manual de Comisiones',
            'icon' => 'la-percentage',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-percentage mr-1"></i> Reporte de Comisiones de Mozos</h5>
                <p>Detalle de las ventas realizadas por cada miembro de tu personal y el cálculo de sus respectivas comisiones basadas en la tasa asignada.</p>
            '
        ],
        'seller.cash' => [
            'title' => 'Manual de Caja POS',
            'icon' => 'la-cash-register',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-cash-register mr-1"></i> Apertura, Movimientos y Cierre de Caja</h5>
                <p>Lleva el control exacto del dinero físico y transacciones del turno de venta actual.</p>
                <div class="mb-3">
                    <strong>Operaciones principales:</strong>
                    <ul class="pl-3 mt-1 text-muted">
                        <li><strong>Apertura de Caja:</strong> Se requiere ingresar un saldo inicial en efectivo para iniciar a vender.</li>
                        <li><strong>Ingresos y Egresos:</strong> Registra entradas o salidas manuales de efectivo (ej. pago de proveedores rápidos o sencillos).</li>
                        <li><strong>Arqueo y Cierre:</strong> Al finalizar el turno, haz el conteo físico, compara con el sistema y cierra caja.</li>
                    </ul>
                </div>
            '
        ],
        'seller.pos.bank_accounts' => [
            'title' => 'Manual de Caja y Bancos',
            'icon' => 'la-university',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-university mr-1"></i> Cuentas y Métodos de Cobro</h5>
                <p>Configura las cuentas bancarias o billeteras electrónicas (Yape, Plin) donde se destinarán los pagos del POS.</p>
            '
        ],
        'seller.registers' => [
            'title' => 'Manual de Cajas Registradoras',
            'icon' => 'la-cash-register',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-cash-register mr-1"></i> Cajas Registradoras</h5>
                <p>Administra los diferentes puntos de cobro físicos (ej. Caja 01, Caja 02, Caja Bar) y asigna personal.</p>
            '
        ],
        'seller.expenses' => [
            'title' => 'Manual de Gastos',
            'icon' => 'la-receipt',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-receipt mr-1"></i> Registro de Egresos y Gastos</h5>
                <p>Mantén un registro clasificado de todos los egresos del negocio (insumos, servicios, alquileres) para ver la rentabilidad real.</p>
            '
        ],
        'seller.invoicing' => [
            'title' => 'Manual de Facturación Electrónica',
            'icon' => 'la-file-invoice',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-file-invoice mr-1"></i> Configuración SUNAT</h5>
                <p>Gestión de tu información tributaria y estado de envíos electrónicos a SUNAT.</p>
                <div class="mb-3">
                    <strong>Secciones Clave:</strong>
                    <ul class="pl-3 mt-1 text-muted">
                        <li><strong>Datos de Empresa:</strong> Registra RUC, Razón Social y carga tu Certificado Digital.</li>
                        <li><strong>Series de Comprobante:</strong> Define las series para Boletas, Facturas y Notas de Venta.</li>
                    </ul>
                </div>
            '
        ],
        'seller.reports' => [
            'title' => 'Manual de Reportes de Ventas',
            'icon' => 'la-file-alt',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-file-alt mr-1"></i> Reportes del Negocio</h5>
                <p>Visualiza resúmenes de ventas filtrados por fechas, productos más vendidos, y métodos de pago utilizados.</p>
            '
        ],
        'seller.reports.advanced' => [
            'title' => 'Manual de Reportes Avanzados',
            'icon' => 'la-chart-bar',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-chart-bar mr-1"></i> Estadísticas Avanzadas</h5>
                <p>Análisis profundo del negocio, incluyendo márgenes de ganancia, costos de insumos, horas pico de ventas y rentabilidad neta.</p>
            '
        ],
        'seller.delivery' => [
            'title' => 'Manual de Integración Delivery',
            'icon' => 'la-motorcycle',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-motorcycle mr-1"></i> Canales de Delivery Externo</h5>
                <p>Configura las aplicaciones de delivery integradas (Rappi, PedidosYa, etc.) y las tarifas de envío por kilómetro.</p>
            '
        ],
        'seller.notifications' => [
            'title' => 'Manual de Notificaciones Push',
            'icon' => 'la-bell',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-bell mr-1"></i> Envió de Notificaciones Masivas</h5>
                <p>Envía mensajes push promocionales directamente a los teléfonos de todos tus clientes registrados para incentivar las ventas.</p>
            '
        ],
        'seller.qrmenu' => [
            'title' => 'Manual de QR de Carta',
            'icon' => 'la-qrcode',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-qrcode mr-1"></i> Carta Digital y Código QR</h5>
                <p>Obtén el código QR único de tu tienda. Puedes imprimirlo y colocarlo en las mesas para que los comensales escaneen y vean tu carta digital.</p>
            '
        ],
        'seller.external.order' => [
            'title' => 'Manual de Pedidos Externos',
            'icon' => 'la-globe',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-globe mr-1"></i> Registro Rápido de Pedidos Externos</h5>
                <p>Formulario ágil para registrar llamadas telefónicas o mensajes de WhatsApp directamente en tu panel y asignarlos a despacho.</p>
            '
        ],
        'seller.api.settings' => [
            'title' => 'Manual de API Settings',
            'icon' => 'la-plug',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-plug mr-1"></i> Llaves API y Integraciones</h5>
                <p>Configuración técnica para conectar tu sistema Lizto con plataformas externas y webhooks de sincronización.</p>
            '
        ],
        'seller.pricing' => [
            'title' => 'Manual de Suscripciones y Planes',
            'icon' => 'la-crown',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-crown mr-1"></i> Planes Lizto</h5>
                <p>Revisa las características de tu plan activo y realiza actualizaciones (Upgrade) a planes Premium para desbloquear facturación y reportes avanzados.</p>
            '
        ],
        'seller.inventory.items' => [
            'title' => 'Manual de Insumos y Materias Primas',
            'icon' => 'la-box',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-box mr-1"></i> Insumos y Materias Primas</h5>
                <p>Gestiona los ingredientes base que utilizas para preparar tus platos o productos.</p>
                <div class="mb-3">
                    <strong>Pasos para registrar un insumo:</strong>
                    <ol class="pl-3 mt-1 text-muted">
                        <li>Haz clic en <strong>Nuevo Insumo</strong>.</li>
                        <li>Completa el nombre, unidad de medida base (ej: Gramos, Kilogramos, Unidades) y stock inicial.</li>
                        <li>Define el costo unitario promedio para valorizar tu almacén.</li>
                    </ol>
                </div>
            '
        ],
        'seller.logistics.warehouses' => [
            'title' => 'Manual de Almacenes',
            'icon' => 'la-warehouse',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-warehouse mr-1"></i> Control de Stock por Almacén</h5>
                <p>Administra múltiples almacenes (Cocina, Barra, Almacén Central) y revisa el inventario disponible en cada uno de ellos.</p>
                <div class="mb-3">
                    <strong>Procedimiento operativo:</strong>
                    <ul class="pl-3 mt-1 text-muted">
                        <li><strong>Almacén por Defecto:</strong> Todo insumo comprado ingresa aquí de forma predeterminada.</li>
                        <li><strong>Almacenes de Preparación:</strong> Crea almacenes como "Cocina" o "Barra" para transferir stock desde el Almacén Principal.</li>
                        <li>Revisa los saldos de stock filtrando por almacén para cuadrar inventarios físicos.</li>
                    </ul>
                </div>
            '
        ],
        'seller.logistics.suppliers' => [
            'title' => 'Manual de Proveedores',
            'icon' => 'la-truck',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-truck mr-1"></i> Catálogo de Proveedores</h5>
                <p>Directorio de proveedores autorizados para compras del negocio.</p>
                <div class="mb-3">
                    <strong>Administración:</strong>
                    <ul class="pl-3 mt-1 text-muted">
                        <li>Registra sus datos de contacto, RUC o DNI y dirección.</li>
                        <li>Vincula a cada proveedor con las compras u órdenes de compra para analizar la frecuencia de abastecimiento y mejores precios.</li>
                    </ul>
                </div>
            '
        ],
        'seller.logistics.purchase_orders' => [
            'title' => 'Manual de Órdenes de Compra',
            'icon' => 'la-file-contract',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-file-contract mr-1"></i> Órdenes de Compra</h5>
                <p>Documento formal enviado a proveedores para solicitar mercancía.</p>
                <div class="mb-3">
                    <strong>Flujo de trabajo:</strong>
                    <ol class="pl-3 mt-1 text-muted">
                        <li>Crea una orden seleccionando el proveedor y agregando los insumos con cantidades y costos pactados.</li>
                        <li>El estado inicial es <strong>Pendiente</strong>.</li>
                        <li>Una vez aprobada, puedes descargar el PDF oficial para enviarlo por correo o WhatsApp a tu proveedor.</li>
                    </ol>
                </div>
            '
        ],
        'seller.logistics.receptions' => [
            'title' => 'Manual de Recepción de Mercadería',
            'icon' => 'la-truck-loading',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-truck-loading mr-1"></i> Recepciones de Mercadería</h5>
                <p>Registra el ingreso físico de insumos a tus almacenes cotejando contra una Orden de Compra.</p>
                <div class="mb-3">
                    <strong>Instrucciones de recepción:</strong>
                    <ol class="pl-3 mt-1 text-muted">
                        <li>Selecciona la Orden de Compra correspondiente en estado Pendiente.</li>
                        <li>Verifica físicamente las cantidades recibidas de cada insumo.</li>
                        <li>Si llega una cantidad menor, regístrala para mantener la diferencia como pendiente.</li>
                        <li>Confirma la recepción: esto incrementa automáticamente el stock en el almacén seleccionado y registra la entrada en el Kardex.</li>
                    </ol>
                </div>
            '
        ],
        'seller.inventory.purchases' => [
            'title' => 'Manual de Compras Directas',
            'icon' => 'la-receipt',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-receipt mr-1"></i> Registro de Compras Directas</h5>
                <p>Registra compras rápidas de insumos que no requirieron una orden de compra previa, afectando directamente el stock y la caja.</p>
                <div class="mb-3">
                    <strong>Pauta de registro:</strong>
                    <ol class="pl-3 mt-1 text-muted">
                        <li>Verifica que la <strong>Caja esté Abierta</strong> (requisito obligatorio).</li>
                        <li>Selecciona el proveedor y tipo de comprobante (Boleta o Factura).</li>
                        <li>Agrega los insumos comprados con sus respectivos costos unitarios.</li>
                        <li>Guarda el registro: el stock del almacén por defecto se incrementa y se genera un movimiento de egreso en tu sesión de caja abierta.</li>
                    </ol>
                </div>
            '
        ],
        'seller.inventory.recipes' => [
            'title' => 'Manual de Recetario (Cocina y Barra)',
            'icon' => 'la-utensils',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-utensils mr-1"></i> Recetario (Cocina y Barra)</h5>
                <p>Define la composición exacta de cada plato o bebida para automatizar los costos y el descuento de inventario.</p>
                <div class="mb-3">
                    <strong>¿Cómo estructurar una receta perfecta?</strong>
                    <ol class="pl-3 mt-1 text-muted">
                        <li>Selecciona el plato/bebida de tu catálogo y define el número de porciones que rinde la receta base (ej: 1 porción).</li>
                        <li>Agrega cada insumo indicando la <strong>Cantidad Bruta (Gross)</strong> necesaria (peso total antes de limpiar/preparar).</li>
                        <li>Especifica el <strong>% de Merma (Waste)</strong> (ej: 10% para papa pelada). El sistema calculará automáticamente la cantidad neta útil.</li>
                        <li>Guarda la receta. A partir de ahora, cada vez que vendas este producto en el POS, el sistema descontará del almacén el insumo bruto exacto.</li>
                    </ol>
                </div>
            '
        ],
        'seller.logistics.transfers' => [
            'title' => 'Manual de Transferencias de Stock',
            'icon' => 'la-exchange-alt',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-exchange-alt mr-1"></i> Transferencias de Inventario</h5>
                <p>Mueve stock de insumos entre diferentes almacenes (ej: de Almacén Central a Barra).</p>
                <div class="mb-3">
                    <strong>Pasos para transferir:</strong>
                    <ol class="pl-3 mt-1 text-muted">
                        <li>Crea una transferencia especificando el almacén de origen y el almacén de destino.</li>
                        <li>Agrega los insumos y las cantidades a mover.</li>
                        <li>Confirma la transferencia: el stock se deduce del origen y se incrementa en el destino, quedando registrado en el Kardex.</li>
                    </ol>
                </div>
            '
        ],
        'seller.inventory.wastes' => [
            'title' => 'Manual de Mermas / Desperdicios',
            'icon' => 'la-trash-alt',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-trash-alt mr-1"></i> Registro de Mermas y Desperdicios</h5>
                <p>Registra pérdidas accidentales, productos vencidos o mermas fuera de receta para justificar descuadres de stock.</p>
                <div class="mb-3">
                    <strong>Procedimiento:</strong>
                    <ol class="pl-3 mt-1 text-muted">
                        <li>Selecciona el insumo y la cantidad desperdiciada.</li>
                        <li>Elige el motivo de la pérdida (ej: Vencimiento, Rotura, Deterioro).</li>
                        <li>Guarda la merma: esto reducirá el stock físico y registrará una salida por ajuste en el Kardex.</li>
                    </ol>
                </div>
            '
        ],
        'seller.inventory.sales' => [
            'title' => 'Manual de Ventas de Insumos',
            'icon' => 'la-shopping-basket',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-shopping-basket mr-1"></i> Ventas Directas de Inventario</h5>
                <p>Registra ventas rápidas de materias primas o insumos a terceros (ej: vender sacos de papa o botellas a otros locales).</p>
                <div class="mb-3">
                    <strong>Pauta de registro:</strong>
                    <ol class="pl-3 mt-1 text-muted">
                        <li>Verifica que la <strong>Caja esté Abierta</strong>.</li>
                        <li>Selecciona el cliente/proveedor y añade los insumos con su precio de venta unitario.</li>
                        <li>Confirma: se genera una salida de stock y el dinero ingresa a la sesión de caja activa.</li>
                    </ol>
                </div>
            '
        ],
        'seller.inventory.kardex' => [
            'title' => 'Manual de Kardex',
            'icon' => 'la-history',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-history mr-1"></i> Kardex de Movimientos</h5>
                <p>El libro contable de tu inventario. Muestra la trazabilidad absoluta de cada insumo.</p>
                <div class="mb-3">
                    <strong>¿Cómo leer el Kardex?</strong>
                    <ul class="pl-3 mt-1 text-muted">
                        <li><strong>Entradas:</strong> Compras directas, devoluciones por anulación, recepciones de órdenes de compra.</li>
                        <li><strong>Salidas:</strong> Ventas de POS (consumos de recetas), mermas, transferencias salientes.</li>
                        <li>Monitorea el <strong>Stock Restante (Balance)</strong> tras cada transacción para detectar descuadres.</li>
                    </ul>
                </div>
            '
        ],
        'seller.logistics.reports' => [
            'title' => 'Manual de Valoración y Costos',
            'icon' => 'la-calculator',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-calculator mr-1"></i> Valoración y Costos de Inventario</h5>
                <p>Análisis financiero de tu inventario almacenado.</p>
                <div class="mb-3">
                    <strong>Métricas clave:</strong>
                    <ul class="pl-3 mt-1 text-muted">
                        <li>Visualiza el costo total invertido en materias primas utilizando el método promedio ponderado.</li>
                        <li>Identifica qué almacenes tienen mayor capital inmovilizado.</li>
                    </ul>
                </div>
            '
        ],
        'seller.inventory.tax-report' => [
            'title' => 'Manual de Reporte Tributario de Insumos',
            'icon' => 'la-file-invoice-dollar',
            'html' => '
                <h5 class="mb-3 text-success font-weight-bold"><i class="las la-file-invoice-dollar mr-1"></i> Reporte Tributario de Inventario</h5>
                <p>Consolidado de compras y ventas de insumos estructurado para auditorías internas o contabilidad tributaria.</p>
            '
        ],
    ];

    $currentManual = $manuals[$currentRoute] ?? [
        'title' => 'Ayuda de Usuario',
        'icon' => 'la-question-circle',
        'html' => '
            <h5 class="mb-3 text-success font-weight-bold"><i class="las la-question-circle mr-1"></i> Manual de Usuario General</h5>
            <p>Bienvenido al Panel de Administración de tu negocio. Desde el menú lateral izquierdo puedes navegar entre todas las opciones de gestión.</p>
            <div class="mb-3">
                <strong>Consejos Generales:</strong>
                <ul class="pl-3 mt-1 text-muted">
                    <li>Utiliza el botón de Ayuda en cualquier vista para ver los detalles e instrucciones correspondientes a esa pantalla.</li>
                    <li>Presiona <kbd style="padding: 2px 6px !important; font-size: 10px !important;">Ctrl+K</kbd> en cualquier momento para ver la guía de atajos de teclado rápidos.</li>
                </ul>
            </div>
        '
    ];
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Panel Vendedor' }} — {{ $store->name ?? 'Lizto Delivery' }}</title>
    <meta name="description" content="Panel de gestión para vendedores de Lizto — delivery, POS y administración">
    <link rel="shortcut icon" href="{{ siteFavicon() }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/global/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/global/css/all.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/global/css/line-awesome.min.css') }}">
    <style>
    /* ═══════════════════════════════════════════════
       DESIGN SYSTEM — SELLER PANEL v3.0 PREMIUM
       ═══════════════════════════════════════════════ */
    :root {
        /* Brand */
        --s-primary: #16a34a;
        --s-accent: #22c55e;
        --s-accent-dark: #16a34a;
        --s-accent-deeper: #15803d;
        --s-accent-light: #dcfce7;
        --s-accent-glow: rgba(34,197,94,.18);

        /* Surfaces */
        --s-bg: #f0f2f5;
        --s-surface: #ffffff;
        --s-surface-2: #f8fafb;
        --s-bg-light: #f8fafb;
        --s-bg-card: #ffffff;
        --s-border: #e4e9ef;
        --s-border-light: rgba(0,0,0,.06);

        /* Sidebar */
        --s-side: #0f1923;
        --s-side-2: #162130;
        --s-side-border: rgba(255,255,255,.06);
        --s-side-text: rgba(255,255,255,.5);
        --s-side-text-active: #ffffff;
        --s-side-hover: rgba(255,255,255,.05);

        /* Text */
        --s-text: #0f172a;
        --s-text-2: #475569;
        --s-text-3: #94a3b8;
        --s-text-primary: #0f172a;
        --s-text-secondary: #475569;
        --s-text-muted: #94a3b8;

        /* Semantic */
        --s-success: #22c55e;
        --s-success-bg: #dcfce7;
        --s-success-text: #15803d;
        --s-warning: #f59e0b;
        --s-warning-bg: #fef3c7;
        --s-warning-text: #92400e;
        --s-danger: #ef4444;
        --s-danger-bg: #fee2e2;
        --s-danger-text: #991b1b;
        --s-info: #3b82f6;
        --s-info-bg: #dbeafe;
        --s-info-text: #1e40af;
        --s-purple: #8b5cf6;
        --s-purple-bg: #ede9fe;
        --s-purple-text: #6d28d9;

        /* Shadows */
        --s-shadow-sm: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
        --s-shadow: 0 4px 16px rgba(0,0,0,.08), 0 1px 4px rgba(0,0,0,.04);
        --s-shadow-lg: 0 10px 40px rgba(0,0,0,.12), 0 4px 12px rgba(0,0,0,.06);
        --s-shadow-accent: 0 8px 24px rgba(34,197,94,.25);

        /* Radii */
        --s-radius-sm: 8px;
        --s-radius: 14px;
        --s-radius-lg: 20px;
        --s-radius-xl: 28px;

        /* Typography */
        --s-font: 'Plus Jakarta Sans', 'Inter', system-ui, sans-serif;

        /* Sidebar Width */
        --s-sidebar-w: 260px;
        --s-topbar-h: 64px;
    }

    *, *::before, *::after { box-sizing: border-box; }
    html { scroll-behavior: smooth; }

    body {
        font-family: var(--s-font);
        background: var(--s-bg);
        color: var(--s-text);
        margin: 0;
        min-height: 100vh;
        font-size: 14px;
        line-height: 1.5;
        -webkit-font-smoothing: antialiased;
    }

    /* ─── LAYOUT ─── */
    .s-wrap { display: flex; min-height: 100vh; }

    /* ─── SIDEBAR ─── */
    .s-side {
        width: var(--s-sidebar-w);
        background: var(--s-side);
        position: fixed;
        top: 0; left: 0; bottom: 0;
        z-index: 200;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-right: 1px solid var(--s-side-border);
        transition: transform .3s cubic-bezier(.4,0,.2,1), width .25s cubic-bezier(.4,0,.2,1);
    }

    /* Sidebar top gradient accent */
    .s-side::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 200px;
        background: radial-gradient(ellipse at top left, rgba(34,197,94,.12) 0%, transparent 70%);
        pointer-events: none;
    }

    .s-side-scroll { flex: 1; overflow-y: auto; overflow-x: hidden; padding-bottom: 16px; }
    .s-side-scroll::-webkit-scrollbar { width: 4px; }
    .s-side-scroll::-webkit-scrollbar-track { background: transparent; }
    .s-side-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 4px; }

    /* Brand */
    .s-brand {
        padding: 20px 20px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid var(--s-side-border);
        position: relative;
        z-index: 1;
    }
    .s-brand-logo {
        width: 44px; height: 44px;
        border-radius: 13px;
        background: linear-gradient(135deg, #22c55e, #16a34a);
        display: grid; place-items: center;
        font-size: 22px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(34,197,94,.35);
    }
    .s-brand-info h2 {
        font-size: 15px; font-weight: 800; color: #fff;
        margin: 0; letter-spacing: -.3px; line-height: 1.2;
    }
    .s-brand-info span {
        font-size: 10px; color: var(--s-accent);
        text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;
    }

    /* Nav sections */
    .s-nav-section { padding: 16px 0 0; }
    .s-nav-label {
        padding: 0 20px 8px;
        font-size: 9.5px; font-weight: 700;
        text-transform: uppercase; letter-spacing: 1.8px;
        color: rgba(255,255,255,.2);
        display: block;
    }

    /* Nav links */
    .s-nav a {
        display: flex; align-items: center; gap: 10px;
        padding: 9px 20px;
        color: var(--s-side-text);
        text-decoration: none;
        font-size: 13px; font-weight: 600;
        letter-spacing: -.1px;
        border-left: 3px solid transparent;
        transition: all .18s cubic-bezier(.4,0,.2,1);
        position: relative;
        white-space: nowrap;
        overflow: hidden;
        border-radius: 0;
    }
    .s-nav a .s-nav-icon {
        width: 30px; height: 30px;
        border-radius: 8px;
        display: grid; place-items: center;
        font-size: 15px;
        flex-shrink: 0;
        transition: all .18s;
        background: rgba(255,255,255,.04);
    }
    .s-nav a:hover {
        color: #fff;
        background: var(--s-side-hover);
        border-left-color: rgba(34,197,94,.5);
    }
    .s-nav a:hover .s-nav-icon {
        background: rgba(34,197,94,.15);
        color: var(--s-accent);
    }
    .s-nav a.active {
        color: #fff;
        background: linear-gradient(90deg, rgba(34,197,94,.12), transparent);
        border-left-color: var(--s-accent);
    }
    .s-nav a.active .s-nav-icon {
        background: rgba(34,197,94,.2);
        color: var(--s-accent);
    }

    /* Sidebar footer */
    .s-side-foot {
        padding: 12px 16px 20px;
        border-top: 1px solid var(--s-side-border);
        flex-shrink: 0;
    }
    .s-side-foot a {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 12px; color: rgba(255,255,255,.35);
        text-decoration: none; font-size: 12px; font-weight: 600;
        border-radius: 10px; transition: all .18s;
    }
    .s-side-foot a:hover { color: #fff; background: rgba(255,255,255,.06); }
    .s-side-foot a i { font-size: 16px; }

    /* ─── MAIN AREA ─── */
    .s-main { margin-left: var(--s-sidebar-w); flex: 1; min-width: 0; display: flex; flex-direction: column; }

    /* Topbar */
    .s-topbar {
        height: var(--s-topbar-h);
        background: var(--s-surface);
        border-bottom: 1px solid var(--s-border);
        display: flex; align-items: center; justify-content: space-between;
        padding: 0 28px;
        position: sticky; top: 0; z-index: 150;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }
    .s-hamburger {
        display: inline-flex; align-items: center; justify-content: center;
        width: 38px; height: 38px;
        border: 1.5px solid var(--s-border);
        border-radius: 10px;
        background: var(--s-surface-2);
        color: var(--s-text-2);
        cursor: pointer;
        font-size: 18px;
        transition: all .18s;
        flex-shrink: 0;
        position: relative;
        z-index: 1;
        pointer-events: auto;
    }
    .s-hamburger:hover {
        background: var(--s-accent-light);
        border-color: var(--s-accent);
        color: var(--s-accent-dark);
    }
    .s-topbar-title {
        font-size: 18px; font-weight: 800;
        display: flex; align-items: center; gap: 10px;
        letter-spacing: -.4px; color: var(--s-text);
        margin: 0;
    }
    .s-topbar-title .s-title-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        background: var(--s-accent-light);
        display: grid; place-items: center;
        color: var(--s-accent-dark); font-size: 18px;
    }
    .s-topbar-right { display: flex; align-items: center; gap: 10px; }
    .s-topbar-time {
        font-size: 12px; color: var(--s-text-3); font-weight: 500;
        background: var(--s-surface-2);
        border: 1px solid var(--s-border);
        padding: 6px 12px; border-radius: 8px;
        display: flex; align-items: center; gap: 6px;
    }

    /* Page content */
    .s-content {
        padding: 28px;
        flex: 1;
        animation: sFadeIn .3s cubic-bezier(.4,0,.2,1);
    }
    @keyframes sFadeIn {
        from { opacity:0; transform:translateY(8px); }
        to   { opacity:1; transform:translateY(0); }
    }

    /* ─── CARDS ─── */
    .s-card {
        background: var(--s-surface);
        border: 1px solid var(--s-border);
        border-radius: var(--s-radius-lg);
        padding: 24px;
        box-shadow: var(--s-shadow-sm);
        transition: box-shadow .2s;
    }
    .s-card:hover { box-shadow: var(--s-shadow); }
    .s-card-title {
        font-size: 15px; font-weight: 800;
        margin: 0 0 20px;
        display: flex; align-items: center; gap: 9px;
        letter-spacing: -.2px; color: var(--s-text);
    }
    .s-card-title i { color: var(--s-accent-dark); font-size: 18px; }

    /* ─── STAT CARDS ─── */
    .s-stat {
        background: var(--s-surface);
        border: 1px solid var(--s-border);
        border-radius: var(--s-radius);
        padding: 20px;
        display: flex; align-items: center; gap: 14px;
        transition: all .22s cubic-bezier(.4,0,.2,1);
        cursor: default;
    }
    .s-stat:hover { transform: translateY(-3px); box-shadow: var(--s-shadow); }
    .s-stat-icon {
        width: 52px; height: 52px;
        border-radius: 14px;
        display: grid; place-items: center;
        font-size: 22px; flex-shrink: 0;
    }
    .s-stat-icon.green  { background: var(--s-success-bg);  color: var(--s-accent-dark); }
    .s-stat-icon.blue   { background: var(--s-info-bg);      color: var(--s-info); }
    .s-stat-icon.amber  { background: var(--s-warning-bg);   color: var(--s-warning); }
    .s-stat-icon.red    { background: var(--s-danger-bg);    color: var(--s-danger); }
    .s-stat-icon.purple { background: var(--s-purple-bg);    color: var(--s-purple); }
    .s-stat strong { font-size: 26px; font-weight: 900; display: block; letter-spacing: -.5px; color: var(--s-text); line-height: 1.1; }
    .s-stat small  { font-size: 12px; color: var(--s-text-3); font-weight: 600; margin-top: 2px; display: block; }

    /* ─── BUTTONS ─── */
    .s-btn {
        display: inline-flex !important; align-items: center; gap: 7px;
        padding: 10px 18px;
        border: none; border-radius: 11px;
        font-size: 13px; font-weight: 700;
        cursor: pointer; transition: all .18s cubic-bezier(.4,0,.2,1);
        font-family: var(--s-font); letter-spacing: -.1px;
        text-decoration: none !important; line-height: 1.4;
    }
    .s-btn:hover { transform: translateY(-1px); }
    .s-btn:active { transform: translateY(0) scale(.98); }
    .s-btn i { font-size: 15px; }

    .s-btn-primary {
        background: linear-gradient(135deg, #22c55e, #16a34a) !important;
        color: #fff !important;
        box-shadow: 0 4px 14px rgba(34,197,94,.28);
    }
    .s-btn-primary:hover {
        background: linear-gradient(135deg, #16a34a, #15803d) !important;
        box-shadow: 0 6px 20px rgba(34,197,94,.4);
    }
    .s-btn-outline {
        background: transparent !important;
        border: 1.5px solid var(--s-border) !important;
        color: var(--s-text-2) !important;
    }
    .s-btn-outline:hover {
        border-color: var(--s-accent) !important;
        color: var(--s-accent-dark) !important;
        background: var(--s-accent-light) !important;
    }
    .s-btn-ghost {
        background: transparent !important;
        color: var(--s-text-3) !important;
        border: none !important;
    }
    .s-btn-ghost:hover { background: var(--s-surface-2) !important; color: var(--s-text) !important; }
    .s-btn-danger {
        background: linear-gradient(135deg, #ef4444, #dc2626) !important;
        color: #fff !important;
        box-shadow: 0 4px 14px rgba(239,68,68,.25);
    }
    .s-btn-danger:hover { box-shadow: 0 6px 20px rgba(239,68,68,.35) !important; }
    .s-btn-amber {
        background: linear-gradient(135deg, #f59e0b, #d97706) !important;
        color: #fff !important;
    }
    .s-btn-sm { padding: 7px 13px; font-size: 12px; border-radius: 9px; }
    .s-btn-sm i { font-size: 13px; }
    .s-btn-xs { padding: 5px 10px; font-size: 11px; border-radius: 7px; }
    .s-btn-lg { padding: 13px 24px; font-size: 14px; border-radius: 13px; }

    /* ─── BADGES ─── */
    .s-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 4px 10px;
        border-radius: 30px; font-size: 11px; font-weight: 700;
        letter-spacing: .2px; line-height: 1.3;
    }
    .s-badge i { font-size: 11px; }
    .s-badge-green  { background: var(--s-success-bg);  color: var(--s-success-text); }
    .s-badge-amber  { background: var(--s-warning-bg);  color: var(--s-warning-text); }
    .s-badge-blue   { background: var(--s-info-bg);     color: var(--s-info-text); }
    .s-badge-red    { background: var(--s-danger-bg);   color: var(--s-danger-text); }
    .s-badge-purple { background: var(--s-purple-bg);   color: var(--s-purple-text); }
    .s-badge-gray   { background: var(--s-surface-2);   color: var(--s-text-3); border: 1px solid var(--s-border); }

    /* ─── INPUTS ─── */
    .s-input {
        padding: 10px 14px;
        border: 1.5px solid var(--s-border);
        border-radius: 11px;
        font-size: 13px; width: 100%;
        font-family: var(--s-font);
        background: var(--s-surface-2);
        color: var(--s-text);
        transition: all .18s;
        line-height: 1.4;
    }
    .s-input:focus {
        outline: none;
        border-color: var(--s-accent);
        box-shadow: 0 0 0 3px var(--s-accent-glow);
        background: #fff;
    }
    .s-input::placeholder { color: var(--s-text-3); }
    .s-input-group { display: flex; flex-direction: column; gap: 5px; }
    .s-input-label {
        font-size: 11px; font-weight: 700;
        color: var(--s-text-2);
        letter-spacing: .2px; text-transform: uppercase;
    }

    /* ─── TABLE ─── */
    .s-table-wrapper { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 8px; }
    .s-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .s-table th, .s-table td {
        padding: 13px 16px;
        text-align: left;
        border-bottom: 1px solid var(--s-border);
    }
    .s-table th {
        font-size: 10.5px; text-transform: uppercase;
        letter-spacing: 1px; color: var(--s-text-3); font-weight: 700;
        background: var(--s-surface-2);
        white-space: nowrap;
    }
    .s-table th:first-child { border-radius: 10px 0 0 10px; }
    .s-table th:last-child  { border-radius: 0 10px 10px 0; }
    .s-table tbody tr { transition: background .12s; }
    .s-table tbody tr:hover { background: rgba(34,197,94,.025); }
    .s-table td { color: var(--s-text); }

    /* ─── ALERTS ─── */
    .s-alert {
        display: flex; align-items: center; gap: 10px;
        padding: 13px 18px;
        border-radius: var(--s-radius);
        font-size: 13px; font-weight: 600;
        animation: sFadeIn .3s;
        margin: 0 28px 16px;
    }
    .s-alert-success { background: var(--s-success-bg); color: var(--s-success-text); border-left: 3px solid var(--s-success); }
    .s-alert-error   { background: var(--s-danger-bg);  color: var(--s-danger-text);  border-left: 3px solid var(--s-danger); }

    /* ─── MODAL ─── */
    .s-modal {
        position: fixed; inset: 0; z-index: 9999;
        display: none; align-items: center; justify-content: center;
    }
    .s-modal.open { display: flex; }
    .s-modal-bg {
        position: absolute; inset: 0;
        background: rgba(0,0,0,.5);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        animation: sBgIn .2s;
    }
    @keyframes sBgIn { from { opacity:0; } to { opacity:1; } }
    .s-modal-box {
        position: relative; z-index: 1;
        background: var(--s-surface);
        border-radius: var(--s-radius-xl);
        width: 100%; max-width: 540px;
        max-height: 90vh; overflow-y: auto;
        margin: 16px;
        box-shadow: var(--s-shadow-lg);
        animation: sBoxIn .25s cubic-bezier(.34,1.56,.64,1);
    }
    @keyframes sBoxIn { from { opacity:0; transform:scale(.94) translateY(10px); } to { opacity:1; transform:scale(1) translateY(0); } }
    .s-modal-box::-webkit-scrollbar { width: 5px; }
    .s-modal-box::-webkit-scrollbar-thumb { background: var(--s-border); border-radius: 5px; }
    .s-modal-head {
        padding: 24px 24px 0;
        display: flex; align-items: flex-start; justify-content: space-between;
        margin-bottom: 20px;
    }
    .s-modal-title { font-size: 18px; font-weight: 800; margin: 0; color: var(--s-text); letter-spacing: -.3px; }
    .s-modal-close {
        width: 32px; height: 32px; border: none;
        background: var(--s-surface-2); border-radius: 50%;
        font-size: 16px; cursor: pointer;
        display: grid; place-items: center;
        color: var(--s-text-3); transition: all .15s;
        flex-shrink: 0; margin-top: 2px;
    }
    .s-modal-close:hover { background: var(--s-border); color: var(--s-text); }
    .s-modal-body { padding: 0 24px 24px; }

    /* ─── EMPTY STATE ─── */
    .s-empty {
        text-align: center; padding: 48px 24px; color: var(--s-text-3);
    }
    .s-empty i { font-size: 52px; display: block; margin-bottom: 12px; color: var(--s-border); }
    .s-empty p { font-size: 14px; margin: 0; color: var(--s-text-3); }

    /* ─── FORM GRID ─── */
    .s-form-grid { display: grid; gap: 14px; }
    .s-form-grid-2 { grid-template-columns: 1fr 1fr; }
    .s-form-grid-3 { grid-template-columns: 1fr 1fr 1fr; }
    .s-divider { border: none; border-top: 1px solid var(--s-border); margin: 16px 0; }

    /* ─── PAGE HEADER ─── */
    .s-page-head {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
    }
    .s-page-head h1 {
        font-size: 22px; font-weight: 800; margin: 0;
        display: flex; align-items: center; gap: 10px;
        letter-spacing: -.4px; color: var(--s-text);
    }
    .s-page-head h1 .s-icon-wrap {
        width: 40px; height: 40px; border-radius: 12px;
        background: var(--s-accent-light);
        display: grid; place-items: center;
        color: var(--s-accent-dark); font-size: 20px;
    }
    .s-page-head-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

    /* ─── GRID UTILS ─── */
    .s-grid-4 { display: grid; grid-template-columns: repeat(auto-fit,minmax(190px,1fr)); gap: 14px; }
    .s-grid-3 { display: grid; grid-template-columns: repeat(auto-fit,minmax(240px,1fr)); gap: 16px; }
    .s-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

    /* ─── PREMIUM CTA overlay ─── */
    .s-premium-overlay {
        position: fixed; inset: 0; z-index: 99998;
        display: none; align-items: center; justify-content: center;
        background: rgba(0,0,0,.6);
        backdrop-filter: blur(6px);
    }
    .s-premium-overlay.open { display: flex; }
    .s-premium-box {
        background: linear-gradient(135deg, #0f1923, #162130);
        border: 1px solid rgba(34,197,94,.25);
        border-radius: 28px;
        padding: 40px;
        max-width: 440px; width: calc(100% - 32px);
        text-align: center;
        box-shadow: 0 24px 60px rgba(0,0,0,.5), 0 0 0 1px rgba(34,197,94,.15);
        animation: sBoxIn .3s cubic-bezier(.34,1.56,.64,1);
    }
    .s-premium-icon {
        width: 72px; height: 72px; border-radius: 20px;
        background: linear-gradient(135deg, #22c55e20, #22c55e10);
        border: 1px solid rgba(34,197,94,.3);
        display: grid; place-items: center;
        font-size: 36px; margin: 0 auto 20px;
    }
    .s-premium-box h2 { color: #fff; font-size: 22px; font-weight: 800; margin: 0 0 8px; }
    .s-premium-box p  { color: rgba(255,255,255,.5); font-size: 14px; margin: 0 0 24px; }
    .s-premium-stars  { color: #fbbf24; font-size: 18px; letter-spacing: 3px; margin-bottom: 20px; }
    .s-premium-close-btn {
        background: none; border: 1px solid rgba(255,255,255,.15);
        color: rgba(255,255,255,.5); padding: 10px 18px; border-radius: 10px;
        font-size: 13px; cursor: pointer; font-family: var(--s-font); font-weight: 600;
        margin-top: 10px; transition: all .2s;
    }
    .s-premium-close-btn:hover { border-color: rgba(255,255,255,.3); color: #fff; }

    /* ─── HAMBURGER ─── */
    .s-hamburger {
        display: flex;
        width: 38px; height: 38px;
        border: 1.5px solid var(--s-border);
        background: var(--s-surface); border-radius: 10px;
        align-items: center; justify-content: center;
        cursor: pointer; font-size: 18px; color: var(--s-text-2);
        transition: all .18s;
        flex-shrink: 0;
    }
    .s-hamburger:hover { background: var(--s-surface-2); }

    /* ─── SIDEBAR COLLAPSED ─── */
    .s-side.collapsed { width: 68px; }
    .s-side.collapsed .s-brand-info,
    .s-side.collapsed .s-nav-label,
    .s-side.collapsed .s-nav-text,
    .s-side.collapsed .s-side-foot .s-foot-text { display: none; }
    .s-side.collapsed .s-brand { padding: 16px 12px; justify-content: center; }
    .s-side.collapsed .s-brand-logo { width: 38px; height: 38px; font-size: 18px; }
    .s-side.collapsed .s-nav a { padding: 10px 0; justify-content: center; gap: 0; border-left: none; }
    .s-side.collapsed .s-nav a .s-nav-icon { margin: 0; font-size: 20px; }
    .s-side.collapsed .s-nav-section { padding: 12px 0 0; }
    .s-side.collapsed .s-side-foot a { padding: 10px 0; justify-content: center; }
    .s-side.collapsed + .s-main { margin-left: 68px; }
    .s-side.collapsed .s-nav a { position: relative; }
    #sb-tooltip {
        position: fixed; background: #1e293b; color: #f1f5f9; border-radius: 6px;
        padding: 5px 10px; font-size: 12px; font-weight: 600;
        white-space: nowrap; pointer-events: none; opacity: 0;
        transition: opacity .12s; z-index: 99999;
        box-shadow: 0 4px 12px rgba(0,0,0,.25);
    }

    /* ─── RESPONSIVE ─── */
    @media (max-width: 1024px) {
        .s-form-grid-3 { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 991px) {
        .s-grid-2,
        [style*="grid-template-columns: 360px 1fr"],
        [style*="grid-template-columns: 340px 1fr"],
        [style*="grid-template-columns: 1fr 2fr"],
        [style*="grid-template-columns: 1fr 1.8fr"],
        [style*="grid-template-columns: 1fr 1.5fr"],
        [style*="grid-template-columns: 1fr 1.2fr"],
        [style*="grid-template-columns: 1.1fr 0.9fr"],
        [style*="grid-template-columns: 2fr 2fr 1.2fr auto"] {
            grid-template-columns: 1fr !important;
        }
    }
    @media (max-width: 768px) {
        :root { --s-sidebar-w: 0px; }
        .s-side { transform: translateX(-260px); width: 260px; }
        .s-side.collapsed { width: 260px; }
        .s-side.collapsed .s-brand-info,
        .s-side.collapsed .s-nav-label,
        .s-side.collapsed .s-nav-text,
        .s-side.collapsed .s-side-foot .s-foot-text { display: block; }
        .s-side.collapsed .s-nav a { padding: 9px 20px; justify-content: flex-start; gap: 10px; border-left: 3px solid transparent; }
        .s-side.collapsed .s-nav a .s-nav-icon { font-size: inherit; }
        .s-side.collapsed .s-brand { padding: 20px 20px 16px; justify-content: flex-start; }
        .s-side.collapsed .s-brand-logo { width: 44px; height: 44px; font-size: 22px; }
        .s-side.collapsed .s-side-foot a { padding: 9px 20px; justify-content: flex-start; }
        .s-side.open { transform: translateX(0); }
        .s-main { margin-left: 0; }
        .s-side.collapsed + .s-main { margin-left: 0; }
        .s-content { padding: 16px; }
        .s-grid-2 { grid-template-columns: 1fr !important; }
        .s-form-grid-2 { grid-template-columns: 1fr; }
        .s-form-grid-3 { grid-template-columns: 1fr; }
        .s-topbar { padding: 0 16px; }
        .s-topbar-time, 
        .s-topbar-right button[onclick="toggleKbShortcuts()"], 
        .s-topbar-right button[onclick="openHelpDrawer()"] { 
            display: none !important; 
        }
        .s-topbar-title {
            font-size: 14px;
        }
        .s-topbar-title .s-title-icon {
            width: 28px;
            height: 28px;
            font-size: 14px;
        }
        .s-page-head { flex-direction: column; align-items: flex-start; }
    }
    @media (max-width: 480px) {
        .s-grid-4 { grid-template-columns: 1fr 1fr; }
    }

    /* ─── TOUCH-SCREEN ENHANCEMENTS ─── */
    @media (hover: none) and (pointer: coarse) {
        .s-btn, button { min-height: 42px; min-width: 42px; }
        .pos-prod { min-height: 140px; }
        .pos-prod-img { height: 110px; }
        .floor-table { width: 90px; height: 90px; font-size: 13px; }
        .s-table th, .s-table td { padding: 14px 16px; }
        input.s-input, select.s-input { padding: 14px 16px; font-size: 16px; }
        .s-nav a { padding: 12px 20px; font-size: 14px; }
        .s-nav a .s-nav-icon { width: 36px; height: 36px; font-size: 17px; }
    }
    html.is-touch .s-btn, html.is-touch button { min-height: 42px; min-width: 42px; }
    html.is-touch .pos-prod { min-height: 140px; }
    html.is-touch .pos-prod-img { height: 110px; }
    html.is-touch .floor-table { width: 90px; height: 90px; font-size: 13px; }
    html.is-touch .s-table th, html.is-touch .s-table td { padding: 14px 16px; }
    html.is-touch input.s-input, html.is-touch select.s-input { padding: 14px 16px; font-size: 16px; }
    html.is-touch .s-nav a .s-nav-icon { width: 36px; height: 36px; font-size: 17px; }
    @media (max-width: 1024px) {
        kbd { display: none !important; }
    }
    html.is-touch kbd { display: none !important; }
    #kb-overlay kbd {
        background: #1e293b !important;
        border: 1px solid #0f172a !important;
        color: #ffffff !important;
        padding: 4px 10px !important;
        border-radius: 6px !important;
        font-size: 11px !important;
        font-weight: 800 !important;
        font-family: monospace !important;
        min-width: 32px !important;
        display: inline-block !important;
        text-align: center !important;
        box-shadow: 0 2px 0 #0f172a !important;
        text-shadow: none !important;
    }
    /* Slide-out Help Drawer */
    .s-help-drawer {
        position: fixed;
        top: 0;
        right: -420px;
        width: 400px;
        height: 100vh;
        background: var(--s-surface);
        box-shadow: -4px 0 24px rgba(0,0,0,0.15);
        z-index: 99999;
        transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        border-left: 1px solid var(--s-border);
    }
    .s-help-drawer.open {
        right: 0;
    }
    .s-help-drawer-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--s-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--s-surface-2);
    }
    .s-help-drawer-body {
        padding: 24px;
        flex: 1;
        overflow-y: auto;
        font-size: 13.5px;
        color: var(--s-text-2);
        line-height: 1.6;
    }
    .s-help-drawer-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.4);
        z-index: 99998;
        display: none;
        backdrop-filter: blur(2px);
    }
    @media (max-width: 480px) {
        .s-help-drawer {
            width: 100%;
            right: -100%;
        }
    }
    </style>
    @stack('style')
</head>
<body>
<div class="s-wrap">
    <!-- SIDEBAR -->
    <aside class="s-side" id="s-sidebar">
        <div class="s-brand" style="position: relative; display: flex; align-items: center; justify-content: space-between; gap: 8px;">
            <form id="logoUploadForm" action="{{ route('seller.logo.update') }}" method="POST" enctype="multipart/form-data" style="margin:0; padding:0; display:flex; align-items:center; gap:12px; flex: 1;">
                @csrf
                <div class="s-brand-logo-container" style="position: relative; cursor: pointer; width: 44px; height: 44px; border-radius: 13px; overflow: hidden; flex-shrink: 0;" onclick="document.getElementById('logoInput').click();" title="Cambiar Logo">
                    @if($store && $store->image)
                        <img src="{{ getImage('assets/images/store/' . $store->image) }}" alt="Logo" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <div class="s-brand-logo" style="width: 100%; height: 100%; border-radius: 0; box-shadow: none;">🏪</div>
                    @endif
                    <div class="s-logo-hover-overlay" style="position: absolute; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s;">
                        <i class="las la-camera" style="color: #fff; font-size: 16px;"></i>
                    </div>
                </div>
                <input type="file" id="logoInput" name="logo" accept="image/*" style="display: none;" onchange="document.getElementById('logoUploadForm').submit();">
                <div class="s-brand-info">
                    <h2>{{ Str::limit($store->name ?? 'Mi Tienda', 14) }}</h2>
                    <span style="font-size: 9px; opacity: 0.8; letter-spacing: 0.5px;">Panel Vendedor</span>
                </div>
            </form>
            @if($store && $store->hasPremiumPackage())
            <form id="coverVideoUploadForm" action="{{ route('seller.cover.video.update') }}" method="POST" enctype="multipart/form-data" style="margin:0; padding:0; display:flex; align-items:center;">
                @csrf
                <div class="s-brand-logo-container" style="position: relative; cursor: pointer; width: 34px; height: 34px; border-radius: 10px; overflow: hidden; flex-shrink: 0; background: rgba(255,255,255,0.08); display: grid; place-items: center;" onclick="document.getElementById('coverVideoInput').click();" title="{{ $store->cover_video ? 'Cambiar video de portada (Premium)' : 'Subir video de portada (Premium)' }}">
                    @if($store->cover_video)
                        <i class="las la-video" style="color: #10b981; font-size: 16px;"></i>
                    @else
                        <i class="las la-video" style="color: rgba(255,255,255,0.4); font-size: 16px;"></i>
                    @endif
                    <div class="s-logo-hover-overlay" style="position: absolute; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s;">
                        <i class="las la-upload" style="color: #fff; font-size: 14px;"></i>
                    </div>
                </div>
                <input type="file" id="coverVideoInput" name="cover_video" accept="video/*" style="display: none;" onchange="document.getElementById('coverVideoUploadForm').submit();">
            </form>
            @endif
        </div>
        <style>
        .s-brand-logo-container:hover .s-logo-hover-overlay {
            opacity: 1 !important;
        }
        </style>
        @php
            $staffUser = null;
            $isKitchenOnly = false;
            $isAccountingStaff = false;
            if (session()->has('seller_staff_id')) {
                $staffUser = \App\Models\PosStaff::find(session()->get('seller_staff_id'));
                $isAccountingStaff = $staffUser && ($staffUser->position === 'contabilidad' || $staffUser->hasPermission('accounting'));
                if ($staffUser && $staffUser->hasPermission('kitchen') && !$staffUser->hasPermission('pos_orders') && !$staffUser->hasPermission('billing') && !$staffUser->hasPermission('products') && !$staffUser->hasPermission('hr')) {
                    $isKitchenOnly = true;
                }
            }
        @endphp
        <div class="s-side-scroll">
            <!-- PRINCIPAL -->
            @if(!$staffUser || $staffUser->hasPermission('pos_orders') || $staffUser->hasPermission('kitchen') || $staffUser->hasPermission('billing'))
            <div class="s-nav-section s-nav">
                <span class="s-nav-label">Principal</span>
                @if(!$isKitchenOnly)
                <a href="{{ route('seller.delivery.request') }}" data-tip="Solicitar Envío" class="{{ request()->routeIs('seller.delivery.request') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-motorcycle"></i></span> <span class="s-nav-text">Solicitar Envío</span>
                </a>
                <a href="{{ route('seller.dashboard') }}" data-tip="Dashboard" class="{{ request()->routeIs('seller.dashboard') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-chart-pie"></i></span> <span class="s-nav-text">Dashboard</span>
                </a>
                @endif
                @if(!$staffUser || $staffUser->hasPermission('pos_orders'))
                <a href="{{ route('seller.pos') }}" data-tip="Punto de Venta" class="{{ request()->is('seller/pos') && !request()->is('seller/pos/*') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-cash-register"></i></span> <span class="s-nav-text">Punto de Venta</span>
                </a>
                @endif
                @if(!$staffUser || $staffUser->hasPermission('billing'))
                <a href="{{ route('seller.pos.billing') }}" data-tip="Cobrar" class="{{ request()->routeIs('seller.pos.billing') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-hand-holding-usd"></i></span> <span class="s-nav-text">Cobrar</span>
                </a>
                @endif
                @if((($store->store_type ?? '') === 'restaurant') && (!$staffUser || $staffUser->hasPermission('kitchen')))
                <a href="{{ route('seller.pos.kitchen') }}" data-tip="Cocina" class="{{ request()->routeIs('seller.pos.kitchen') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-utensils"></i></span> <span class="s-nav-text">Cocina</span>
                </a>
                @endif
            </div>
            @endif

            <!-- GESTIÓN -->
            @if(!$staffUser || $isAccountingStaff || $staffUser->hasPermission('pos_orders') || $staffUser->hasPermission('products') || $staffUser->hasPermission('hr'))
            <div class="s-nav-section s-nav">
                <span class="s-nav-label">Gestión</span>
                @if(!$staffUser || $staffUser->hasPermission('pos_orders'))
                 @php
                     $pendingAppOrdersCount = 0;
                     if (isset($store->id)) {
                         $pendingAppOrdersCount = \App\Models\DeliveryOrder::where('store_id', $store->id)
                             ->whereNotIn('status', ['delivered', 'cancelled'])
                             ->count();
                     }
                 @endphp
                 <a href="{{ route('seller.orders') }}" data-tip="Pedidos" class="{{ request()->routeIs('seller.orders') ? 'active' : '' }}" style="position: relative;">
                     <span class="s-nav-icon" style="position: relative;">
                         <i class="las la-receipt"></i>
                         @if($pendingAppOrdersCount > 0)
                             <span style="position: absolute; top: -2px; right: -2px; width: 8px; height: 8px; background-color: #ef4444; border-radius: 50%; display: inline-block;"></span>
                         @endif
                     </span> 
                     <span class="s-nav-text" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                         Pedidos
                         @if($pendingAppOrdersCount > 0)
                             <span style="background-color: #ef4444; color: #fff; font-size: 11px; font-weight: bold; padding: 2px 7px; border-radius: 10px; margin-left: 8px; line-height: 1;">{{ $pendingAppOrdersCount }}</span>
                         @endif
                     </span>
                 </a>
                @endif
                @if(!$staffUser || $staffUser->hasPermission('products'))
                <a href="{{ route('seller.products') }}" data-tip="Productos" class="{{ request()->routeIs('seller.products') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-boxes"></i></span> <span class="s-nav-text">Productos</span>
                </a>
                <a href="{{ route('seller.products.bulk') }}" data-tip="Carga Masiva" class="{{ request()->routeIs('seller.products.bulk') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-cloud-upload-alt"></i></span> <span class="s-nav-text">Carga Masiva</span>
                </a>
                <a href="{{ route('seller.categories') }}" data-tip="Categorías" class="{{ request()->routeIs('seller.categories') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-tags"></i></span> <span class="s-nav-text">Categorías</span>
                </a>
                @endif
                @if(!$isAccountingStaff)<a href="{{ route('seller.customers') }}" data-tip="Clientes" class="{{ request()->routeIs('seller.customers') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-users"></i></span> <span class="s-nav-text">Clientes</span>
                </a>@endif
                @if(($store->store_type ?? '') === 'restaurant' || $isAccountingStaff)
                    @if(!$staffUser || $staffUser->hasPermission('pos_orders'))
                    <a href="{{ route('seller.pos.tables') }}" data-tip="Mesas" class="{{ request()->routeIs('seller.pos.tables') ? 'active' : '' }}">
                        <span class="s-nav-icon"><i class="las la-th"></i></span> <span class="s-nav-text">Mesas</span>
                    </a>
                    <a href="{{ route('seller.pos.floorplan') }}" data-tip="Salón y Mesas" class="{{ request()->routeIs('seller.pos.floorplan') ? 'active' : '' }}">
                        <span class="s-nav-icon"><i class="las la-map-marked-alt"></i></span> <span class="s-nav-text">Salón y Mesas</span>
                    </a>
                    <a href="{{ route('seller.pos.reservations.list') }}" data-tip="Reservas" class="{{ request()->routeIs('seller.pos.reservations.list') ? 'active' : '' }}">
                        <span class="s-nav-icon"><i class="las la-calendar-check"></i></span> <span class="s-nav-text">Reservas</span>
                    </a>
                    @endif
                    @if(!$staffUser || $isAccountingStaff || $staffUser->hasPermission('hr'))
                    <a href="{{ route('seller.pos.staff') }}" data-tip="Personal / RR.HH" class="{{ request()->routeIs('seller.pos.staff') ? 'active' : '' }}">
                        <span class="s-nav-icon"><i class="las la-users-cog"></i></span> <span class="s-nav-text">Personal / RR.HH</span>
                    </a>
                    @if($store->hasPremiumPackage() || $isAccountingStaff)
                    <a href="{{ route('seller.pos.hr.attendance') }}" data-tip="Asistencia" class="{{ request()->routeIs('seller.pos.hr.attendance') ? 'active' : '' }}">
                        <span class="s-nav-icon"><i class="las la-calendar-check"></i></span> <span class="s-nav-text">Asistencia</span>
                    </a>
                    <a href="{{ route('seller.pos.hr.payroll') }}" data-tip="Planillas" class="{{ request()->routeIs('seller.pos.hr.payroll') ? 'active' : '' }}">
                        <span class="s-nav-icon"><i class="las la-wallet"></i></span> <span class="s-nav-text">Planillas</span>
                    </a>
                    @endif
                    @endif
                    @if(!$isAccountingStaff && $store->hasPremiumPackage() && (!$staffUser || $staffUser->hasPermission('reports')))
                    <a href="{{ route('seller.pos.staff.reports') }}" data-tip="Comisiones" class="{{ request()->routeIs('seller.pos.staff.reports') ? 'active' : '' }}">
                        <span class="s-nav-icon"><i class="las la-percentage"></i></span> <span class="s-nav-text">Comisiones</span>
                    </a>
                    @endif
                @endif
            </div>
            @endif

            <!-- FINANZAS -->
            @if(!$staffUser || $isAccountingStaff || $staffUser->hasPermission('billing') || $staffUser->hasPermission('reports') || $staffUser->hasPermission('accounting') || $staffUser->hasPermission('settings'))
            <div class="s-nav-section s-nav">
                <span class="s-nav-label">Finanzas</span>
                @if(!$staffUser || $staffUser->hasPermission('billing'))
                <a href="{{ route('seller.cash') }}" data-tip="Caja y Bancos" class="{{ (request()->routeIs('seller.cash') || request()->routeIs('seller.pos.bank_accounts') || request()->routeIs('seller.registers')) ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-university"></i></span> <span class="s-nav-text">Caja y Bancos</span>
                </a>
                <a href="{{ route('seller.expenses') }}" data-tip="Gastos" class="{{ request()->routeIs('seller.expenses') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-receipt"></i></span> <span class="s-nav-text">Gastos</span>
                </a>
                @endif
                @if(($canAccessInvoicing || $isAccountingStaff) && (!$staffUser || $isAccountingStaff || $staffUser->hasPermission('settings')))
                <a href="{{ route('seller.invoicing') }}" data-tip="Facturación" class="{{ request()->routeIs('seller.invoicing') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-file-invoice"></i></span> <span class="s-nav-text">Facturación</span>
                </a>
                @endif
                @if(!$isAccountingStaff)<a href="{{ route('seller.crm.customers') }}" data-tip="CRM Clientes" class="{{ (request()->routeIs('seller.crm.customers') || request()->routeIs('seller.crm.customer.profile')) ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-users-cog"></i></span> <span class="s-nav-text">CRM Clientes</span>
                </a>@endif
                @if(!$staffUser || $isAccountingStaff || $staffUser->hasPermission('reports'))
                <a href="{{ route('seller.reports') }}" data-tip="Reportes" class="{{ request()->routeIs('seller.reports') && !request()->is('seller/reports/*') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-file-alt"></i></span> <span class="s-nav-text">Reportes</span>
                </a>
                @if((!$staffUser || $isAccountingStaff || $staffUser->hasPermission('reports')) && ($canAccessReports || $isAccountingStaff))
                <a href="{{ route('seller.reports.advanced') }}" data-tip="Reportes Avanzados" class="{{ request()->routeIs('seller.reports.advanced') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-chart-bar"></i></span> <span class="s-nav-text">Reportes Avanzados</span>
                </a>
                @endif
                @endif
                @if((!$staffUser || $isAccountingStaff || $staffUser->hasPermission('accounting')) && ($canAccessReports || $isAccountingStaff))
                <a href="{{ route('seller.declarations') }}" data-tip="Declaraciones SUNAT / SIRE" class="{{ request()->routeIs('seller.declarations') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-file-signature"></i></span> <span class="s-nav-text">Declaraciones</span>
                </a>
                @endif
            </div>
            @endif

            <!-- INVENTARIO Y LOGÍSTICA -->
            @if(($canAccessInventory || $isAccountingStaff) && (!$staffUser || $staffUser->hasPermission('inventory') || $isAccountingStaff))
            <div class="s-nav-section s-nav">
                <span class="s-nav-label">Inventario y Logística</span>
                @if(!$isAccountingStaff)
                <a href="{{ route('seller.inventory.items') }}" data-tip="Insumos / Productos" class="{{ request()->routeIs('seller.inventory.items') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-box"></i></span> <span class="s-nav-text">Insumos / Productos</span>
                </a>
                <a href="{{ route('seller.logistics.warehouses') }}" data-tip="Almacenes y Stock" class="{{ request()->routeIs('seller.logistics.warehouses') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-warehouse"></i></span> <span class="s-nav-text">Almacenes y Stock</span>
                </a>
                <a href="{{ route('seller.logistics.suppliers') }}" data-tip="Proveedores" class="{{ request()->routeIs('seller.logistics.suppliers') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-truck"></i></span> <span class="s-nav-text">Proveedores</span>
                </a>
                <a href="{{ route('seller.logistics.purchase_orders') }}" data-tip="Órdenes de Compra" class="{{ request()->routeIs('seller.logistics.purchase_orders') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-shopping-cart"></i></span> <span class="s-nav-text">Órdenes de Compra</span>
                </a>
                <a href="{{ route('seller.logistics.receptions') }}" data-tip="Recepciones" class="{{ request()->routeIs('seller.logistics.receptions') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-truck-loading"></i></span> <span class="s-nav-text">Recepciones</span>
                </a>
                <a href="{{ route('seller.inventory.purchases') }}" data-tip="Compras Directas" class="{{ request()->routeIs('seller.inventory.purchases') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-receipt"></i></span> <span class="s-nav-text">Compras Directas</span>
                </a>
                @if((($store->store_type ?? '') === 'restaurant'))
                <a href="{{ route('seller.inventory.recipes') }}" data-tip="Recetas" class="{{ request()->routeIs('seller.inventory.recipes') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-book-open"></i></span> <span class="s-nav-text">Recetas</span>
                </a>
                @endif
                <a href="{{ route('seller.logistics.transfers') }}" data-tip="Transferencias" class="{{ request()->routeIs('seller.logistics.transfers') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-exchange-alt"></i></span> <span class="s-nav-text">Transferencias</span>
                </a>
                <a href="{{ route('seller.inventory.wastes') }}" data-tip="Mermas" class="{{ request()->routeIs('seller.inventory.wastes') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-exclamation-triangle"></i></span> <span class="s-nav-text">Mermas</span>
                </a>
                @if(($store->store_type ?? '') !== 'restaurant')
                <a href="{{ route('seller.inventory.sales') }}" data-tip="Ventas Directas" class="{{ request()->routeIs('seller.inventory.sales') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-shopping-bag"></i></span> <span class="s-nav-text">Ventas Directas</span>
                </a>
                @endif
                @endif
                <a href="{{ route('seller.inventory.kardex') }}" data-tip="Kardex" class="{{ request()->routeIs('seller.inventory.kardex') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-list-alt"></i></span> <span class="s-nav-text">Kardex</span>
                </a>
                @if(!$isAccountingStaff)
                <a href="{{ route('seller.logistics.reports') }}" data-tip="Valoración y Costos" class="{{ request()->routeIs('seller.logistics.reports') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-chart-bar"></i></span> <span class="s-nav-text">Valoración y Costos</span>
                </a>
                <a href="{{ route('seller.inventory.tax-report') }}" data-tip="Reporte Tributario" class="{{ request()->routeIs('seller.inventory.tax-report') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-file-invoice-dollar"></i></span> <span class="s-nav-text">Reporte Tributario</span>
                </a>
                @endif
            </div>
            @endif

            <!-- LOGÍSTICA & MARKETING -->
            @if(!$isKitchenOnly && !$isAccountingStaff)
            <div class="s-nav-section s-nav">
                <span class="s-nav-label">Canales</span>
                @if(!$staffUser || $staffUser->hasPermission('settings'))
                <a href="{{ route('seller.delivery') }}" data-tip="Delivery Apps" class="{{ request()->routeIs('seller.delivery') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-motorcycle"></i></span> <span class="s-nav-text">Delivery Apps</span>
                </a>
                @endif
                @if((!$staffUser || $staffUser->hasPermission('notifications')) && $canAccessNotifications)
                <a href="{{ route('seller.notifications') }}" data-tip="Notificaciones" class="{{ request()->routeIs('seller.notifications') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-bell"></i></span> <span class="s-nav-text">Notificaciones</span>
                </a>
                @endif
                @if(($store->store_type ?? '') === 'restaurant')
                <a href="{{ route('seller.qrmenu') }}" data-tip="QR de Carta" class="{{ request()->routeIs('seller.qrmenu') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-qrcode"></i></span> <span class="s-nav-text">QR de Carta</span>
                </a>
                <a href="/seller/kiosk/" target="_blank" data-tip="Kiosco">
                    <span class="s-nav-icon"><i class="las la-tablet-alt"></i></span> <span class="s-nav-text">Kiosco</span>
                </a>
                <a href="{{ route('seller.external.order') }}" data-tip="Pedido Externo" class="{{ request()->routeIs('seller.external.order') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-globe"></i></span> <span class="s-nav-text">Pedido Externo</span>
                </a>
                @endif
                @if(!$staffUser || $staffUser->hasPermission('settings'))
                <a href="{{ route('seller.api.settings') }}" data-tip="API" class="{{ request()->routeIs('seller.api.settings') ? 'active' : '' }}">
                    <span class="s-nav-icon"><i class="las la-plug"></i></span> <span class="s-nav-text">API</span>
                </a>
                @endif
            </div>
            @endif
        </div>

        <!-- FOOTER -->
        <div class="s-side-foot">
            @if(!$isKitchenOnly && !$isAccountingStaff)
            <a href="{{ route('seller.pricing') }}" class="{{ request()->routeIs('seller.pricing') ? 'active' : '' }}">
                <i class="las la-crown"></i> <span class="s-nav-text">Planes</span>
            </a>
            @endif
            <a href="{{ route('seller.logout') }}">
                <i class="las la-sign-out-alt"></i> <span class="s-nav-text">Cerrar sesión</span>
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="s-main">
        <!-- DELIVERY REQUEST FLOATING BANNER -->
        <div style="background: linear-gradient(135deg, #1e293b, #0f172a); color: #fff; padding: 12px 24px; font-size: 13px; font-weight: 600; display: flex; align-items: center; justify-content: space-between; gap: 16px; border-bottom: 1.5px solid var(--s-border); z-index: 999; position: relative; box-shadow: 0 4px 12px rgba(0,0,0,0.05); flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="background: rgba(34,197,94,0.15); color: #22c55e; width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; box-shadow: 0 2px 8px rgba(34,197,94,0.2);">
                    <i class="las la-shipping-fast"></i>
                </span>
                <span style="letter-spacing: -0.1px;">¿Necesitas enviar un pedido o entregar algo urgente? <strong style="color: #22c55e;">Solicita un motorizado express</strong> y uno de nuestros repartidores afiliados se encargará de inmediato.</span>
            </div>
            <a href="{{ route('seller.delivery.request') }}" style="background: #22c55e; color: #fff; padding: 7px 16px; border-radius: 8px; text-decoration: none; font-size: 12px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 10px rgba(34,197,94,0.3); border: none; transition: transform 0.2s, background 0.2s;" onmouseover="this.style.background='#16a34a'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='#22c55e'; this.style.transform='translateY(0)';" onmousedown="this.style.transform='translateY(1px)';">
                <i class="las la-plus"></i> Solicitar Envío
            </a>
        </div>

        <!-- AMBER EXPIRATION ALERT (1–7 days) -->
        @if($showAmberAlert)
        <div style="background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; padding: 10px 20px; font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: space-between; gap: 12px; border-bottom: 1px solid rgba(0,0,0,0.1); z-index: 1000; position: relative;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="las la-exclamation-triangle" style="font-size: 18px;"></i>
                <span>Tu suscripción al plan <strong>{{ $storeActivePackage->package->name }}</strong> vence en <strong>{{ $daysRemaining }} {{ $daysRemaining == 1 ? 'día' : 'días' }}</strong> (el {{ $storeActivePackage->expires_at->format('d/m/Y') }}). Renueva ahora para evitar interrupciones.</span>
            </div>
            <a href="{{ route('seller.pricing') }}" style="background: rgba(255,255,255,0.25); color: #fff; padding: 5px 12px; border-radius: 6px; text-decoration: none; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid rgba(255,255,255,0.4); display: inline-block;">Renovar Suscripción</a>
        </div>
        @endif

        <!-- CRITICAL EXPIRATION ALERT (vence HOY = 0 días) -->
        @if($showCriticalAlert)
        <div style="background: linear-gradient(135deg, #dc2626, #b91c1c); color: #fff; padding: 10px 20px; font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: space-between; gap: 12px; border-bottom: 2px solid rgba(0,0,0,0.2); z-index: 1001; position: relative; box-shadow: 0 4px 14px rgba(220,38,38,0.35);">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="las la-exclamation-circle" style="font-size: 20px; animation: pulse 1.4s ease-in-out infinite;"></i>
                <span>⚠️ Tu suscripción al plan <strong>{{ $storeActivePackage->package->name }}</strong> <strong>vence HOY</strong> ({{ $storeActivePackage->expires_at->format('d/m/Y') }}). ¡Renueva ahora para no perder el acceso!</span>
            </div>
            <a href="{{ route('seller.pricing') }}" style="background: #fff; color: #dc2626; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border: none; display: inline-block; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">¡Renovar Ahora!</a>
        </div>
        <style>@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.5} }</style>
        @endif


        <!-- ═══ INACTIVE SERVICE BANNER ═══ -->
        @if(isset($store) && !$store->status)
        <div style="background: linear-gradient(135deg, #dc2626, #991b1b); color: #fff; padding: 0; font-size: 13px; font-weight: 600; border-bottom: 2px solid #7f1d1d; z-index: 1000; position: relative; box-shadow: 0 4px 16px rgba(220,38,38,0.3);">
            <!-- Main Message -->
            <div style="padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                    <div style="background: rgba(255,255,255,0.15); width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="las la-ban" style="font-size: 22px;"></i>
                    </div>
                    <div>
                        <div style="font-size: 15px; font-weight: 800; margin-bottom: 2px;">
                            <i class="las la-exclamation-circle" style="margin-right: 4px;"></i> Servicio Inactivo
                        </div>
                        <div style="font-size: 12px; opacity: 0.9; font-weight: 500;">Tu tienda <strong>{{ $store->name }}</strong> se encuentra suspendida. Algunas funciones no están disponibles.</div>
                    </div>
                </div>
                <a href="https://wa.me/51997428341?text=Hola,%20mi%20servicio%20Lizto%20est%C3%A1%20inactivo.%20Necesito%20ayuda." target="_blank" style="background: rgba(255,255,255,0.95); color: #991b1b; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-size: 13px; font-weight: 800; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border: none; transition: all 0.2s; white-space: nowrap;" onmouseover="this.style.background='#fff'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='rgba(255,255,255,0.95)'; this.style.transform='translateY(0)';">
                    <i class="las la-headset" style="font-size: 16px;"></i> Contactar Soporte
                </a>
            </div>
            <!-- FAQ Section -->
            <div style="background: rgba(0,0,0,0.15); padding: 14px 24px; border-top: 1px solid rgba(255,255,255,0.1);">
                <details style="cursor: pointer;">
                    <summary style="font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px; list-style: none; padding: 4px 0;">
                        <i class="las la-question-circle" style="font-size: 16px; color: #fca5a5;"></i>
                        <span>¿Por qué mi servicio está suspendido?</span>
                        <i class="las la-chevron-down" style="margin-left: auto; font-size: 12px; transition: transform 0.2s;"></i>
                    </summary>
                    <div style="padding: 12px 0 4px 28px; font-size: 12.5px; line-height: 1.7; color: rgba(255,255,255,0.9);">
                        <p style="margin: 0 0 10px 0;">Tu servicio puede haber sido suspendido por una de las siguientes razones:</p>
                        <ul style="margin: 0 0 12px 0; padding-left: 18px;">
                            <li style="margin-bottom: 6px;"><strong>No se realizó la cancelación a tiempo</strong> del plan de suscripción vigente.</li>
                            <li style="margin-bottom: 6px;"><strong>Hubo un problema con el pago</strong> de la suscripción (pago rechazado, fondos insuficientes, etc.).</li>
                        </ul>
                        <div style="background: rgba(255,255,255,0.1); border-radius: 8px; padding: 12px 14px; display: flex; align-items: center; gap: 10px;">
                            <i class="las la-info-circle" style="font-size: 18px; color: #fca5a5; flex-shrink: 0;"></i>
                            <span>Para reactivar tu servicio, contacta a nuestro equipo de soporte al <strong>997 428 341</strong> o haz clic en el botón de soporte.</span>
                        </div>
                    </div>
                </details>
            </div>
        </div>
        @endif

        <!-- TOPBAR -->
        <div class="s-topbar">
            <div style="display:flex;align-items:center;gap:14px">
                <button class="s-hamburger" onclick="toggleSidebar()" aria-label="Menú">
                    <i class="las la-bars"></i>
                </button>
                @hasSection('page-title')
                    <h1 class="s-topbar-title">@yield('page-title')</h1>
                @else
                    <h1 class="s-topbar-title">
                        <span class="s-title-icon"><i class="las la-store"></i></span>
                        Panel Vendedor
                    </h1>
                @endif
            </div>
            <div class="s-topbar-right">
                <div class="s-topbar-time">
                    <i class="las la-clock"></i>
                    <span id="s-clock">{{ now()->format('d/m/Y H:i') }}</span>
                </div>
                <button onclick="toggleKbShortcuts()" class="s-btn s-btn-ghost s-btn-sm" title="Atajos de teclado (Ctrl+K)" style="border-radius:8px;font-size:11px;gap:6px;display:inline-flex;align-items:center;padding:6px 10px;background:var(--s-surface-2);border:1px solid var(--s-border);height:auto;min-height:unset;min-width:unset;margin:0 4px;vertical-align:middle;cursor:pointer;">
                    <i class="las la-keyboard" style="font-size:15px;color:var(--s-text-muted);"></i>
                    <kbd style="background:var(--s-surface);border:1px solid var(--s-border);padding:2px 5px;border-radius:4px;font-size:10px;font-weight:700;color:var(--s-text-primary);box-shadow:0 1px 0 rgba(0,0,0,0.15);font-family:monospace;">Ctrl+K</kbd>
                </button>
                <button onclick="openHelpDrawer()" class="s-btn s-btn-ghost s-btn-sm" title="Manual de ayuda de esta página" style="border-radius:8px;font-size:11px;gap:6px;display:inline-flex;align-items:center;padding:6px 10px;background:var(--s-surface-2);border:1px solid var(--s-border);height:auto;min-height:unset;min-width:unset;margin:0 4px;vertical-align:middle;cursor:pointer;">
                    <i class="las la-question-circle" style="font-size:15px;color:var(--s-accent-dark);"></i> Ayuda
                </button>
                @yield('topbar-actions')
            </div>
        </div>

        <!-- ALERTS -->
        @if(session('success'))
        <div class="s-alert s-alert-success" role="alert">
            <i class="las la-check-circle" style="font-size:18px;flex-shrink:0"></i>
            <span>{{ session('success') }}</span>
        </div>
        @endif
        @if(session('error'))
        <div class="s-alert s-alert-error" role="alert">
            <i class="las la-exclamation-circle" style="font-size:18px;flex-shrink:0"></i>
            <span>{{ session('error') }}</span>
        </div>
        @endif

        @yield('seller-content')

        <!-- ═══ INACTIVE OVERLAY — BLOCKS ALL MODULES ═══ -->
        @if(isset($store) && !$store->status)
        <div id="inactive-overlay" style="position:fixed;inset:0;z-index:99990;background:rgba(248,250,252,0.85);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;cursor:not-allowed;">
            <div style="background:#fff;border-radius:20px;padding:40px;max-width:420px;width:calc(100% - 32px);text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.12);border:1px solid #e4e9ef;">
                <div style="width:72px;height:72px;border-radius:20px;background:linear-gradient(135deg,#fee2e2,#fecaca);display:grid;place-items:center;margin:0 auto 20px;">
                    <i class="las la-lock" style="font-size:36px;color:#dc2626;"></i>
                </div>
                <h3 style="margin:0 0 8px;font-size:20px;font-weight:800;color:#0f172a;">Servicio Suspendido</h3>
                <p style="margin:0 0 20px;font-size:14px;color:#64748b;line-height:1.6;">Tu tienda se encuentra inactiva. No es posible acceder a los módulos del panel hasta que el servicio sea reactivado.</p>
                <a href="https://wa.me/51997428341?text=Hola,%20mi%20servicio%20Lizto%20est%C3%A1%20inactivo.%20Necesito%20ayuda." target="_blank" style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;padding:12px 24px;border-radius:12px;text-decoration:none;font-size:14px;font-weight:700;box-shadow:0 4px 14px rgba(34,197,94,0.3);transition:all 0.2s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(34,197,94,0.4)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 14px rgba(34,197,94,0.3)'">
                    <i class="las la-headset" style="font-size:18px;"></i> Contactar Soporte — WhatsApp
                </a>
                <p style="margin:16px 0 0;font-size:12px;color:#94a3b8;">📞 <strong>997 428 341</strong></p>
            </div>
        </div>
        @endif
    </main>
</div>

<!-- PREMIUM OVERLAY -->
<div class="s-premium-overlay" id="s-premium-overlay">
    <div class="s-premium-box">
        <div class="s-premium-icon">⭐</div>
        <div class="s-premium-stars">★★★★★</div>
        <h2>Función Premium</h2>
        <p>Esta función requiere un plan Premium. Desbloquea reportes avanzados, facturación electrónica, notificaciones push y más.</p>
        <a href="{{ route('seller.pricing') }}" class="s-btn s-btn-primary s-btn-lg" style="justify-content:center;width:100%;margin-bottom:8px">
            <i class="las la-crown"></i> Ver Planes
        </a>
        <button class="s-premium-close-btn" onclick="document.getElementById('s-premium-overlay').classList.remove('open')">
            Quizás después
        </button>
    </div>
</div>

<!-- SIDEBAR OVERLAY (mobile) -->
<div id="s-sidebar-overlay" style="display:none;position:fixed;inset:0;z-index:199;background:rgba(0,0,0,.4);backdrop-filter:blur(2px);" onclick="toggleSidebar()"></div>

<!-- SIDEBAR TOOLTIP -->
<div id="sb-tooltip"></div>

<script>
// Clock update
function updateClock() {
    var now = new Date();
    var d = now.getDate().toString().padStart(2,'0');
    var m = (now.getMonth()+1).toString().padStart(2,'0');
    var y = now.getFullYear();
    var h = now.getHours().toString().padStart(2,'0');
    var min = now.getMinutes().toString().padStart(2,'0');
    var el = document.getElementById('s-clock');
    if (el) el.textContent = d+'/'+m+'/'+y+' '+h+':'+min;
}
setInterval(updateClock, 10000);

// Touch screen detection
(function() {
    if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
        document.documentElement.classList.add('is-touch');
    }
})();

// Sidebar toggle
function toggleSidebar() {
    var s = document.getElementById('s-sidebar');
    var o = document.getElementById('s-sidebar-overlay');
    if (window.innerWidth <= 768) {
        s.classList.toggle('open');
        o.style.display = s.classList.contains('open') ? 'block' : 'none';
    } else {
        s.classList.toggle('collapsed');
        localStorage.setItem('sb_collapsed', s.classList.contains('collapsed') ? '1' : '0');
    }
}
(function(){
    var isPosPage = window.location.pathname.indexOf('/seller/pos') !== -1;
    if (window.innerWidth > 768) {
        if (isPosPage || window.innerWidth < 1200 || localStorage.getItem('sb_collapsed') === '1') {
            document.getElementById('s-sidebar').classList.add('collapsed');
        }
    }
})();

// Sidebar collapsed tooltips (JS-based, avoids overflow:hidden clipping)
(function(){
    var tip = document.getElementById('sb-tooltip');
    document.getElementById('s-sidebar').addEventListener('mouseover', function(e){
        var a = e.target.closest('a[data-tip]');
        if (!a || !a.closest('.s-side.collapsed')) { tip.style.opacity='0'; return; }
        tip.textContent = a.getAttribute('data-tip');
        var r = a.getBoundingClientRect();
        tip.style.left = (r.right + 8) + 'px';
        tip.style.top = (r.top + r.height/2 - 12) + 'px';
        tip.style.opacity = '1';
    });
    document.getElementById('s-sidebar').addEventListener('mouseout', function(e){
        var a = e.target.closest('a[data-tip]');
        if (a) tip.style.opacity = '0';
    });
    document.getElementById('s-sidebar').addEventListener('click', function(e){
        var a = e.target.closest('a[data-tip]');
        if (a) tip.style.opacity = '0';
    });
})();

// Premium gate
var STORE_PLAN_TYPE = '{{ $storePlanType }}';
var STORE_IS_INACTIVE = {{ isset($store) && !$store->status ? 'true' : 'false' }};
    var PREMIUM_ONLY_ROUTES = ['notifications'];
    var FEATURED_PLUS_ROUTES = ['reports/advanced', 'inventory', 'logistics'];
document.addEventListener('click', function(e) {
    var link = e.target.closest('a[href]');
    if (!link) return;
    var href = link.getAttribute('href') || '';

    // Block all navigation when store is inactive (except logout, pricing, delivery request, and WhatsApp)
    if (STORE_IS_INACTIVE) {
        var allowed = ['/seller/logout', '/seller/pricing', '/seller/delivery/request', 'wa.me/'];
        var isAllowed = allowed.some(function(a) { return href.indexOf(a) !== -1; });
        if (!isAllowed) {
            e.preventDefault();
            e.stopPropagation();
            return;
        }
    }

    if (href.indexOf('/seller/delivery/request') !== -1 || href.indexOf('/seller/pricing') !== -1) return;

    if (STORE_PLAN_TYPE === 'free') {
        e.preventDefault();
        document.getElementById('s-premium-overlay').classList.add('open');
        return;
    }

    var isPremiumOnly = PREMIUM_ONLY_ROUTES.some(function(r) { return href.indexOf('/seller/' + r) !== -1; });
    if (isPremiumOnly && STORE_PLAN_TYPE !== 'premium') {
        e.preventDefault();
        document.getElementById('s-premium-overlay').classList.add('open');
        return;
    }

    var isFeaturedPlus = FEATURED_PLUS_ROUTES.some(function(r) { return href.indexOf('/seller/' + r) !== -1; });
    if (isFeaturedPlus && STORE_PLAN_TYPE === 'basic') {
        e.preventDefault();
        document.getElementById('s-premium-overlay').classList.add('open');
        return;
    }
});

// ═══ INACTIVE STORE — DISABLE SIDEBAR + FORMS ═══
if (STORE_IS_INACTIVE) {
    document.addEventListener('DOMContentLoaded', function() {
        // Dim sidebar links
        var sidebar = document.getElementById('s-sidebar');
        if (sidebar) {
            sidebar.style.opacity = '0.45';
            sidebar.style.pointerEvents = 'none';
            sidebar.style.userSelect = 'none';
        }
        // Disable all forms
        document.querySelectorAll('form').forEach(function(f) {
            f.addEventListener('submit', function(e) { e.preventDefault(); e.stopPropagation(); });
        });
        // Disable all buttons except the overlay CTA and logout
        document.querySelectorAll('.s-btn, button').forEach(function(btn) {
            if (!btn.closest('#inactive-overlay') && !btn.closest('.s-side-foot')) {
                btn.setAttribute('disabled', 'true');
                btn.style.pointerEvents = 'none';
                btn.style.opacity = '0.5';
            }
        });
    });
}
</script>

<!-- KEYBOARD SHORTCUTS OVERLAY -->
<div id="kb-overlay" style="display:none;position:fixed;inset:0;z-index:99998;background:rgba(15,25,35,0.8);align-items:center;justify-content:center;backdrop-filter:blur(6px);" onclick="toggleKbShortcuts()">
    <div style="background:var(--s-surface);border-radius:20px;padding:32px;max-width:620px;width:calc(100% - 32px);box-shadow:0 20px 60px rgba(0,0,0,0.3);" onclick="event.stopPropagation()">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
            <h3 style="margin:0;font-weight:800;font-size:18px;color:var(--s-text);"><i class="las la-keyboard" style="color:var(--s-primary);font-size:22px;margin-right:8px;"></i> Atajos de Teclado</h3>
            <button onclick="toggleKbShortcuts()" style="background:none;border:none;font-size:22px;color:var(--s-text-3);cursor:pointer;">✕</button>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--s-bg-light);border-radius:10px;">
                <kbd>Ctrl+K</kbd>
                <span style="font-size:12px;font-weight:600;color:var(--s-text-2);">Mostrar / ocultar esta guía</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--s-bg-light);border-radius:10px;">
                <kbd>/</kbd>
                <span style="font-size:12px;font-weight:600;color:var(--s-text-2);">Buscar productos (Enfocar)</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--s-bg-light);border-radius:10px;">
                <kbd>Ctrl+P</kbd>
                <span style="font-size:12px;font-weight:600;color:var(--s-text-2);">Ir al Terminal de Ventas (POS)</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--s-bg-light);border-radius:10px;">
                <kbd>Ctrl+D</kbd>
                <span style="font-size:12px;font-weight:600;color:var(--s-text-2);">Ir al Panel de Administración</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--s-bg-light);border-radius:10px;">
                <kbd>Ctrl+B</kbd>
                <span style="font-size:12px;font-weight:600;color:var(--s-text-2);">Ir a Cobrar / Caja POS</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--s-bg-light);border-radius:10px;">
                <kbd>Ctrl+O</kbd>
                <span style="font-size:12px;font-weight:600;color:var(--s-text-2);">Ver Historial de Pedidos</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--s-bg-light);border-radius:10px;">
                <kbd>Ctrl+E</kbd>
                <span style="font-size:12px;font-weight:600;color:var(--s-text-2);">Ir a Facturación Electrónica</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--s-bg-light);border-radius:10px;">
                <kbd>Esc</kbd>
                <span style="font-size:12px;font-weight:600;color:var(--s-text-2);">Cerrar ventanas emergentes</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;background:var(--s-bg-light);border-radius:10px;">
                <kbd>Enter</kbd>
                <span style="font-size:12px;font-weight:600;color:var(--s-text-2);">Confirmar / Procesar Pedido</span>
            </div>
        </div>
        <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--s-border);text-align:center;">
            <span style="font-size:11px;color:var(--s-text-3);font-weight:600;">Presiona </span>
            <kbd style="padding: 2px 6px !important; font-size: 10px !important;">Ctrl+K</kbd>
            <span style="font-size:11px;color:var(--s-text-3);font-weight:600;"> en cualquier momento para abrir esta guía</span>
        </div>
    </div>
</div>

<!-- HELP DRAWER -->
<div class="s-help-drawer-overlay" id="s-help-drawer-overlay" onclick="closeHelpDrawer()"></div>
<div class="s-help-drawer" id="s-help-drawer">
    <div class="s-help-drawer-header">
        <h3 style="margin:0;font-weight:800;font-size:15px;color:var(--s-text);display:flex;align-items:center;gap:8px;">
            <i class="las {{ $currentManual['icon'] }}" style="color:var(--s-primary);font-size:20px;"></i>
            {{ $currentManual['title'] }}
        </h3>
        <button onclick="closeHelpDrawer()" style="background:none;border:none;font-size:20px;color:var(--s-text-3);cursor:pointer;line-height:1;">✕</button>
    </div>
    <div class="s-help-drawer-body">
        {!! $currentManual['html'] !!}
        <div style="margin-top:24px;padding-top:16px;border-top:1px solid var(--s-border);text-align:center;">
            <p style="font-size:11px;color:var(--s-text-3);margin-bottom:0;">¿Necesitas más ayuda? Contáctanos a través de Soporte Lizto.</p>
        </div>
    </div>
</div>

<script>
// Keyboard shortcuts
function toggleKbShortcuts() {
    var el = document.getElementById('kb-overlay');
    el.style.display = el.style.display === 'flex' ? 'none' : 'flex';
}

function openHelpDrawer() {
    document.getElementById('s-help-drawer-overlay').style.display = 'block';
    document.getElementById('s-help-drawer').classList.add('open');
}

function closeHelpDrawer() {
    document.getElementById('s-help-drawer-overlay').style.display = 'none';
    document.getElementById('s-help-drawer').classList.remove('open');
}

document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'k') { e.preventDefault(); toggleKbShortcuts(); return; }
    if (e.key === 'Escape') { 
        document.querySelectorAll('[id$="-modal"]').forEach(function(m) { m.style.display = 'none'; }); 
        document.getElementById('kb-overlay').style.display = 'none'; 
        var pm = document.getElementById('p-modal');
        if (pm) pm.style.display = 'none';
        closeHelpDrawer();
        return; 
    }
    if (e.ctrlKey && e.key === 'p') { e.preventDefault(); window.location = '{{ route('seller.pos') }}'; return; }
    if (e.ctrlKey && e.key === 'd') { e.preventDefault(); window.location = '{{ route('seller.dashboard') }}'; return; }
    if (e.ctrlKey && e.key === 'b') { e.preventDefault(); window.location = '{{ route('seller.pos.billing') }}'; return; }
    if (e.ctrlKey && e.key === 'o') { e.preventDefault(); window.location = '{{ route('seller.orders') }}'; return; }
    if (e.ctrlKey && e.key === 'e') { e.preventDefault(); window.location = '{{ route('seller.invoicing') }}'; return; }
    if (e.key === '/' && !['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) {
        e.preventDefault();
        var search = document.getElementById('product-search');
        if (search) search.focus();
    }
});
</script>
<script src="{{ asset('assets/global/js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('assets/global/js/bootstrap.bundle.min.js') }}"></script>
@stack('script-lib')
@stack('script')
@include('seller.partials.realtime_notifications')
@include('seller.partials.firebase_notifications')
@include('partials.jsoft_ai')
</body>
</html>
