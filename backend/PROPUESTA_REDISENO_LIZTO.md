# PROPUESTA DE REDISEÑO DE COMUNICACIÓN - LIZTO

## Análisis del Problema Actual

**Situación actual:**
- La ruta raíz (`/`) muestra directamente el marketplace de delivery
- Los visitantes perciben Lizto como "una app de delivery más"
- No existe una narrativa que conecte los 4 pilares del ecosistema
- Las secciones están desconectadas entre sí
- Falta un "mapa mental" que muestre cómo todo se integra

**Lo que falta comunicar:**
- Lizto NO es solo delivery
- Lizto NO es solo taxi
- Lizto es un **ecosistema urbano integrado** con 6 aplicaciones interconectadas

---

## 1. NUEVA ESTRUCTURA DE LANDING PAGE

### Estructura propuesta (secciones de arriba a abajo):

```
┌─────────────────────────────────────────────────────────────┐
│  HEADER: Logo | Ecosistema | Negocios | Taxi | Contacto    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  HERO PRINCIPAL                                             │
│  "Una ciudad conectada. Una sola plataforma."               │
│  [Cómo funciona el ecosistema - diagrama visual]            │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  ECOSSISTEMA LIZTO (4 columnas visuales)                    │
│  Usuarios → Negocios → Repartidores → Conductores           │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  SERVICIOS PRINCIPALES (3 cards grandes)                    │
│  [Delivery] [Taxi] [Favor]                                  │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  PARA NEGOCIOS (B2B)                                        │
│  Panel web + App + POS + Facturación                        │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  CÓMO FUNCIONA (Flujo visual conectado)                     │
│  Paso 1 → Paso 2 → Paso 3 → Paso 4                         │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  NÚMEROS QUE HABLAN                                         │
│  Contadores animados + testimonios                          │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  DESCARGA LA APP (6 apps disponibles)                       │
│  Usuario | Negocio | Repartidor | Conductor | Vendedor      │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  CTA FINAL                                                  │
│  "Únete al ecosistema Lizto"                                │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  FOOTER                                                     │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. HERO PRINCIPAL - NUEVO MENSAJE

### Texto actual (problemático):
> "Tu ciudad, más cerca."
> "Pide un taxi o solicita un delivery desde una sola plataforma."

**Problema:** Reduce Lizto a 2 servicios.

### Texto propuesto:

**Headline principal:**
> **"Una ciudad conectada. Una sola plataforma."**

**Subheadline:**
> "Lizto une personas, negocios, repartidores y conductores en un mismo ecosistema. Todo funciona en tiempo real, todo está conectado."

**Tagline de refuerzo:**
> "No somos una app de delivery. No somos un taxi. Somos la infraestructura que conecta tu ciudad."

**CTA principal:**
> "Explora el ecosistema" (scroll a sección de servicios)

**CTA secundario:**
> "Soy negocio" → Link a `/negocios`

### Diagrama visual del Hero:

```
                    ┌─────────────┐
                    │   LIZTO     │
                    │  Plataforma │
                    │   Central   │
                    └──────┬──────┘
                           │
          ┌────────────────┼────────────────┐
          │                │                │
          ▼                ▼                ▼
   ┌──────────┐    ┌──────────┐    ┌──────────┐
   │ Usuarios │    │ Negocios │    │ Servicios│
   │ Compran  │    │ Venden   │    │ Conectan │
   └────┬─────┘    └────┬─────┘    └────┬─────┘
        │               │               │
        ▼               ▼               ▼
   ┌──────────┐    ┌──────────┐    ┌──────────┐
   │Delivery  │    │  POS +   │    │Repartidores
   │  Taxi    │    │ Inventario│   │ Conductores
   │  Favor   │    │  QR +    │    │          │
   │          │    │ Facturación│   │          │
   └──────────┘    └──────────┘    └──────────┘
