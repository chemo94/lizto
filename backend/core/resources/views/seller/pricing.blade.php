@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-crown"></i></span> Planes y Precios
@endsection

@section('seller-content')
<div class="s-content">
    @if($store)
        @php
            $activeStorePackages = $store->storePackages->filter(fn($sp) => $sp->isActive())
                ->sortByDesc(fn($sp) => $sp->expires_at?->timestamp ?? PHP_INT_MAX)->values() ?? collect();
            $activePaidPackage = $activeStorePackages->first(fn($sp) => $sp->package);
        @endphp

        @if(!$activePaidPackage)
        <div class="s-card" style="border: 2px dashed #ef4444; background: rgba(239, 68, 68, 0.05); padding: 24px; border-radius: 12px; margin-bottom: 24px; text-align: center;">
            <div style="font-size: 48px; margin-bottom: 12px;">⚠️</div>
            <h2 style="font-weight: 900; font-size: 20px; color: #ef4444; margin: 0 0 8px;">TU SUSCRIPCIÓN HA VENCIDO</h2>
            <p style="color: var(--s-text-2); font-size: 14px; max-width: 600px; margin: 0 auto 20px; line-height: 1.6;">
                Tu plan actual ha expirado. Para seguir utilizando el Punto de Venta (POS), monitor de cocina, facturación electrónica y reportes de tu negocio, selecciona y renueva uno de nuestros planes a continuación.
            </p>
        </div>
        @endif

        <div class="s-card" style="margin-bottom: 24px; border-left: 4px solid {{ $activePaidPackage ? 'var(--s-primary)' : '#ef4444' }}; background: var(--s-surface-2); display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; padding: 16px 20px; border-radius: 8px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="font-size: 28px; color: {{ $activePaidPackage ? 'var(--s-primary)' : '#ef4444' }}; display: flex; align-items: center;">
                    <i class="las la-crown"></i>
                </div>
                <div>
                    <div style="font-size: 11px; color: var(--s-text-3); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Estado de tu Cuenta</div>
                    @if($activePaidPackage)
                        <div style="font-size: 15px; font-weight: 800; color: var(--s-text-1);">
                            Tu negocio está suscrito al plan <span style="color: var(--s-primary)">{{ $activePaidPackage->package->name }}</span>
                        </div>
                        <div style="font-size: 13px; color: var(--s-text-2); margin-top: 2px;">
                            Fecha de vencimiento: <strong>{{ $activePaidPackage->expires_at ? $activePaidPackage->expires_at->format('d/m/Y h:i A') : 'Nunca' }}</strong>
                        </div>
                    @else
                        <div style="font-size: 15px; font-weight: 800; color: #ef4444;">Sin Suscripción Activa</div>
                        <div style="font-size: 13px; color: var(--s-text-2); margin-top: 2px;">Por favor, selecciona y contrata uno de nuestros planes para comenzar a gestionar tu negocio.</div>
                    @endif
                </div>
            </div>
            <div>
                @if($activePaidPackage)
                    <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                        <i class="las la-check-circle"></i> Activo
                    </span>
                @else
                    <span style="background: rgba(239, 68, 68, 0.15); color: #ef4444; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                        <i class="las la-exclamation-circle"></i> Inactivo
                    </span>
                @endif
            </div>
        </div>
    @endif

    <div style="text-align:center;margin-bottom:32px">
        <h2 style="font-weight:900;font-size:24px;margin:0 0 6px">Escoge el plan ideal para tu negocio</h2>
        <p style="color:var(--s-text-3);font-size:14px;margin:0">Sistema completo para restaurantes o modalidad Solo Envíos</p>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:20px">
        @php
            $planColors = ['basic'=>'#3b82f6','featured'=>'#f59e0b','premium'=>'#8b5cf6','delivery'=>'#06b6d4'];
            $planIcons = ['basic'=>'la-rocket','featured'=>'la-star','premium'=>'la-crown','delivery'=>'la-motorcycle'];
        @endphp
        @foreach($packages as $pkg)
        @php
            $isActive = in_array($pkg->type, $activePackageTypes);
            $isFree = false;
            $features = $pkg->displayFeatures();
        @endphp
        <div class="s-card" style="position:relative;border:2px solid {{ $isActive ? $planColors[$pkg->type] ?? 'var(--s-primary)' : 'var(--s-border)' }};{{ $isActive ? 'background:'.($planColors[$pkg->type] ?? '#16a34a').'06' : '' }};text-align:center; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                @if($isActive)
                <span style="position:absolute;top:-1px;right:16px;font-size:10px;font-weight:900;padding:3px 10px;border-radius:0 0 6px 6px;color:#fff;background:{{ $planColors[$pkg->type] ?? 'var(--s-primary)' }}">ACTUAL</span>
                @endif
                @if(!$isActive && $pkg->type === 'premium')
                <span style="position:absolute;top:14px;right:-4px;font-size:9px;font-weight:900;padding:3px 10px;border-radius:4px;color:#fff;background:linear-gradient(135deg,#8b5cf6,#ec4899);transform:rotate(3deg)">MÁS VENDIDO</span>
                @endif
                <div style="margin-bottom:16px">
                    <div style="width:50px;height:50px;border-radius:14px;background:{{ $planColors[$pkg->type] ?? 'var(--s-primary)' }}20;display:grid;place-items:center;margin:0 auto 10px;font-size:24px;color:{{ $planColors[$pkg->type] ?? 'var(--s-primary)' }}">
                        @if($pkg->icon)
                            <span>{{ $pkg->icon }}</span>
                        @else
                            <i class="las {{ $planIcons[$pkg->type] ?? 'la-box' }}"></i>
                        @endif
                    </div>
                    <h3 style="font-weight:900;font-size:18px;margin:0 0 4px">{{ $pkg->name }}</h3>
                    <div style="font-size:10px;font-weight:800;text-transform:uppercase;color:{{ $planColors[$pkg->type] ?? 'var(--s-primary)' }};margin-bottom:6px">{{ $pkg->service_mode === 'delivery_only' ? 'Solo solicitar envíos' : 'Sistema de restaurante' }}</div>
                    @if($pkg->description)
                    <p style="font-size:12px;color:var(--s-text-3);line-height:1.35;margin:0 0 10px">{{ $pkg->description }}</p>
                    @endif
                    <div style="font-weight:900;font-size:28px;color:{{ $planColors[$pkg->type] ?? 'var(--s-text)' }}">S/ {{ number_format($pkg->price,2) }}</div>
                    <div style="font-size:11px;color:var(--s-text-3)">
                        @if($pkg->duration_days > 0)/ {{ $pkg->duration_days }} días @else / siempre @endif
                    </div>
                </div>
                <ul style="list-style:none;padding:0;margin:0 0 20px;font-size:13px;display:flex;flex-direction:column;gap:8px;text-align:left">
                    @foreach($features as $f)
                    <li style="display:flex;align-items:center;gap:6px;color:var(--s-text-2)">
                        <i class="las la-check-circle" style="color:{{ $planColors[$pkg->type] ?? 'var(--s-success)' }};font-size:16px"></i>
                        {{ $f }}
                    </li>
                    @endforeach
                </ul>
            </div>
            <div>
                @if($isFree)
                <button class="s-btn" style="width:100%;justify-content:center" disabled><i class="las la-check"></i> Plan Actual</button>
                @elseif($isActive)
                    @if($pkg->type !== 'free')
                        @php
                            $sp = $activeStorePackages->firstWhere('package_id', $pkg->id);
                            $isCloseToExpire = false;
                            if ($sp && $sp->expires_at) {
                                $expiresAtDate = $sp->expires_at instanceof \Carbon\Carbon ? $sp->expires_at : \Carbon\Carbon::parse($sp->expires_at);
                                $daysLeft = (int) now()->startOfDay()->diffInDays($expiresAtDate->startOfDay(), false);
                                $isCloseToExpire = $daysLeft <= 7;
                            }
                        @endphp
                        <button
                            type="button"
                            class="s-btn mp-pay-btn"
                            style="width:100%;justify-content:center;background:#22c55e;border-color:#22c55e;color:#fff;font-weight:700;"
                            data-package-id="{{ $pkg->id }}"
                            data-package-name="{{ $pkg->name }}"
                            data-package-price="{{ number_format($pkg->price, 2) }}"
                            data-package-color="{{ $planColors[$pkg->type] ?? '#22c55e' }}">
                            <i class="las la-sync"></i> Renovar Plan
                        </button>
                        @if($sp && $sp->expires_at)
                            @if($isCloseToExpire)
                                <div style="margin-top:8px; color:#ef4444; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; gap:4px;">
                                    <i class="las la-exclamation-circle" style="font-size:14px;"></i> ¡Vence en {{ $daysLeft > 0 ? $daysLeft : 0 }} días! Renueva ahora.
                                </div>
                            @else
                                <div style="margin-top:8px; color:#10b981; font-size:11px; font-weight:600; display:flex; align-items:center; justify-content:center; gap:4px;">
                                    <i class="las la-info-circle" style="font-size:14px;"></i> Quedan {{ $daysLeft }} días de vigencia.
                                </div>
                            @endif
                        @endif
                    @else
                        <button class="s-btn" style="width:100%;justify-content:center;border-color:{{ $planColors[$pkg->type] ?? 'var(--s-border)' }};color:{{ $planColors[$pkg->type] ?? 'var(--s-text)' }}" disabled><i class="las la-check"></i> Plan Activo</button>
                    @endif
                @else
                <button
                    type="button"
                    class="s-btn mp-pay-btn"
                    style="width:100%;justify-content:center;background:{{ $planColors[$pkg->type] ?? 'var(--s-primary)' }};border-color:{{ $planColors[$pkg->type] ?? 'var(--s-primary)' }};color:#fff"
                    data-package-id="{{ $pkg->id }}"
                    data-package-name="{{ $pkg->name }}"
                    data-package-price="{{ number_format($pkg->price, 2) }}"
                    data-package-color="{{ $planColors[$pkg->type] ?? '#3b82f6' }}">
                    <i class="las la-credit-card"></i> Pagar con Tarjeta
                </button>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <!-- Full comparison -->


    @if($payments && $payments->count() > 0)
    <div class="s-card" style="margin-top:24px">
        <h3 class="s-card-title"><i class="las la-history"></i> Historial de Pagos y Suscripciones</h3>
        <div class="s-table-wrapper">
            <table class="s-table">
                <thead>
                    <tr>
                        <th>Fecha de Pago</th>
                        <th>Plan / Paquete</th>
                        <th>Importe</th>
                        <th style="text-align:center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $pay)
                    <tr>
                        <td>{{ $pay->paid_at ? $pay->paid_at->format('d/m/Y h:i A') : $pay->created_at->format('d/m/Y h:i A') }}</td>
                        <td style="font-weight:700">{{ $pay->package->name ?? 'Plan Personalizado' }}</td>
                        <td>S/ {{ number_format($pay->package_amount, 2) }}</td>
                        <td style="text-align:center">
                            @php $statusMap = ['paid'=>['#10b981','Aprobado'],'pending'=>['#f59e0b','Pendiente'],'failed'=>['#ef4444','Rechazado'],'initiated'=>['#64748b','Iniciado']]; $sc = $statusMap[$pay->status] ?? ['#64748b',$pay->status]; @endphp
                            <span style="background: {{ $sc[0] }}22; color: {{ $sc[0] }}; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700;">{{ $sc[1] }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

{{-- ===================== MODAL DE PAGO MERCADOPAGO ===================== --}}
<div id="mp-payment-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.65);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:16px;">
    <div id="mp-modal-box" style="background:var(--s-surface);border-radius:20px;width:100%;max-width:480px;max-height:95vh;overflow-y:auto;box-shadow:0 25px 80px rgba(0,0,0,0.5);position:relative;">

        {{-- Header --}}
        <div id="mp-modal-header" style="padding:24px 24px 0;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div id="mp-plan-icon" style="width:44px;height:44px;border-radius:12px;display:grid;place-items:center;font-size:20px;background:#3b82f620;color:#3b82f6;">
                    <i class="las la-credit-card"></i>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--s-text-3);font-weight:700;text-transform:uppercase;letter-spacing:.5px">Suscripción</div>
                    <div id="mp-plan-name" style="font-size:16px;font-weight:900;color:var(--s-text-1)">Plan</div>
                </div>
            </div>
            <button onclick="closeMpModal()" style="background:none;border:none;cursor:pointer;color:var(--s-text-3);font-size:22px;line-height:1;padding:4px;border-radius:8px;" id="mp-close-btn">
                <i class="las la-times"></i>
            </button>
        </div>

        {{-- Price badge --}}
        <div style="padding:16px 24px 0;">
            <div id="mp-amount-badge" style="background:var(--s-surface-2);border-radius:12px;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:13px;color:var(--s-text-3)">Total a pagar</span>
                <span id="mp-plan-price" style="font-size:24px;font-weight:900;color:var(--s-text-1)">S/ 0.00</span>
            </div>
        </div>

        {{-- Step 1: Card form --}}
        <div id="mp-step-form" style="padding:20px 24px 24px;">
            <div style="font-size:12px;font-weight:700;color:var(--s-text-3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:16px;display:flex;align-items:center;gap:6px;">
                <i class="las la-lock" style="color:#10b981"></i> Pago 100% Seguro · Encriptado por MercadoPago
            </div>

            {{-- Card number --}}
            <div style="margin-bottom:14px">
                <label style="display:block;font-size:12px;font-weight:700;color:var(--s-text-2);margin-bottom:6px;">Número de Tarjeta</label>
                <div style="position:relative">
                    <input id="mp-card-number" type="text" placeholder="0000 0000 0000 0000" maxlength="19"
                        style="width:100%;padding:12px 48px 12px 14px;border-radius:10px;border:1.5px solid var(--s-border);background:var(--s-surface-2);color:var(--s-text-1);font-size:15px;font-family:monospace;letter-spacing:1px;box-sizing:border-box;outline:none;transition:border .2s"
                        oninput="formatCardNumber(this)" onfocus="this.style.borderColor='var(--s-primary)'" onblur="this.style.borderColor='var(--s-border)'">
                    <span id="mp-card-brand-icon" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:22px;color:var(--s-text-4)">
                        <i class="las la-credit-card"></i>
                    </span>
                </div>
            </div>

            {{-- Exp + CVV --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--s-text-2);margin-bottom:6px;">Vencimiento</label>
                    <input id="mp-card-expiry" type="text" placeholder="MM/AA" maxlength="5"
                        style="width:100%;padding:12px 14px;border-radius:10px;border:1.5px solid var(--s-border);background:var(--s-surface-2);color:var(--s-text-1);font-size:15px;box-sizing:border-box;outline:none;transition:border .2s"
                        oninput="formatExpiry(this)" onfocus="this.style.borderColor='var(--s-primary)'" onblur="this.style.borderColor='var(--s-border)'">
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--s-text-2);margin-bottom:6px;">CVV / CVC</label>
                    <input id="mp-card-cvv" type="password" placeholder="•••" maxlength="4"
                        style="width:100%;padding:12px 14px;border-radius:10px;border:1.5px solid var(--s-border);background:var(--s-surface-2);color:var(--s-text-1);font-size:15px;box-sizing:border-box;outline:none;transition:border .2s"
                        onfocus="this.style.borderColor='var(--s-primary)'" onblur="this.style.borderColor='var(--s-border)'">
                </div>
            </div>

            {{-- Cardholder --}}
            <div style="margin-bottom:14px">
                <label style="display:block;font-size:12px;font-weight:700;color:var(--s-text-2);margin-bottom:6px;">Nombre del Titular (como aparece en la tarjeta)</label>
                <input id="mp-card-holder" type="text" placeholder="NOMBRE APELLIDO"
                    style="width:100%;padding:12px 14px;border-radius:10px;border:1.5px solid var(--s-border);background:var(--s-surface-2);color:var(--s-text-1);font-size:14px;box-sizing:border-box;outline:none;text-transform:uppercase;transition:border .2s"
                    oninput="this.value=this.value.toUpperCase()" onfocus="this.style.borderColor='var(--s-primary)'" onblur="this.style.borderColor='var(--s-border)'">
            </div>

            {{-- Doc type + number --}}
            <div style="display:grid;grid-template-columns:120px 1fr;gap:12px;margin-bottom:14px">
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--s-text-2);margin-bottom:6px;">Tipo Doc.</label>
                    <select id="mp-doc-type"
                        style="width:100%;padding:12px 10px;border-radius:10px;border:1.5px solid var(--s-border);background:var(--s-surface-2);color:var(--s-text-1);font-size:14px;box-sizing:border-box;outline:none;transition:border .2s"
                        onfocus="this.style.borderColor='var(--s-primary)'" onblur="this.style.borderColor='var(--s-border)'">
                        <option value="DNI">DNI</option>
                        <option value="CE">CE</option>
                        <option value="RUC">RUC</option>
                        <option value="PASAPORTE">Pasaporte</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:var(--s-text-2);margin-bottom:6px;">Nro. Documento</label>
                    <input id="mp-doc-number" type="text" placeholder="12345678"
                        style="width:100%;padding:12px 14px;border-radius:10px;border:1.5px solid var(--s-border);background:var(--s-surface-2);color:var(--s-text-1);font-size:14px;box-sizing:border-box;outline:none;transition:border .2s"
                        onfocus="this.style.borderColor='var(--s-primary)'" onblur="this.style.borderColor='var(--s-border)'">
                </div>
            </div>

            {{-- Installments (auto-loaded) --}}
            <div id="mp-installments-wrap" style="margin-bottom:20px;display:none">
                <label style="display:block;font-size:12px;font-weight:700;color:var(--s-text-2);margin-bottom:6px;">Cuotas</label>
                <select id="mp-installments"
                    style="width:100%;padding:12px 14px;border-radius:10px;border:1.5px solid var(--s-border);background:var(--s-surface-2);color:var(--s-text-1);font-size:14px;box-sizing:border-box;outline:none;">
                    <option value="1">1 cuota (sin interés)</option>
                </select>
            </div>

            {{-- Error message --}}
            <div id="mp-form-error" style="display:none;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:12px 14px;color:#ef4444;font-size:13px;margin-bottom:16px;display:flex;align-items:flex-start;gap:8px;">
                <i class="las la-exclamation-circle" style="font-size:18px;flex-shrink:0;margin-top:1px"></i>
                <span id="mp-form-error-text"></span>
            </div>

            {{-- Submit button --}}
            <button id="mp-submit-btn" onclick="submitMpPayment()"
                style="width:100%;padding:15px;border-radius:12px;border:none;background:linear-gradient(135deg,#009ee3,#0070b4);color:#fff;font-size:15px;font-weight:900;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px;transition:opacity .2s;letter-spacing:.3px;">
                <i class="las la-lock"></i>
                <span id="mp-submit-text">Confirmar Pago Seguro</span>
            </button>

            <div style="text-align:center;margin-top:14px;font-size:11px;color:var(--s-text-4);display:flex;align-items:center;justify-content:center;gap:6px;">
                <i class="las la-shield-alt"></i> Procesado por MercadoPago · SSL 256-bit
            </div>
        </div>

        {{-- Step 2: Processing --}}
        <div id="mp-step-loading" style="display:none;padding:48px 24px;text-align:center;">
            <div style="width:64px;height:64px;border-radius:50%;background:rgba(0,158,227,.1);display:grid;place-items:center;margin:0 auto 20px;animation:mpPulse 1.5s ease-in-out infinite;">
                <i class="las la-spinner" style="font-size:32px;color:#009ee3;animation:spin 1s linear infinite"></i>
            </div>
            <div style="font-size:17px;font-weight:800;color:var(--s-text-1);margin-bottom:8px">Procesando tu pago...</div>
            <div style="font-size:13px;color:var(--s-text-3)">No cierres ni recargues esta página.</div>
        </div>

        {{-- Step 3: Result --}}
        <div id="mp-step-result" style="display:none;padding:48px 24px;text-align:center;">
            <div id="mp-result-icon" style="width:72px;height:72px;border-radius:50%;display:grid;place-items:center;margin:0 auto 20px;font-size:36px;"></div>
            <div id="mp-result-title" style="font-size:20px;font-weight:900;margin-bottom:10px;"></div>
            <div id="mp-result-msg" style="font-size:14px;color:var(--s-text-2);margin-bottom:28px;line-height:1.6;"></div>
            <button id="mp-result-btn" onclick="handleMpResultBtn()" style="padding:12px 28px;border-radius:10px;border:none;font-size:14px;font-weight:700;cursor:pointer;"></button>
        </div>

    </div>
