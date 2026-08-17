<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class DeepSeekService
{
    protected $apiKey;
    protected $apiUrl = 'https://api.deepseek.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = env('DEEPSEEK_API_KEY');
    }

    public function chat($messages, $role = 'admin', $sellerId = null)
    {
        if (!$this->apiKey) {
            return [
                'role' => 'assistant',
                'content' => 'Error: La API key de DeepSeek (`DEEPSEEK_API_KEY`) no está configurada en el archivo `.env` del servidor.'
            ];
        }

        $systemPrompt = $this->getSystemPrompt($role, $sellerId);
        
        // Ensure system prompt is at the beginning
        if (count($messages) === 0 || $messages[0]['role'] !== 'system') {
            array_unshift($messages, ['role' => 'system', 'content' => $systemPrompt]);
        }

        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'execute_select_query',
                    'description' => 'Executes a read-only SQL SELECT query on the database to gather metrics, counts, listings, reports or details. Only SELECT queries are permitted.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => 'The raw SQL SELECT query to execute. Example: SELECT COUNT(*) FROM users'
                            ]
                        ],
                        'required' => ['query']
                    ]
                ]
            ]
        ];

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(60)
                ->post($this->apiUrl, [
                    'model' => 'deepseek-chat',
                    'messages' => $messages,
                    'tools' => $tools,
                    'tool_choice' => 'auto'
                ]);

            if (!$response->successful()) {
                return [
                    'role' => 'assistant',
                    'content' => 'Error de conexión con la IA de DeepSeek: ' . $response->body()
                ];
            }

            $resData = $response->json();
            $choice = $resData['choices'][0]['message'] ?? null;

            if (!$choice) {
                return [
                    'role' => 'assistant',
                    'content' => 'No se recibió una respuesta válida de la IA.'
                ];
            }

            // Handle tool calls
            if (isset($choice['tool_calls']) && count($choice['tool_calls']) > 0) {
                $toolCall = $choice['tool_calls'][0];
                $funcName = $toolCall['function']['name'] ?? '';
                $arguments = json_decode($toolCall['function']['arguments'] ?? '{}', true);

                if ($funcName === 'execute_select_query') {
                    $query = $arguments['query'] ?? '';
                    try {
                        $results = $this->executeSelectQuery($query, $role, $sellerId);
                        
                        // Append choice and tool results to messages
                        $messages[] = $choice;
                        $messages[] = [
                            'role' => 'tool',
                            'tool_call_id' => $toolCall['id'],
                            'content' => json_encode($results)
                        ];

                        // Second call to get final response
                        $secondResponse = Http::withToken($this->apiKey)
                            ->timeout(60)
                            ->post($this->apiUrl, [
                                'model' => 'deepseek-chat',
                                'messages' => $messages
                            ]);

                        if ($secondResponse->successful()) {
                            $secData = $secondResponse->json();
                            return $secData['choices'][0]['message'] ?? [
                                'role' => 'assistant',
                                'content' => 'Error al procesar la respuesta final.'
                            ];
                        }
                    } catch (\Exception $e) {
                        return [
                            'role' => 'assistant',
                            'content' => 'Ocurrió un error al consultar la base de datos: ' . $e->getMessage() . "\n\nQuery intentada: `" . $query . "`"
                        ];
                    }
                }
            }

            return $choice;

        } catch (\Exception $e) {
            return [
                'role' => 'assistant',
                'content' => 'Error excepcional al conectar con JSoft AI: ' . $e->getMessage()
            ];
        }
    }

    public function executeSelectQuery($sql, $role = 'admin', $sellerId = null)
    {
        $cleanSql = trim($sql);
        
        // Security check: Must start with SELECT
        if (!preg_match('/^select\b/i', $cleanSql)) {
            throw new \Exception("Solo se permiten consultas SQL de tipo SELECT para seguridad.");
        }

        // Security check: Block destructive commands
        $blocked = ['insert', 'update', 'delete', 'drop', 'alter', 'truncate', 'replace', 'create', 'grant', 'revoke', 'information_schema', 'mysql.', 'pg_'];
        foreach ($blocked as $word) {
            if (stripos($cleanSql, $word) !== false) {
                throw new \Exception("La consulta contiene una palabra clave no permitida: " . $word);
            }
        }

        // Multi-tenant enforcement for Sellers
        if ($role === 'seller' && $sellerId) {
            // Block forbidden tables for sellers
            $blockedTables = ['drivers', 'fleets', 'zones', 'delivery_orders'];
            foreach ($blockedTables as $t) {
                if (stripos($cleanSql, $t) !== false) {
                    throw new \Exception("Acceso denegado: Como vendedor no tienes permitido consultar datos de la tabla '" . $t . "'.");
                }
            }
            // Enforce seller_id on seller-specific tables
            $sellerTables = ['pos_orders', 'pos_expenses', 'pos_cash_sessions', 'pos_transactions'];
            foreach ($sellerTables as $t) {
                if (stripos($cleanSql, $t) !== false) {
                    if (stripos($cleanSql, 'seller_id') === false) {
                        throw new \Exception("Acceso denegado: Toda consulta a '" . $t . "' debe incluir obligatoriamente el filtro 'seller_id = " . $sellerId . "'.");
                    }
                    // Prevent querying other seller_ids
                    if (preg_match('/seller_id\s*=\s*(\d+)/i', $cleanSql, $matches)) {
                        if (intval($matches[1]) !== intval($sellerId)) {
                            throw new \Exception("Acceso denegado: No estás autorizado para consultar datos de otro seller_id.");
                        }
                    }
                }
            }
        }

        // Enforce limit
        if (stripos($cleanSql, 'limit') === false) {
            $cleanSql = rtrim($cleanSql, ';') . ' LIMIT 50';
        }

        return DB::select($cleanSql);
    }

    protected function getSystemPrompt($role = 'admin', $sellerId = null)
    {
        $contextPrompt = "";
        if ($role === 'seller' && $sellerId) {
            $contextPrompt = "ESTÁS ASISTIENDO A UN VENDEDOR (SELLER) CON ID = " . $sellerId . ".\n" .
                             "RESTRICCIÓN MULTI-TENANT DE SEGURIDAD:\n" .
                             "- Estás en el panel de vendedor (POS). Tienes PROHIBIDO brindar información o hacer consultas sobre conductores (`drivers`), flotas (`fleets`), zonas (`zones`) u otros vendedores.\n" .
                             "- Cada consulta SQL de SELECT que realices sobre `pos_orders`, `pos_expenses`, `pos_cash_sessions`, `pos_transactions` DEBE incluir estrictamente la cláusula `seller_id = " . $sellerId . "` para filtrar y mostrar exclusivamente los datos del vendedor actual.\n" .
                             "- Si el usuario te pregunta por flotas, conductores o comisiones globales, aclara amablemente que, como asistente del POS, solo tienes acceso a la información de venta, caja, inventario y gastos de su propia tienda.\n\n";
        } else {
            $contextPrompt = "ESTÁS ASISTIENDO A UN ADMINISTRADOR (ADMIN).\n" .
                             "Tienes acceso global e irrestricto a toda la base de datos de Lizto (incluyendo conductores, comisiones, flotas de transporte, zonas y todos los vendedores del sistema). Brinda información y reportes consolidados cuando se te solicite.\n\n";
        }

        return "Eres JSoft AI, un asistente virtual de Inteligencia Artificial integrado en la plataforma Lizto.\n" .
               "Tu objetivo es ayudar a los administradores y vendedores de Lizto con datos detallados, reportes avanzados de la base de datos, consultas estadísticas financieras, contabilidad y guías detalladas de uso de la aplicación.\n\n" .
               $contextPrompt .
               "REGLAS IMPORTANTES:\n" .
               "1. Responde siempre en español de manera clara, amable, estructurada y profesional.\n" .
               "2. Para cualquier pregunta sobre datos de ventas, conductores, flotas, tiendas, etc., puedes utilizar la herramienta `execute_select_query` para ejecutar una consulta SELECT en la base de datos. Sé preciso al formular la consulta.\n" .
               "3. Siempre interpreta y resume los resultados de las consultas de base de datos de manera entendible para el usuario, en lugar de solo mostrar el JSON crudo.\n" .
               "4. Si el usuario te pregunta cómo realizar un proceso o llenar un formulario en la aplicación, respóndele detalladamente basándote en las guías descritas a continuación.\n\n" .
               "GUÍAS DE REPORTES FINANCIEROS Y CONTABILIDAD:\n" .
               "Puedes calcular y reportar lo siguiente usando consultas de base de datos:\n" .
               "- **Reporte de Ventas**: Sumar el campo `total` de la tabla `pos_orders` (o `delivery_orders`) filtrando por `status` (los completados/entregados suelen ser 'delivered' o 'ready') y por fecha. Agrupa por día, semana, mes, año o rango de fechas.\n" .
               "- **Reporte de Gastos**: Sumar el campo `amount` de la tabla `pos_expenses` filtrando por fecha (`expense_date`).\n" .
               "- **Flujo de Caja (Efectivo)**: Analizar la tabla `pos_transactions` (campos `amount`, `type` que puede ser 'in' o 'out', `payment_method` que puede ser 'cash', 'card', 'yape', 'plin', etc.) o `pos_cash_sessions` (sumar `total_cash_in` y restar `total_cash_out`).\n" .
               "- **Utilidad Neta (Ingresos - Egresos)**: Calcular sumando el total de ventas cobradas y restando el total de gastos registrados en el periodo solicitado.\n" .
               "- **Filtros de Fechas en MySQL para Consultas**:\n" .
               "  - *Hoy*: `DATE(created_at) = CURDATE()` (o `DATE(expense_date) = CURDATE()`)\n" .
               "  - *Esta Semana*: `YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)`\n" .
               "  - *Este Mes*: `MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())`\n" .
               "  - *Este Año*: `YEAR(created_at) = YEAR(CURDATE())`\n" .
               "  - *Rango específico*: `created_at BETWEEN 'YYYY-MM-DD 00:00:00' AND 'YYYY-MM-DD 23:59:59'`\n\n" .
               "GUÍAS DE PROCESOS COMPLETOS DEL SISTEMA LIZTO:\n" .
               "- **Cómo Registrar una Flota (Transport Partner)**: Ir a la página de registro `/fleet/register`, ingresar los datos del dueño, el nombre de la flota, y seleccionar la Ciudad de Operación. Al registrarse, se activa por defecto en el 'Plan Lizto Partner' de suscripción fija ($69/mes y $199 inversión inicial) donde Lizto no cobra comisiones por viaje.\n" .
               "- **Cómo Configurar Tarifas de Flota**: En el panel de control de flota, acceder al menú 'Tarifas', actualizar la Tarifa Base, Tarifa por Kilómetro y Tarifa por Minuto, y guardar. Estas tarifas aplicarán de forma prioritaria para sus conductores asignados.\n" .
               "- **Cómo Asignar/Dar de Alta Conductores**: En el Panel de Flota, ir a la sección 'Conductores', buscar el conductor por su nombre de usuario o correo electrónico, y dar clic en 'Asignar'. También los conductores pueden registrarse usando el código de invitación de la flota.\n" .
               "- **Cómo Gestionar la Caja del POS**: Para procesar pagos, el vendedor debe abrir una caja registradora. Ve a Caja -> Abrir Caja, ingresa el saldo inicial y abre la caja. Las comandas/pedidos pendientes sí pueden enviarse a cocina sin abrir la caja, pero no se cobrarán hasta abrirla.\n" .
               "- **Registrar un Gasto (Egreso)**: Ir a POS -> Gastos (`pos_expenses`), ingresar el concepto, monto y vincular a la caja activa. Esto restará del balance de la sesión de caja activa al cerrarla.\n" .
               "- **Ventas a Crédito**: Al realizar el cobro en el POS de una mesa o pedido, elige el método de pago 'Crédito (Pago Posterior)'. Esto registrará la venta como pendiente de cobro (A CRÉDITO) y liberará la mesa. Posteriormente se puede cobrar el crédito seleccionando nuevamente 'Cobrar y Emitir' cuando el cliente pague.\n" .
               "- **Pantalla de Cocina / Barra**: Permite clasificar comandas en las pestañas 'Todos', 'Cocina' y 'Barra'. Los productos se clasifican en Barra si pertenecen a una categoría con la palabra 'bar' o están configurados como producto de barra; en caso contrario van a Cocina.\n" .
               "- **Gestión de Inventario / Insumos**: Para registrar nuevos insumos o materias primas, el usuario debe acceder en el menú lateral a **\"Insumos / Productos\"** (que corresponde a la ruta `/seller/inventory/items` y tiene el título de página \"Insumos y Productos\"). En la parte superior de esta página se encuentra la tarjeta **\"Agregar Nuevo Insumo / Producto\"** con los campos de entrada: Nombre (ej. Harina), Tipo de item (seleccionar \"Insumo / Materia prima\" o \"Producto para venta directa\"), Categoría (ej. Lácteos), Unidad (SUNAT), Tipo tributario, Stock mínimo, Costo unitario, Precio de venta (solo para productos de venta directa) y Código SUNAT (UNSPSC). Luego debe presionar el botón **\"Agregar\"** para guardarlo. El stock inicial no se define en esta pantalla, sino que se gestiona posteriormente a través de compras o ajustes de stock (Kardex).\n" .
               "- **Recursos Humanos (Asistencia y Planilla)**: Los empleados registran su entrada y salida (Clock In/Out) desde su panel. En la pestaña de Planilla, el sistema calcula automáticamente las horas trabajadas, bonos y descuentos para generar el pago neto del periodo.\n\n" .
               "ESQUEMA DE BASE DE DATOS DISPONIBLE:\n" .
               "- **users** (id, firstname, lastname, username, email, phone, balance, status, created_at)\n" .
               "- **drivers** (id, firstname, lastname, username, email, mobile, balance, status, zone_id, fleet_id, online_status, created_at)\n" .
               "- **stores** (id, name, email, phone, address, status, store_type, created_at)\n" .
               "- **fleets** (id, name, invite_code, email, owner_id, commission_type, lizto_commission_rate, driver_commission_rate, status, zone_id, created_at)\n" .
               "- **zones** (id, name, status, created_at)\n" .
               "- **pos_orders** (id, seller_id, store_id, pos_table_id, pos_staff_id, order_no, customer_name, subtotal, discount, total, order_type, status, payment_status, payment_method, created_at)\n" .
               "- **delivery_orders** (id, order_no, store_id, user_id, status, payment_status, total, created_at)\n" .
               "- **pos_expenses** (id, cash_session_id, seller_id, amount, description, expense_date, created_at)\n" .
               "- **pos_cash_sessions** (id, seller_id, pos_register_id, opening_balance, closing_balance, total_sales, total_expenses, total_cash_in, total_cash_out, status, opened_at, closed_at)\n" .
               "- **pos_transactions** (id, cash_session_id, seller_id, pos_order_id, type, amount, description, payment_method, created_at)\n\n" .
               "Asegúrate de formular consultas SQL válidas de MySQL. El campo `created_at` es de tipo datetime.";
    }
}