```

---

## 2B. ESTRUCTURA DE NAVEGACIÓN COMPLETA (Incluye Marketplace)

### El problema actual con el marketplace:
- La ruta raíz (`/`) va directo al marketplace de delivery
- El visitante NO ve la landing que explica el ecosistema
- Pierde el contexto de que Lizto es más que delivery

### Solución: Reorganizar las rutas

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         ESTRUCTURA DE URLs PROPUESTA                        │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  / (raíz)  ──────────►  LANDING PRINCIPAL (Nueva)                          │
│                         "Una ciudad conectada. Una sola plataforma."        │
│                         Muestra el ecosistema completo                      │
│                                                                             │
│  /delivery  ─────────►  MARKETPLACE DE DELIVERY (Actual)                    │
│                         Donde los usuarios compran                          │
│                         Se accede desde la landing                          │
│                                                                             │
│  /taxi  ─────────────►  SERVICIO DE TAXI (Actual)                          │
│                         Donde los usuarios solicitan taxi                   │
│                                                                             │
│  /favor  ────────────►  LIZTO FAVOR (Actual)                               │
│                         Donde los usuarios solicitan encargos               │
│                                                                             │
│  /negocios  ─────────►  LANDING B2B (Actual)                               │
│                         Donde los negocios se registran                     │
│                                                                             │
│  /inicio  ───────────►  Redirige a / ( Landing principal)                   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Cómo se conecta todo (Flujo del usuario):

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         FLUJO DEL USUARIO                                   │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  1. PRIMERA VISITA                                                          │
│     Usuario llega a / (landing principal)                                   │
│     Ve: "Una ciudad conectada. Una sola plataforma."                        │
│     Entiende: Lizto es un ecosistema, no solo delivery                      │
│                                                                             │
│  2. EXPLORA EL ECOSISTEMA                                                   │
│     Ve: Los 4 pilares (Usuarios, Negocios, Repartidores, Conductores)      │
│     Ve: Los 3 servicios (Delivery, Taxi, Favor)                            │
│     Ve: Cómo funciona el flujo conectado                                    │
│                                                                             │
│  3. DECIDE ACCIONAR                                                          │
│     Opción A: "Quiero comprar" → Click en "Delivery" → /delivery           │
│     Opción B: "Quiero viajar" → Click en "Taxi" → /taxi                    │
│     Opción C: "Soy negocio" → Click en "Negocios" → /negocios              │
│     Opción D: "Descargo la app" → Click en app → Store                     │
│                                                                             │
│  4. DENTRO DEL MARKETPLACE                                                   │
│     El marketplace (/delivery) mantiene su funcionalidad actual             │
│     Pero ahora el usuario SABE que es parte de algo más grande              │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Header actualizado:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  HEADER NAVEGACIÓN                                                          │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Logo: lizto                                                                │
│                                                                             │
│  Nav: [Ecosistema] [Delivery] [Taxi] [Negocios] [Contacto]                 │
│                                                                             │
│  CTA: [Soy negocio]                                                        │
│                                                                             │
│  NOTA: "Ecosistema" es la landing principal (/)                             │
│        "Delivery" va al marketplace (/delivery)                             │
│        "Taxi" va al servicio de taxi (/taxi)                               │
│        "Negocios" va a la landing B2B (/negocios)                           │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Marketplace: Cómo integrarlo visualmente

El marketplace actual (`/delivery`) no necesita cambiar su funcionalidad, pero SÍ puede mejorar su header para recordar al usuario que es parte del ecosistema:

**Banner superior en el marketplace:**
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  🌐 Lizto Ecosistema: Delivery | Taxi | Negocios | Favor    [Ir a inicio] │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  MARKETPLACE ACTUAL                                                         │
│  "¿Qué quieres pedir hoy?"                                                  │
│  [Buscador] [Categorías] [Tiendas]                                         │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

**Texto del banner:**
> "Lizto es más que delivery. Explora taxi, favor y más."

**CTA del banner:**
> "Descubre el ecosistema" → Link a `/` (landing principal)

### Beneficios de esta estructura:

1. **Primera impresión correcta:** El usuario llega a `/` y entiende que Lizto es un ecosistema
2. **Marketplace preservado:** `/delivery` sigue funcionando igual
3. **Conexión visible:** El banner en el marketplace recuerda los otros servicios
4. **Navegación clara:** El header muestra todas las opciones
5. **SEO mejorado:** La landing principal puede posicionar para términos de ecosistema

### Implementación técnica:

1. **Mover la landing actual** de `/inicio` a `/` (raíz)
2. **Mover el marketplace** de `/` a `/delivery` (ya existe)
3. **Actualizar el header** para reflejar la nueva navegación
4. **Agregar banner** en el marketplace con link a la landing
5. **Crear redirección** de `/inicio` a `/` (para compatibilidad)

### Diagrama: El Marketplace dentro del Ecosistema

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                     EL MARKETPLACE EN EL ECOSISTEMA                        │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│                            ┌─────────────────┐                              │
│                            │    LIZTO        │                              │
│                            │   PLATAFORMA    │                              │
│                            │     CENTRAL     │                              │
│                            └────────┬────────┘                              │
│                                     │                                       │
│         ┌───────────────────────────┼───────────────────────────┐           │
│         │                           │                           │           │
│         ▼                           ▼                           ▼           │
│  ┌─────────────┐            ┌─────────────┐            ┌─────────────┐      │
│  │  USUARIOS   │            │  NEGOCIOS   │            │  SERVICIOS  │      │
│  │             │            │             │            │             │      │
│  │  COMPRAN    │◄──────────►│  VENDEN     │◄──────────►│  CONECTAN   │      │
│  │             │            │             │            │             │      │
│  └──────┬──────┘            └──────┬──────┘            └──────┬──────┘      │
│         │                           │                           │           │
│         │                           │                           │           │
│         ▼                           ▼                           ▼           │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                                                                     │   │
│  │                        MARKETPLACE                                  │   │
│  │                                                                     │   │
│  │  ┌─────────────────────────────────────────────────────────────┐   │   │
│  │  │                                                             │   │   │
│  │  │   El marketplace es el PUNTO DE ENCUENTRO donde:            │   │   │
│  │  │                                                             │   │   │
│  │  │   • Los USUARIOS compran productos                          │   │   │
│  │  │   • Los NEGOCIOS reciben pedidos                            │   │   │
│  │  │   • Los REPARTIDORES realizan entregas                      │   │   │
│  │  │   • Todo se sincroniza en TIEMPO REAL                       │   │   │
│  │  │                                                             │   │   │
│  │  └─────────────────────────────────────────────────────────────┘   │   │
│  │                                                                     │   │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐           │   │
│  │  │Restaurantes│ │Farmacias │  │Licorerías│  │ Tiendas  │           │   │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────┘           │   │
│  │                                                                     │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  FLUJO EN EL MARKETPLACE:                                                   │
│                                                                             │
│  Usuario ──► Busca tienda ──► Elige productos ──► Paga                     │
│      │                              │                  │                    │
│      │                              ▼                  │                    │
│      │                      Negocio recibe             │                    │
│      │                      y prepara pedido           │                    │
│      │                              │                  │                    │
│      │                              ▼                  │                    │
│      │                      Repartidor se              │                    │
│      │                      asigna automáticamente     │                    │
│      │                              │                  │                    │
│      │                              ▼                  │                    │
│      └────────────────────── Recibe en minutos ◄──────┘                    │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Por qué el marketplace es fundamental:

| Razón | Explicación |
|-------|-------------|
| **Es donde ocurre la acción** | El marketplace es donde los usuarios realmente compran |
| **Conecta usuarios con negocios** | Es el puente entre la demanda y la oferta |
| **Genera ingresos** | Las comisiones por delivery son una fuente de revenue |
| **Retiene usuarios** | Un buen marketplace hace que los usuarios vuelvan |
| **Atrae negocios** | Más usuarios = más ventas para los negocios |

### Cómo el marketplace refuerza el ecosistema:

1. **Para el usuario:** "Puedo comprar de CUALQUIER tienda en una sola app"
2. **Para el negocio:** "Mis productos llegan a miles de clientes sin invertir en delivery"
3. **Para el repartidor:** "Tengo pedidos constantes y puedo ganar dinero a mi ritmo"
4. **Para la plataforma:** "Cada transición fortalece la red"

### Mensaje clave del marketplace:

> **"No solo pedir comida. Conectar con tu ciudad."**

El marketplace no es solo un lugar para pedir comida. Es el punto donde la ciudad se conecta:
- Los negocios locales llegan a más personas
- Los repartidores tienen trabajo constante
- Los usuarios tienen todo a un clic
- Todo funciona en tiempo real

---

## 3. SECCIÓN: EL ECOSSISTEMA LIZTO

### Headline:
> **"Cuatro pilares. Una plataforma."**

### Subheadline:
> "Cada actor del ecosistema tiene su herramienta. Todos están conectados en tiempo real."

### Layout: 4 columnas con icono + título + descripción + screenshot

#### Columna 1: USUARIOS
**Ícono:** Persona con celular
**Título:** "Compra y solicita servicios"
**Descripción:**
> "Accede a tiendas locales, pide delivery, viaja en taxi o solicita un encargo. Todo desde una sola app."

**Apps incluidas:**
- App de delivery
- App de taxi
- Lizto Favor

**Elemento visual:** Mockup de celular mostrando la app de usuario

---

#### Columna 2: NEGOCIOS
**Ícono:** Tienda / Local
**Título:** "Administra tu operación"
**Descripción:**
> "Gestiona pedidos, inventario, ventas y facturación. Todo conectado con tu delivery propio y la red de repartidores Lizto."

**Herramientas incluidas:**
- Panel web para negocios
- App para negocios
- Punto de venta (POS)
- Menú digital con QR
- Gestión de inventario
- Facturación electrónica

**Elemento visual:** Mockup de laptop mostrando el panel de negocios

---

#### Columna 3: REPARTIDORES
**Ícono:** Moto con paquete
**Título:** "Realiza entregas"
**Descripción:**
> "Conecta con negocios y usuarios. Acepta pedidos, entrega en tiempo real y gana por cada envío."

**App incluida:**
- App para repartidores

**Elemento visual:** Mockup de celular con mapa de entregas

---

#### Columna 4: CONDUCTORES
**Ícono:** Auto con persona
**Título:** "Realiza viajes"
**Descripción:**
> "Brinda servicio de taxi con tarifas transparentes. Acepta viajes, navega con GPS y recibe pagos automáticos."

**App incluida:**
- App para conductores

**Elemento visual:** Mockup de celular con mapa de viajes

---

### Conector visual entre columnas:

```
USUARIOS ◄──────────────► NEGOCIOS
   │                           │
   │      LIZTO CENTRAL        │
   │    (Tiempo Real)          │
   │                           │