</div>

<style>
@keyframes mpPulse { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.05);opacity:.85} }
@keyframes spin { to{transform:rotate(360deg)} }
#mp-payment-modal.mp-open { display:flex !important; }
#mp-form-error.mp-show { display:flex !important; }
</style>

<script src="https://sdk.mercadopago.com/js/v2"></script>
<script>
/* ---- state ---- */
let mpInstance = null, mpPublicKey = null, mpCurrentTrx = null, mpProcessUrl = null;
let mpCardBin = '', mpPaymentMethodId = '', mpIssuerId = null, mpResultStatus = '';

/* ---- open modal ---- */
document.querySelectorAll('.mp-pay-btn').forEach(btn => {
    btn.addEventListener('click', async function () {
        const pkgId    = this.dataset.packageId;
        const pkgName  = this.dataset.packageName;
        const pkgPrice = this.dataset.packagePrice;
        const pkgColor = this.dataset.packageColor;

        // update modal header
        document.getElementById('mp-plan-name').textContent  = pkgName;
        document.getElementById('mp-plan-price').textContent = 'S/ ' + pkgPrice;
        document.getElementById('mp-plan-icon').style.background = pkgColor + '20';
        document.getElementById('mp-plan-icon').style.color       = pkgColor;
        document.getElementById('mp-submit-btn').style.background = 'linear-gradient(135deg,#009ee3,#0070b4)';

        showMpStep('form');
        resetMpForm();
        openMpModal();

        // fetch public_key + trx from server
        try {
            const res = await fetch('{{ route("seller.pricing.checkout") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ package_id: pkgId })
            });
            const data = await res.json();
            if (!res.ok || data.error) { showMpError(data.error || 'Error al iniciar el pago.'); return; }

            mpPublicKey  = data.public_key;
            mpCurrentTrx = data.trx;
            mpProcessUrl = data.process_url;

            // init MP SDK
            mpInstance = new MercadoPago(mpPublicKey, { locale: 'es-PE' });
        } catch (e) {
            showMpError('Error de conexión. Intenta de nuevo.');
        }
    });
});