REPARTIDORES ◄─────────► CONDUCTORES
```

**Texto debajo del diagrama:**
> "Todos los participantes están sincronizados. Cuando un usuario pide, el negocio recibe, el repartidor se asigna y todo se actualiza en tiempo real."

---

## 4. SECCIÓN: SERVICIOS PRINCIPALES

### Headline:
> **"Lo que puedes hacer con Lizto"**

### Layout: 3 cards grandes con imagen de fondo

#### Card 1: DELIVERY
**Título:** "Delivery"
**Descripción:**
> "Restaurantes, farmacias, licorerías, tiendas y más. Tu pedido llega en minutos."

**Datos clave:**
- X+ tiendas disponibles
- Entrega en minutos
- Envío gratis en tu primer pedido

**CTA:** "Explorar tiendas" → Marketplace

---

#### Card 2: TAXI
**Título:** "Taxi"
**Descripción:**
> "Viaja seguro por tu ciudad. Conductores verificados, tarifas transparentes y seguimiento en tiempo real."

**Datos clave:**
- Conductores verificados
- Tarifa calculada por Google Maps
- Seguimiento en tiempo real

**CTA:** "Solicitar taxi" → `/taxi`

---

#### Card 3: FAVOR
**Título:** "Lizto Favor"
**Descripción:**
> "¿Necesitas que te compren algo? ¿O enviar un paquete? Un favor lo hace por ti."

**Datos clave:**
- Compras por encargo
- Envíos urgentes
- Flexible y personalizado

**CTA:** "Solicitar favor" → `/favor`

---

## 5. SECCIÓN: PARA NEGOCIOS (B2B)

### Headline:
> **"Haz crecer tu negocio con Lizto"**

### Subheadline:
> "Herramientas profesionales para vender más, administrar mejor y conectar con miles de clientes."

### Layout: Grid de 6 beneficios

| Beneficio | Descripción |
|-----------|-------------|
| **0% Comisión** | No pagas por cada venta. Quédate con tus ganancias. |
| **App Propia** | Tu tienda con tu marca, en la App Store y Google Play. |
| **POS Profesional** | Punto de venta integrado con inventario y facturación. |
| **QR en Mesas** | Tus clientes piden desde su celular escaneando un código. |
| **Delivery Propio** | Gestiona tus propios repartidores o usa la red Lizto. |
| **Analytics Premium** | Dashboards con ventas, productos más vendidos y tendencias. |

### CTA:
> "Crear mi tienda gratis" → WhatsApp Business

### Mockup visual:
Laptop + Celular mostrando el panel de negocios con:
- Dashboard de ventas
- Lista de pedidos
- Inventario
- Facturación

---

## 6. SECCIÓN: CÓMO FUNCIONA (Flujo Visual)

### Headline:
> **"Todo conectado en 4 pasos"**

### Flujo visual (flecha que conecta los pasos):

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  1. ELIGE   │ ──► │  2. PIDE    │ ──► │  3. GESTIONA│ ──► │  4. RECIBE  │
│             │     │             │     │             │     │             │
│ Un usuario  │     │ El pedido   │     │ El negocio  │     │ El reparti- │
│ abre la app │     │ llega al    │     │ prepara y   │     │ dor entre- │
│ y elige     │     │ negocio en  │     │ asigna un   │     │ ga en       │
│ tienda o    │     │ tiempo real │     │ repartidor  │     │ minutos     │
│ servicio    │     │             │     │ o conductor │     │             │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
       │                   │                   │                   │
       ▼                   ▼                   ▼                   ▼
  App Usuario         Lizto Central      App Negocio        App Repartidor
                                        Panel Web
```

### Texto explicativo debajo:
> "Detrás de cada pedido hay un ecosistema funcionando. El usuario compra, el negocio administra, el repartidor entrega y Lizto lo conecta todo en tiempo real."

---

## 7. SECCIÓN: NÚMEROS QUE HABLAN

### Headline:
> **"El impacto de Lizto en tu ciudad"**

### Contadores animados:

| Número | Label |
|--------|-------|
| X+ | Tiendas activas |
| X+ | Pedidos entregados |
| X+ | Repartidores verificados |
| X+ | Conductores activos |
| X+ | Usuarios satisfechos |

### Testimonios:

**Testimonio 1 - Negocio:**
> "Desde que uso Lizto, mis ventas aumentaron un 40%. El POS y la facturación me ahorran horas."
> — *Gerente de restaurante en Tarapoto*

**Testimonio 2 - Usuario:**
> "Puedo pedir de cualquier tienda y llegar en taxi sin cambiar de app. Es súper práctico."
> — *Usuario frecuente*

**Testimonio 3 - Repartidor:**
> "Gano dinero a mi ritmo. La app es fácil de usar y los pagos son rápidos."
> — *Repartidor Lizto*

---

## 8. SECCIÓN: DESCARGA LA APP

### Headline:
> **"Descarga la app que necesitas"**