/* ---- card number → detect method ---- */
async function onCardBinChange(bin) {
    if (!mpInstance || bin.length < 6) return;
    if (bin === mpCardBin) return;
    mpCardBin = bin;
    try {
        const pms = await mpInstance.getPaymentMethods({ bin });
        if (pms && pms.results && pms.results.length > 0) {
            const pm = pms.results[0];
            mpPaymentMethodId = pm.id;
            mpIssuerId = pm.issuer?.id || null;
            // update card icon
            const icon = document.getElementById('mp-card-brand-icon');
            if (pm.id.includes('visa'))       icon.innerHTML = '<i class="lab la-cc-visa" style="color:#1a1f71;font-size:26px"></i>';
            else if (pm.id.includes('master')) icon.innerHTML = '<i class="lab la-cc-mastercard" style="color:#eb001b;font-size:26px"></i>';
            else if (pm.id.includes('amex'))   icon.innerHTML = '<i class="lab la-cc-amex" style="color:#007bc1;font-size:26px"></i>';
            else icon.innerHTML = '<i class="las la-credit-card" style="font-size:22px"></i>';

            // load installments
            await loadInstallments();
        }
    } catch(e) {}
}

async function loadInstallments() {
    if (!mpInstance || !mpPaymentMethodId) return;
    const amount = parseFloat(document.getElementById('mp-plan-price').textContent.replace('S/ ','').replace(',',''));
    try {
        const inst = await mpInstance.getInstallments({ amount: String(amount), bin: mpCardBin, paymentMethodId: mpPaymentMethodId });
        if (inst && inst.length > 0 && inst[0].payer_costs) {
            const sel = document.getElementById('mp-installments');
            sel.innerHTML = '';
            inst[0].payer_costs.forEach(i => {
                const opt = document.createElement('option');
                opt.value = i.installments;
                opt.textContent = i.recommended_message || (i.installments + ' cuota(s)');
                sel.appendChild(opt);
            });
            document.getElementById('mp-installments-wrap').style.display = 'block';
        }
    } catch(e) {}
}