### Layout: 6 cards (2 filas de 3)

| App | Para quién | Stores |
|-----|------------|--------|
| **Lizto - Delivery y Taxi** | Usuarios que compran y viajan | iOS / Android |
| **Lizto Negocios** | Dueños de tiendas y restaurantes | iOS / Android |
| **Lizto Repartidor** | Personas que hacen entregas | iOS / Android |
| **Lizto Conductor** | Personas que dan servicio de taxi | iOS / Android |
| **Lizto Vendedor** | Vendedores con POS | Web |
| **Lizto Admin** | Administradores de plataforma | Web |

---

## 9. SECCIÓN: CTA FINAL

### Headline:
> **"Únete al ecosistema que conecta tu ciudad"**

### Subheadline:
> "Ya sea que quieras comprar, vender, entregar o conducir, Lizto tiene tu lugar."

### CTAs:
- "Soy usuario" → Descarga app
- "Soy negocio" → `/negocios`
- "Quiero repartir" → Descarga app repartidor
- "Quiero conducir" → Descarga app conductor

---

## 10. MENSAJES DE MARCA (Brand Messaging)

### Brand Essence:
> **"Conectamos ciudades"**

### Brand Promise:
> "Lizto es la infraestructura tecnológica que permite que personas, negocios y servicios se conecten en tiempo real."