/* ---- input formatters ---- */
function formatCardNumber(el) {
    let v = el.value.replace(/\D/g,'').substring(0,16);
    el.value = v.replace(/(.{4})/g,'$1 ').trim();
    const bin = v.substring(0,6);
    if (bin.length >= 6) onCardBinChange(bin);
}
function formatExpiry(el) {
    let v = el.value.replace(/\D/g,'');
    if (v.length >= 3) v = v.substring(0,2) + '/' + v.substring(2,4);
    el.value = v;
}

/* ---- submit ---- */
async function submitMpPayment() {
    hideError();
    const cardNumber = document.getElementById('mp-card-number').value.replace(/\s/g,'');
    const expiry     = document.getElementById('mp-card-expiry').value;
    const cvv        = document.getElementById('mp-card-cvv').value;
    const holder     = document.getElementById('mp-card-holder').value.trim();
    const docType    = document.getElementById('mp-doc-type').value;
    const docNumber  = document.getElementById('mp-doc-number').value.trim();
    const expParts   = expiry.split('/');

    // basic validation
    if (cardNumber.length < 13) return showMpError('Ingresa un número de tarjeta válido.');
    if (expParts.length < 2 || expParts[0].length !== 2 || expParts[1].length !== 2) return showMpError('Ingresa la fecha de vencimiento en formato MM/AA.');
    if (cvv.length < 3) return showMpError('Ingresa el código CVV/CVC.');
    if (holder.length < 3) return showMpError('Ingresa el nombre del titular de la tarjeta.');
    if (!mpInstance) return showMpError('El sistema de pago no está listo. Recarga la página.');

    showMpStep('loading');

    try {
        // create card token
        const tokenData = await mpInstance.createCardToken({
            cardNumber:       cardNumber,
            cardholderName:   holder,
            cardExpirationMonth: expParts[0],
            cardExpirationYear:  '20' + expParts[1],
            securityCode:     cvv,
            identificationType: docType,
            identificationNumber: docNumber,
        });

        if (!tokenData || tokenData.error || !tokenData.id) {
            const cause = tokenData?.cause?.[0]?.description || 'No se pudo procesar la tarjeta.';
            showMpStep('form');
            return showMpError(cause);
        }

        // send to backend
        const res = await fetch(mpProcessUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({
                trx:            mpCurrentTrx,
                card_token:     tokenData.id,
                installments:   parseInt(document.getElementById('mp-installments').value) || 1,
                payment_method: mpPaymentMethodId,
                issuer_id:      mpIssuerId,
                payer_email:    '{{ $seller->email ?? "" }}',
                payer_doc_type: docType,
                payer_doc_num:  docNumber,
            })
        });

        const data = await res.json();

        if (data.status === 'approved') {
            mpResultStatus = 'approved';
            showMpResult('success', '¡Pago Aprobado! 🎉', data.message || 'Tu suscripción ha sido activada correctamente.');
        } else if (data.status === 'pending') {
            mpResultStatus = 'pending';
            showMpResult('pending', 'Pago en Revisión', data.message || 'Tu pago está siendo verificado por MercadoPago.');
        } else {
            mpResultStatus = 'error';
            showMpStep('form');
            showMpError(data.message || 'El pago fue rechazado. Verifica los datos e intenta de nuevo.');
        }
    } catch(e) {
        showMpStep('form');
        showMpError('Error inesperado: ' + (e.message || 'Intenta de nuevo.'));
    }
}