### Brand Pillars:

1. **CONEXIÓN**
   > "Todo está conectado. Cada acción en una parte del ecosistema se refleja en las demás."

2. **CONFIANZA**
   > "Repartidores verificados. Conductores registrados. Negocios validados. Tu seguridad es prioridad."

3. **CERCA**
   > "Lo que necesitas, cuando lo necesitas. En minutos, no en horas."

4. **SIMPLICIDAD**
   > "Una app para todo. No necesitas cambiar de plataforma."

### Brand Voice:
- **Tono:** Cercano, profesional, confiable
- **Estilo:** Directo, sin rodeos, enfocado en beneficios
- **Evitar:** Jerga técnica, promesas vacías, exageraciones

### Taglines alternativos:

| Opción | Tagline |
|--------|---------|
| A (Recomendada) | "Una ciudad conectada. Una sola plataforma." |
| B | "Todo conectado. Tú en el centro." |
| C | "La infraestructura de tu ciudad." |
| D | "Conectamos personas, negocios y servicios." |

---

## 11. STORYTELLING - NARRATIVA DE MARCA

### Estructura narrativa:

**1. EL PROBLEMA (Contexto):**
> "En cada ciudad hay cientos de negocios, miles de personas y servicios repartidos por todos lados. Pero no están conectados. Cada app es una isla. Cada plataforma es un silo."

**2. LA SOLUCIÓN (Lizto):**
> "Lizto crea la conexión. Una plataforma donde los negocios venden, los usuarios compran, los repartidores entregan y los conductores mueven la ciudad. Todo en tiempo real. Todo sincronizado."

**3. EL IMPACTO (Resultado):**
> "Un negocio que antes solo vendía en local ahora alcanza a miles de clientes. Un usuario que antes pedía por teléfono ahora tiene opciones en segundos. Una ciudad que antes se movía por fragmentos ahora funciona como un todo."

**4. LA VISIÓN (Futuro):**
> "Lizto no es solo una app. Es la infraestructura que conecta tu ciudad. Y esto apenas comienza."

---

## 12. DIAGRAMAS CONCEPTUALES

### Diagrama 1: Ecosistema Central

```
                        ┌─────────────────────┐
                        │                     │
                        │      LIZTO          │
                        │   PLATAFORMA        │
                        │    CENTRAL          │
                        │                     │
                        │  • Tiempo real      │
                        │  • Sincronización   │
                        │  • Pagos integrados │
                        │  • Geolocalización  │
                        └──────────┬──────────┘
                                   │
           ┌───────────────────────┼───────────────────────┐
           │                       │                       │
           ▼                       ▼                       ▼
    ┌─────────────┐        ┌─────────────┐        ┌─────────────┐
    │             │        │             │        │             │
    │  COMPRAN    │        │  VENDEN     │        │  CONECTAN   │
    │             │        │             │        │             │
    │  Usuarios   │◄──────►│  Negocios   │◄──────►│  Servicios  │
    │             │        │             │        │             │
    └──────┬──────┘        └──────┬──────┘        └──────┬──────┘
           │                       │                       │
           ▼                       ▼                       ▼
    ┌─────────────┐        ┌─────────────┐        ┌─────────────┐
    │ • Delivery  │        │ • POS       │        │ • Reparti-  │
    │ • Taxi      │        │ • Inventario│        │   dors      │
    │ • Favor     │        │ • QR Menú   │        │ • Conduc-   │
    │             │        │ • Factura-  │        │   tores     │
    │             │        │   ción      │        │             │
    └─────────────┘        └─────────────┘        └─────────────┘
```