/* ---- result handler ---- */
function showMpResult(type, title, msg) {
    const icon  = document.getElementById('mp-result-icon');
    const ttl   = document.getElementById('mp-result-title');
    const msgEl = document.getElementById('mp-result-msg');
    const btn   = document.getElementById('mp-result-btn');

    if (type === 'success') {
        icon.style.background = 'rgba(16,185,129,.15)';
        icon.style.color      = '#10b981';
        icon.innerHTML        = '<i class="las la-check-circle"></i>';
        ttl.style.color       = '#10b981';
        btn.style.background  = '#10b981';
        btn.style.color       = '#fff';
        btn.textContent       = 'Ver mi Plan Activo';
    } else if (type === 'pending') {
        icon.style.background = 'rgba(245,158,11,.15)';
        icon.style.color      = '#f59e0b';
        icon.innerHTML        = '<i class="las la-clock"></i>';
        ttl.style.color       = '#f59e0b';
        btn.style.background  = '#f59e0b';
        btn.style.color       = '#fff';
        btn.textContent       = 'Entendido';
    }

    ttl.textContent = title;
    msgEl.textContent = msg;
    showMpStep('result');
}

function handleMpResultBtn() {
    closeMpModal();
    if (mpResultStatus === 'approved') window.location.reload();
}

/* ---- helpers ---- */
function openMpModal()  { document.getElementById('mp-payment-modal').classList.add('mp-open'); document.body.style.overflow='hidden'; }
function closeMpModal() { document.getElementById('mp-payment-modal').classList.remove('mp-open'); document.body.style.overflow=''; }
function showMpStep(step) {
    document.getElementById('mp-step-form').style.display    = step === 'form'    ? 'block'  : 'none';
    document.getElementById('mp-step-loading').style.display = step === 'loading' ? 'block'  : 'none';
    document.getElementById('mp-step-result').style.display  = step === 'result'  ? 'block'  : 'none';
    document.getElementById('mp-close-btn').style.display    = step === 'loading' ? 'none'   : 'block';
}
function showMpError(msg) {
    const el = document.getElementById('mp-form-error');
    document.getElementById('mp-form-error-text').textContent = msg;
    el.style.display = 'flex';
}
function hideError() { document.getElementById('mp-form-error').style.display = 'none'; }
function resetMpForm() {
    ['mp-card-number','mp-card-expiry','mp-card-cvv','mp-card-holder','mp-doc-number'].forEach(id => { const el = document.getElementById(id); if(el) el.value = ''; });
    document.getElementById('mp-card-brand-icon').innerHTML = '<i class="las la-credit-card"></i>';
    document.getElementById('mp-installments-wrap').style.display = 'none';
    hideError();
    mpCardBin = ''; mpPaymentMethodId = ''; mpIssuerId = null;
}

// close on backdrop click
document.getElementById('mp-payment-modal').addEventListener('click', function(e) {
    if (e.target === this) closeMpModal();
});
</script>
@endsection