### Diagrama 2: Flujo de un Pedido

```
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│ USUARIO  │    │ LIZTO    │    │ NEGOCIO  │    │ REPARTIDOR│
│          │    │ CENTRAL  │    │          │    │          │
└────┬─────┘    └────┬─────┘    └────┬─────┘    └────┬─────┘
     │               │               │               │
     │  1. Abre app  │               │               │
     │──────────────►│               │               │
     │               │               │               │
     │  2. Selecciona│               │               │
     │     tienda    │               │               │
     │──────────────►│               │               │
     │               │               │               │
     │               │  3. Notifica  │               │
     │               │     nuevo     │               │
     │               │     pedido    │               │
     │               │──────────────►│               │
     │               │               │               │
     │               │               │  4. Acepta    │
     │               │               │     pedido    │
     │               │◄──────────────│               │
     │               │               │               │
     │               │  5. Asigna    │               │
     │               │     repartidor│               │
     │               │──────────────────────────────►│
     │               │               │               │
     │  6. Tracking  │               │               │
     │     en tiempo │               │  7. Recoge    │
     │     real      │               │     pedido    │
     │◄──────────────│               │◄──────────────│
     │               │               │               │
     │               │               │               │  8. Entrega
     │               │               │               │     al
     │               │               │               │     usuario
     │◄──────────────────────────────────────────────│
     │               │               │               │
     │  9. Confirma  │               │               │
     │     entrega   │               │               │
     │──────────────►│               │               │
     │               │               │               │
```

---

## 13. IDEAS DE ILUSTRACIONES

### Estilo visual recomendado:
- **Ilustraciones planas** (flat design) con colores de marca
- **Personas diversas** usando las apps
- **Ciudad estilizada** con edificios, calles, negocios
- **Conexiones visuales** (líneas, puntos, redes) entre elementos
- **Mockups de dispositivos** (celulares, laptops) mostrando las apps

### Ilustraciones específicas:

#### 1. Hero - "Ciudad Conectada"
- Vista aérea de una ciudad estilizada
- Líneas de conexión entre puntos (usuarios, negocios, repartidores)
- Colores de marca (#16a34a verde) para las conexiones
- Etiquetas flotantes: "Delivery", "Taxi", "Tienda", "Repartidor"

#### 2. Ecosistema - "Los 4 Pilares"
- 4 personas estilizadas en una fila
- Cada una con un ícono representativo
- Líneas conectivas entre ellas
- Fondo con patrón de red/sincronización

#### 3. Flujo - "Cómo Funciona"
- Secuencia de 4 escenas miniatura
- Persona con celular → Moto de delivery → Cocina de restaurante → Persona recibiendo
- Flechas fluidas conectando las escenas

#### 4. Negocios - "Herramientas"
- Laptop con dashboard de ventas
- Celular con app de negocio al lado
- Iconos flotantes: POS, QR, Inventario, Factura

#### 5. CTA Final - "Únete"
- Manos sosteniendo celulares con las diferentes apps
- Fondo con gradient de marca
- Texto superpuesto con CTA

---

## 14. JERARQUÍA DE INFORMACIÓN

### Nivel 1 (Más importante - Hero):
1. Headline principal
2. Subheadline
3. Diagrama del ecosistema
4. CTAs principales

### Nivel 2 (Sections principales):
1. Los 4 pilares del ecosistema
2. Los 3 servicios (Delivery, Taxi, Favor)
3. Para negocios (B2B)

### Nivel 3 (Sections de soporte):
1. Cómo funciona (flujo visual)
2. Números y testimonios
3. Descarga de apps

### Nivel 4 (Periférico):
1. CTA final
2. Footer
3. Newsletter

---

## 15. ESTRATEGIA DE POSICIONAMIENTO

### Posicionamiento actual (problemático):
> "Lizto es una app de delivery y taxi"

### Posicionamiento propuesto:
> **"Lizto es la plataforma tecnológica que conecta personas, negocios, repartidores y conductores en una sola red en tiempo real."**

### Competencia y diferenciación:

| Competidor | Percepción | Lizto (Diferenciador) |
|------------|------------|----------------------|
| Rappi | App de delivery | Ecosistema completo (delivery + taxi + negocio + POS) |
| Uber | App de taxi | Conecta con negocios y delivery |
| PedidosYa | Marketplace | Herramientas B2B (POS, facturación, inventario) |
| Shopify | Plataforma B2B | Integración con delivery y taxi propio |

### Mensaje competitivo:
> "Otros te dan una app. Lizto te da un ecosistema."

### Proof points (pruebas):
1. 6 aplicaciones interconectadas
2. Panel web para negocios
3. POS integrado
4. Facturación electrónica
5. Gestión de inventario
6. Menú digital con QR
7. Delivery propio
8. Red de repartidores
9. Servicio de taxi
10. Todo en tiempo real

---

## 16. ACCIONES DE IMPLEMENTACIÓN

### Prioridad 1 (Alto impacto, bajo esfuerzo):
1. **Cambiar el Hero de `/inicio`** con el nuevo headline y diagrama
2. **Agregar sección "Ecosistema"** con los 4 pilares
3. **Actualizar el header** para reflejar "Ecosistema" en la navegación
4. **Modificar textos del footer** para reforzar el posicionamiento

### Prioridad 2 (Alto impacto, esfuerzo medio):
1. **Crear sección "Cómo funciona"** con flujo visual conectado
2. **Agregar sección "Para negocios"** en la landing principal
3. **Actualizar sección de descarga de apps** mostrando las 6 apps
4. **Agregar testimonios** de cada tipo de usuario

### Prioridad 3 (Impacto medio, esfuerzo bajo):
1. **Crear ilustraciones** del ecosistema
2. **Agregar counters animados** con datos reales
3. **Mejorar estructura del marketplace** con mejor narrativa
4. **Actualizar schema.org** con información del ecosistema

---

## 17. MÉTRICAS DE ÉXITO

### KPIs a monitorear:
1. **Tiempo en página** (objetivo: +30%)
2. **Scroll depth** (objetivo: 70%+ llegan a sección de ecosistema)
3. **CTR en CTAs** (objetivo: +25%)
4. **Tasa de registro de negocios** (objetivo: +40%)
5. **Descargas de apps** (objetivo: +30% distribución en las 6 apps)
6. **Percepción de marca** (encuesta: "¿Qué es Lizto?" - objetivo: 80%+ responden "plataforma/ecosistema" en vez de "delivery/taxi")

---

## 18. RESUMEN EJECUTIVO

### El problema:
Lizto se percibe como una app de delivery/taxi porque la landing principal muestra solo esos servicios.

### La solución:
Rediseñar la comunicación para mostrar el ecosistema completo con:
1. Hero que muestre la visión de plataforma conectada
2. Sección de ecosistema con los 4 pilares
3. Flujo visual que conecte todos los actores
4. Narrativa que posicione a Lizto como infraestructura, no como app

### El resultado:
Los visitantes entenderán que Lizto es:
- Una **plataforma** (no una app)
- Un **ecosistema** (no un servicio)
- **Conectado** (no fragmentado)
- **Para todos** (usuarios, negocios, repartidores, conductores)

### El mensaje clave:
> **"Una ciudad conectada. Una sola plataforma."**
