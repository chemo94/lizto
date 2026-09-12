@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-motorcycle"></i></span> Solicitar Envío
@endsection

@push('style')
<style>
/* ═══════════════════════════════════════════
   UBER DIRECT STYLE — DELIVERY REQUEST FORM
   ═══════════════════════════════════════════ */

/* ── Shipment Type Selector ── */
.shipment-type-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 10px;
    margin-bottom: 4px;
}

.shipment-type-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 14px 8px;
    border: 2px solid var(--s-border);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    background: var(--s-card-bg, #fff);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.shipment-type-card:hover {
    border-color: #94a3b8;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.shipment-type-card.active {
    border-color: #22c55e;
    background: rgba(34, 197, 94, 0.04);
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
}

.shipment-type-card.active::after {
    content: '✓';
    position: absolute;
    top: 6px;
    right: 8px;
    font-size: 10px;
    font-weight: 800;
    color: #22c55e;
    background: rgba(34,197,94,0.15);
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.shipment-type-icon {
    font-size: 28px;
    line-height: 1;
    transition: transform 0.2s;
}

.shipment-type-card.active .shipment-type-icon {
    transform: scale(1.15);
}

.shipment-type-label {
    font-size: 11.5px;
    font-weight: 700;
    color: var(--s-text);
    line-height: 1.2;
}

.shipment-type-desc {
    font-size: 10px;
    color: var(--s-text-3);
    line-height: 1.2;
}

/* ── Section Headers ── */
.form-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 1.5px solid var(--s-border);
}

.form-section-header h4 {
    margin: 0;
    font-size: 14px;
    font-weight: 800;
    color: var(--s-text);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-section-header .section-icon {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}

/* ── Address Input Cards ── */
.address-card {
    background: var(--s-card-bg, #fff);
    border: 1.5px solid var(--s-border);
    border-radius: 14px;
    padding: 14px 16px;
    margin-bottom: 10px;
    transition: border-color 0.2s, box-shadow 0.2s;
    position: relative;
}

.address-card:focus-within {
    border-color: #22c55e;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.08);
}

.address-card .address-label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.address-card .address-label .dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.address-card .address-label .dot.pickup { background: #22c55e; }
.address-card .address-label .dot.destination { background: #ef4444; }
.address-card .address-label .dot.stop { background: #f59e0b; }

.address-card .address-label span {
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--s-text-2);
}

.address-card .s-input {
    border: none;
    background: transparent;
    padding: 0;
    font-size: 14px;
    font-weight: 500;
    box-shadow: none;
}

.address-card .s-input:focus {
    box-shadow: none;
}

/* ── Connector line between addresses ── */
.address-connector {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 20px;
    position: relative;
    margin: -4px 0;
}

.address-connector::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: repeating-linear-gradient(
        to bottom,
        var(--s-border) 0px,
        var(--s-border) 4px,
        transparent 4px,
        transparent 8px
    );
}

/* ── Multi-stop controls ── */
.add-stop-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 14px;
    border: 1.5px dashed var(--s-border);
    border-radius: 10px;
    cursor: pointer;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--s-text-2);
    transition: all 0.2s;
    background: transparent;
    width: 100%;
}

.add-stop-btn:hover {
    border-color: #22c55e;
    color: #22c55e;
    background: rgba(34, 197, 94, 0.03);
}

.remove-stop-btn {
    position: absolute;
    top: 14px;
    right: 12px;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    border: none;
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.remove-stop-btn:hover {
    background: rgba(239, 68, 68, 0.2);
}

/* ── Evidence of Delivery Toggle ── */
.evidence-toggle {
    display: flex;
    gap: 6px;
    padding: 3px;
    background: var(--s-card-bg, #f1f5f9);
    border-radius: 12px;
    border: 1.5px solid var(--s-border);
}

.evidence-option {
    flex: 1;
    padding: 10px 8px;
    border-radius: 10px;
    border: none;
    background: transparent;
    cursor: pointer;
    text-align: center;
    transition: all 0.2s;
    font-size: 12px;
    font-weight: 600;
    color: var(--s-text-2);
}

.evidence-option:hover {
    color: var(--s-text);
}

.evidence-option.active {
    background: var(--s-card-bg, #fff);
    color: var(--s-text);
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}

.evidence-option i {
    display: block;
    font-size: 18px;
    margin-bottom: 3px;
}

/* ── Quick Info Chips ── */
.info-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 8px;
    font-size: 11.5px;
    font-weight: 600;
    border: 1px solid;
}

.info-chip.green { background: rgba(34,197,94,0.06); border-color: rgba(34,197,94,0.2); color: #16a34a; }
.info-chip.amber { background: rgba(245,158,11,0.06); border-color: rgba(245,158,11,0.2); color: #d97706; }
.info-chip.red   { background: rgba(239,68,68,0.06); border-color: rgba(239,68,68,0.2); color: #dc2626; }
.info-chip.blue  { background: rgba(59,130,246,0.06); border-color: rgba(59,130,246,0.2); color: #2563eb; }
.info-chip.purple{ background: rgba(139,92,246,0.06); border-color: rgba(139,92,246,0.2); color: #7c3aed; }

/* ── Summary Panel (Right Sidebar) ── */
.summary-card {
    background: var(--s-card-bg, #fff);
    border: 1.5px solid var(--s-border);
    border-radius: 16px;
    overflow: hidden;
    position: sticky;
    top: 24px;
}

.summary-header {
    padding: 18px 20px;
    background: linear-gradient(135deg, #111827, #1f2937);
    color: #fff;
}

.summary-header h3 {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
}

.summary-body {
    padding: 16px 20px;
}

.summary-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    font-size: 13px;
    color: var(--s-text-2);
}

.summary-line strong {
    color: var(--s-text);
}

.summary-line.total {
    border-top: 2px solid var(--s-border);
    margin-top: 8px;
    padding-top: 12px;
    font-size: 15px;
    font-weight: 800;
}

.summary-line.total .total-price {
    font-size: 20px;
    font-weight: 900;
    color: #22c55e;
}

.summary-empty {
    padding: 20px;
    text-align: center;
    color: var(--s-text-3);
    font-size: 12.5px;
}

.summary-route-preview {
    padding: 10px 14px;
    background: var(--s-card-bg, #f8fafc);
    border-radius: 10px;
    margin: 10px 0;
    font-size: 12px;
    color: var(--s-text-2);
    display: flex;
    align-items: center;
    gap: 8px;
}

.summary-route-preview i {
    font-size: 16px;
    color: #22c55e;
}

/* ── COD Amount Field ── */
.cod-amount-field {
    display: none;
    margin-top: 10px;
    padding: 12px 14px;
    background: rgba(245,158,11,0.04);
    border: 1px solid rgba(245,158,11,0.2);
    border-radius: 12px;
    animation: slideDown 0.25s ease;
}

.cod-amount-field.visible {
    display: block;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ── Special Handling Chips ── */
.special-handling-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
}

.handling-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 7px 12px;
    border: 1.5px solid var(--s-border);
    border-radius: 20px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    color: var(--s-text-2);
    transition: all 0.2s;
    background: transparent;
    user-select: none;
}

.handling-chip:hover {
    border-color: #94a3b8;
}

.handling-chip.active {
    border-color: #f59e0b;
    background: rgba(245,158,11,0.06);
    color: #d97706;
}

.handling-chip input[type="checkbox"] {
    display: none;
}

/* ── Express Toggle (New Design) ── */
.express-toggle-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-radius: 14px;
    border: 1.5px solid var(--s-border);
    background: var(--s-card-bg, #fff);
    cursor: pointer;
    transition: all 0.25s;
}

.express-toggle-card.active {
    border-color: #f59e0b;
    background: linear-gradient(135deg, rgba(251,191,36,0.06), rgba(245,158,11,0.02));
    box-shadow: 0 0 0 3px rgba(245,158,11,0.08);
}

.express-toggle-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.express-toggle-left .express-icon {
    font-size: 24px;
}

.express-toggle-left .express-label {
    font-size: 14px;
    font-weight: 700;
    color: var(--s-text);
}

.express-toggle-left .express-sublabel {
    font-size: 11.5px;
    color: var(--s-text-3);
}

.express-toggle-right {
    font-size: 13px;
    font-weight: 800;
    color: #f59e0b;
    white-space: nowrap;
}

/* ── Payer Type Cards ── */
.payer-type-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.payer-type-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 14px 12px;
    border: 2px solid var(--s-border);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}

.payer-type-card:hover {
    border-color: #94a3b8;
}

.payer-type-card.active {
    border-color: #22c55e;
    background: rgba(34, 197, 94, 0.03);
}

.payer-type-card .payer-icon {
    font-size: 24px;
}

.payer-type-card .payer-label {
    font-size: 13px;
    font-weight: 700;
    color: var(--s-text);
}

.payer-type-card .payer-desc {
    font-size: 11px;
    color: var(--s-text-3);
}

/* ── Payment Method Cards ── */
.payment-method-grid {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.pm-card {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border: 2px solid var(--s-border);
    border-radius: 10px;
    cursor: pointer;
    font-size: 12.5px;
    font-weight: 600;
    transition: all 0.2s;
    color: var(--s-text-2);
}

.pm-card:hover { border-color: #94a3b8; }

.pm-card.active {
    border-color: #22c55e;
    background: rgba(34,197,94,0.04);
    color: var(--s-text);
}

.pm-card i { font-size: 16px; }

/* ── Overlay/Modal ── */
.courier-overlay {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(8px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.4s ease;
    color: #fff;
    font-family: 'Inter', sans-serif;
}

.courier-overlay.active { opacity: 1; pointer-events: all; }

.overlay-content {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 24px;
    padding: 40px;
    max-width: 480px;
    width: 90%;
    text-align: center;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
    position: relative;
    overflow: hidden;
    transition: max-width 0.5s ease, width 0.5s ease;
    max-height: 90vh;
}

.overlay-content.map-visible {
    max-width: 920px;
    padding: 28px 32px;
    text-align: left;
    overflow-y: auto;
}

/* Tracking 2-column grid */
.tracking-grid {
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 24px;
    margin-top: 20px;
    align-items: start;
}

.tracking-info {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.tracking-info .driver-card {
    margin-top: 0;
}

.tracking-map-wrap {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.tracking-map-wrap #tracking-map {
    height: 380px;
    border-radius: 16px;
    margin-top: 0;
}

@media (max-width: 768px) {
    .overlay-content.map-visible { max-width: 95vw; padding: 20px; }
    .tracking-grid { grid-template-columns: 1fr; }
    .tracking-map-wrap #tracking-map { height: 280px; }
}

/* Radar */
.radar-container {
    position: relative;
    width: 160px; height: 160px;
    margin: 0 auto 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.radar-circle {
    position: absolute;
    width: 100%; height: 100%;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(34,197,94,0.15) 0%, rgba(34,197,94,0) 70%);
    border: 1px solid rgba(34,197,94,0.3);
    animation: radar-pulse 3s infinite linear;
}

.radar-circle:nth-child(2) { animation-delay: 1s; }
.radar-circle:nth-child(3) { animation-delay: 2s; }

.radar-scanner {
    position: absolute;
    width: 100%; height: 100%;
    border-radius: 50%;
    border: 1.5px solid rgba(34,197,94,0.4);
}

.radar-scanner::after {
    content: '';
    position: absolute;
    top: 50%; left: 50%;
    width: 50%; height: 50%;
    background: linear-gradient(45deg, rgba(34,197,94,0.4) 0%, rgba(34,197,94,0) 100%);
    transform-origin: top left;
    animation: radar-spin 2s infinite linear;
    border-radius: 0 100% 0 0;
}

.radar-icon {
    font-size: 40px;
    color: #22c55e;
    z-index: 10;
    animation: icon-bounce 2s infinite ease-in-out;
}

@keyframes radar-pulse {
    0% { transform: scale(0.4); opacity: 1; }
    100% { transform: scale(1.2); opacity: 0; }
}

@keyframes radar-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes icon-bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

/* Driver card */
.driver-card {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px;
    padding: 24px;
    margin-top: 20px;
    display: none;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    transform: scale(0.9);
    opacity: 0;
    transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.driver-card.show { display: flex; transform: scale(1); opacity: 1; }

.driver-avatar {
    width: 80px; height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #22c55e;
    background: #334155;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    font-weight: bold;
    color: #22c55e;
    margin: 0 auto;
}

.driver-name { font-size: 18px; font-weight: 700; margin: 0; color: #fff; }

.driver-phone {
    font-size: 14px;
    color: #94a3b8;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.08);
    padding: 8px 16px;
    border-radius: 20px;
    transition: background 0.3s;
}

.driver-phone:hover { background: rgba(255,255,255,0.15); color: #fff; }

#tracking-map {
    width: 100%;
    height: 380px;
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,0.1);
}

/* Status badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    background: rgba(34,197,94,0.15);
    color: #22c55e;
    border: 1px solid rgba(34,197,94,0.3);
}

.status-badge.waiting { background: rgba(251,191,36,0.15); color: #fbbf24; border-color: rgba(251,191,36,0.3); }
.status-badge.pickup { background: rgba(96,165,250,0.15); color: #60a5fa; border-color: rgba(96,165,250,0.3); }
.status-badge.delivery { background: rgba(168,85,247,0.15); color: #a855f7; border-color: rgba(168,85,247,0.3); }
.status-badge.done { background: rgba(34,197,94,0.15); color: #22c55e; border-color: rgba(34,197,94,0.3); }

.status-badge .dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
.status-badge .dot.green { background: #22c55e; }
.status-badge .dot.yellow { background: #fbbf24; }
.status-badge .dot.blue { background: #60a5fa; }
.status-badge .dot.purple { background: #a855f7; }

.map-info-window {
    background: #1e293b;
    color: #fff;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

#route-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 12px;
    padding: 10px 16px;
    background: rgba(255,255,255,0.05);
    border-radius: 12px;
    font-size: 13px;
    color: rgba(255,255,255,0.8);
}

#route-info strong { color: #22c55e; }

/* ── Responsive ── */
@media (max-width: 768px) {
    .shipment-type-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .payer-type-grid {
        grid-template-columns: 1fr;
    }
}
.map-selector-overlay {
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(8px);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
}
.map-selector-overlay.active {
    opacity: 1;
    pointer-events: all;
}
.map-selector-content {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 20px;
    padding: 24px;
    max-width: 600px;
    width: 90%;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
}

/* ── Multi-Step Wizard Styling ── */
.wizard-progress-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    background: var(--s-card-bg, #fff);
    border-bottom: 1.5px solid var(--s-border);
    position: relative;
}

.wizard-step-item {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    cursor: pointer;
    opacity: 0.55;
    transition: all 0.3s ease;
    user-select: none;
}

.wizard-step-item.active {
    opacity: 1;
}

.wizard-step-item.completed .wizard-step-badge {
    background: #22c55e;
    color: #fff;
}

.wizard-step-badge {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(148, 163, 184, 0.15);
    color: var(--s-text-2);
    font-weight: 800;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.wizard-step-item.active .wizard-step-badge {
    background: #22c55e;
    color: #fff;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.2);
}

.wizard-step-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--s-text);
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.wizard-step-subtitle {
    font-size: 10.5px;
    color: var(--s-text-3);
    font-weight: 500;
}

.wizard-step-line {
    height: 2px;
    background: var(--s-border);
    flex: 0 0 30px;
    margin: 0 8px;
    border-radius: 2px;
}

.wizard-step-content {
    display: none;
    animation: fadeInStep 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

.wizard-step-content.active {
    display: block;
}

@keyframes fadeInStep {
    from { opacity: 0; transform: translateX(14px); }
    to { opacity: 1; transform: translateX(0); }
}

/* ── Touch-Friendly Inputs ── */
.touch-input-lg {
    height: 52px !important;
    font-size: 15px !important;
    border-radius: 12px !important;
    padding: 12px 16px !important;
    font-weight: 600 !important;
    border: 1.5px solid var(--s-border) !important;
}

.touch-input-lg:focus {
    border-color: #22c55e !important;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.12) !important;
}

.wizard-nav-btns {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1.5px solid var(--s-border);
}

.pac-container {
    z-index: 9999999 !important;
}

.simple-summary-column > .s-card { display:none !important; }
@media (max-width: 767px) {
    #shipment-type-grid { grid-template-columns:repeat(2,1fr) !important; }
    #request-form details > div { grid-template-columns:1fr !important; }
}
</style>
@endpush


@section('seller-content')
<div class="s-content">
    
    <!-- Top Access Info Banner -->
    <div style="display:none;align-items:center;justify-content:space-between;background:linear-gradient(135deg,rgba(34,197,94,0.08),rgba(16,185,129,0.03));border:1.5px solid rgba(34,197,94,0.25);border-radius:14px;padding:12px 18px;margin-bottom:20px;box-shadow:0 2px 8px rgba(34,197,94,0.05);">
        <div style="display:flex;align-items:center;gap:12px;">
            <span style="font-size:22px;background:rgba(34,197,94,0.15);width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;">⚡</span>
            <div>
                <strong style="font-size:13.5px;color:#15803d;display:block;margin-bottom:2px;">Módulo Delivery Libre & Sin Restricciones</strong>
                <span style="font-size:11.5px;color:var(--s-text-2);">Solicita envíos express y conecta con repartidores al instante sin requerir plan suscrito.</span>
            </div>
        </div>
        <span class="info-chip green" style="font-size:11.5px;padding:6px 12px;font-weight:700;"><i class="las la-check-circle"></i> Habilitado</span>
    </div>

    <div class="s-grid-2" style="grid-template-columns: 1.2fr 0.8fr; gap: 24px; align-items: start;">

        <!-- ═══ LEFT: FORM (WIZARD INTERACTIVO TÁCTIL EN 3 PASOS) ═══ -->
        <div class="s-card" style="padding:0;overflow:hidden;border-radius:18px;box-shadow:0 10px 25px rgba(0,0,0,0.05);">
            
            <!-- Cabecera simple -->
            <div class="wizard-progress-bar" style="display:block;padding:22px 24px;">
                <div style="display:flex;align-items:center;gap:13px;">
                    <span style="width:46px;height:46px;border-radius:14px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;display:grid;place-items:center;font-size:25px;box-shadow:0 7px 18px rgba(34,197,94,.25);"><i class="las la-motorcycle"></i></span>
                    <div>
                        <h3 style="margin:0;font-size:20px;font-weight:900;color:var(--s-text);">Solicitar un delivery</h3>
                        <p style="margin:3px 0 0;color:var(--s-text-3);font-size:12.5px;">Completa la ruta, calcula el precio y solicita un repartidor.</p>
                    </div>
                </div>
            </div>
            <div style="display:none">
            <div class="wizard-progress-bar">
                <div class="wizard-step-item active" id="wizard-step-indicator-1" onclick="goToStep(1)">
                    <div class="wizard-step-badge">1</div>
                    <div class="wizard-step-title">
                        <span>📦 Envío</span>
                        <span class="wizard-step-subtitle">Tipo e ítem</span>
                    </div>
                </div>
                <div class="wizard-step-line"></div>
                <div class="wizard-step-item" id="wizard-step-indicator-2" onclick="goToStep(2)">
                    <div class="wizard-step-badge">2</div>
                    <div class="wizard-step-title">
                        <span>🗺️ Ubicación</span>
                        <span class="wizard-step-subtitle">Recojo/Destino</span>
                    </div>
                </div>
                <div class="wizard-step-line"></div>
                <div class="wizard-step-item" id="wizard-step-indicator-3" onclick="goToStep(3)">
                    <div class="wizard-step-badge">3</div>
                    <div class="wizard-step-title">
                        <span>💵 Pago</span>
                        <span class="wizard-step-subtitle">Detalles y cobro</span>
                    </div>
                </div>
            </div>
            </div>

            @if(!$store || !$store->latitude || !$store->longitude)
            <div style="padding:16px 24px 0;">
                <div class="s-alert s-alert-error" style="margin:0;">
                    <i class="las la-exclamation-circle"></i>
                    <span>Faltan coordenadas geográficas de tu establecimiento. Ve a configuración del negocio.</span>
                </div>
            </div>
            @endif

            <form id="request-form" method="POST" action="{{ route('seller.delivery.request.submit') }}">
                @csrf
                <input type="hidden" name="driver_id" value="all">

                <div style="padding:24px;display:flex;flex-direction:column;gap:22px;">
                    <div class="form-section-header" style="margin-top:0;">
                        <div class="section-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6;"><i class="las la-box" style="font-size:20px;"></i></div>
                        <div>
                            <h4 style="font-size:15px;margin:0;">¿Qué envías?</h4>
                            <span style="font-size:11.5px;color:var(--s-text-3);">Selecciona una opción</span>
                        </div>
                    </div>

                    <div class="shipment-type-grid" id="shipment-type-grid" style="grid-template-columns:repeat(4,1fr);gap:12px;">
                        <div class="shipment-type-card active" data-type="document" onclick="selectShipmentType('document', this)">
                            <div class="shipment-type-icon">📄</div>
                            <div class="shipment-type-label">Documento</div>
                        </div>
                        <div class="shipment-type-card" data-type="food" onclick="selectShipmentType('food', this)">
                            <div class="shipment-type-icon">🍽️</div>
                            <div class="shipment-type-label">Comida</div>
                        </div>
                        <div class="shipment-type-card" data-type="package" onclick="selectShipmentType('package', this)">
                            <div class="shipment-type-icon">📦</div>
                            <div class="shipment-type-label">Paquete</div>
                        </div>
                        <div class="shipment-type-card" data-type="other" onclick="selectShipmentType('other', this)">
                            <div class="shipment-type-icon">🏷️</div>
                            <div class="shipment-type-label">Otro</div>
                        </div>
                    </div>
                    <input type="hidden" name="shipment_type" id="shipment-type-hidden" value="document">

                    <div class="address-card" style="border-radius:16px;padding:16px;">
                        <div class="address-label">
                            <div class="dot pickup"></div>
                            <span style="font-size:13px;font-weight:800;">Recojo</span>
                        </div>
                        <input type="text" class="s-input touch-input-lg" name="pickup_address" id="pickup-address"
                            value="{{ $store->name ?? 'Mi Tienda' }} - {{ $store->address ?? '' }}"
                            placeholder="Dirección de recojo" autocomplete="off">
                        <input type="hidden" name="pickup_lat" id="pickup-lat" value="{{ $store->latitude }}">
                        <input type="hidden" name="pickup_lng" id="pickup-lng" value="{{ $store->longitude }}">
                    </div>

                    <div id="destinations-container">
                        <div class="address-card" data-stop-index="0" style="border-radius:16px;padding:16px;border-color:#22c55e;">
                            <div class="address-label" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div class="dot destination"></div>
                                    <span style="font-size:13px;font-weight:800;color:#ef4444;">Destino</span>
                                </div>
                                <button type="button" class="s-btn s-btn-sm" id="select-dest-map-btn" style="padding: 6px 14px; font-size: 12px; height: auto; background: rgba(34, 197, 94, 0.12); border: 1.5px solid rgba(34, 197, 94, 0.4); color: #16a34a; border-radius: 10px; font-weight:800;">
                                    <i class="las la-map-marker" style="font-size:16px;"></i> Ver mapa
                                </button>
                            </div>
                            <input type="text" class="s-input dest-address-input touch-input-lg" name="delivery_address" id="dest-address"
                                placeholder="Escribe la calle, avenida, distrito o referencia..." required autocomplete="off">
                            <input type="hidden" name="delivery_lat" id="delivery-lat">
                            <input type="hidden" name="delivery_lng" id="delivery-lng">
                        </div>
                    </div>
                    <div id="intermediate-stops-container" style="display:none"></div>
                    <div id="short-distance-warning" style="display:none;margin-top:16px;padding:14px 16px;background:rgba(245,158,11,0.08);border:1.5px solid rgba(245,158,11,0.35);border-radius:14px;color:#d97706;animation:fadeInStep 0.3s ease;">
                        <div style="font-weight:800;font-size:13.5px;margin-bottom:3px;display:flex;align-items:center;gap:6px;">
                            <span style="font-size:18px;">⚠️</span> Distancia súper corta (<span id="short-dist-km">--</span> km)
                        </div>
                        <span style="font-size:12px;line-height:1.4;display:block;">¿Es correcta la dirección? Se aplicó automáticamente la <strong>tarifa plana reducida de S/ 4.00</strong>.</span>
                    </div>


                    <div class="s-input-group">
                        <label class="s-input-label" style="font-weight:800;font-size:13px;">Descripción</label>
                        <textarea class="s-input touch-input-lg" name="description" rows="3" placeholder="¿Qué debe recoger y cómo debe entregarlo?" style="height:92px!important;resize:none;" required></textarea>
                    </div>

                    <details style="border:1.5px solid var(--s-border);border-radius:14px;padding:0 15px;background:var(--s-bg);">
                        <summary style="cursor:pointer;list-style:none;padding:14px 0;font-weight:800;font-size:13px;color:var(--s-text-2);"><i class="las la-user-plus" style="font-size:18px;color:#8b5cf6;"></i> Agregar contacto del destinatario <span style="font-weight:500;color:var(--s-text-3);">(opcional)</span></summary>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;padding-bottom:15px;">
                            <input type="text" class="s-input touch-input-lg" name="recipient_name" placeholder="Nombre">
                            <input type="tel" class="s-input touch-input-lg" name="recipient_phone" placeholder="Celular">
                        </div>
                    </details>

                    <div id="package-details-section" style="display:none"></div>
                    <input type="hidden" name="payer_type" id="payer-type-hidden" value="sender">
                    <input type="hidden" name="payment_method" id="payment-method-hidden" value="cash">
                    <input type="hidden" name="evidence_type" id="evidence-type-hidden" value="photo">
                    <input type="hidden" name="is_express" id="is-express-hidden" value="0">
                    <input type="hidden" name="scheduled_at" value="">
                    <select name="time_slot" style="display:none"><option value="" selected></option></select>

                    <button type="button" class="s-btn" onclick="calculateFee()" style="width:100%;min-height:54px;justify-content:center;border-radius:14px;border:1.5px solid #22c55e;background:#fff;color:#16a34a;font-size:15px;font-weight:900;">
                        <i class="las la-calculator" style="font-size:21px;"></i> Calcular envío
                    </button>
                    <button type="submit" class="s-btn s-btn-primary s-btn-lg" style="width:100%;min-height:60px;justify-content:center;border-radius:15px;font-size:17px;font-weight:900;background:linear-gradient(135deg,#22c55e,#16a34a);box-shadow:0 8px 22px rgba(34,197,94,.3);" id="submit-btn" {{ (!$store || !$store->latitude) ? 'disabled' : '' }}>
                        <i class="las la-paper-plane" style="font-size:22px;"></i> Enviar solicitud · S/ 4.00
                    </button>
                </div>
            </form>
        </div>

        <!-- ═══ RIGHT: SUMMARY & MAP ═══ -->
        <div class="simple-summary-column" style="display:flex;flex-direction:column;gap:20px;">

            <!-- Dynamic Summary -->
            <div class="summary-card">
                <div class="summary-header">
                    <h3><i class="las la-receipt"></i> Resumen del Envío</h3>
                </div>
                <div class="summary-body">
                    <!-- Route preview -->
                    <div id="summary-route-empty" class="summary-empty">
                        <i class="las la-map" style="font-size:32px;opacity:0.3;display:block;margin-bottom:8px;"></i>
                        Selecciona un destino para ver el resumen
                    </div>

                    <div id="summary-route-details" style="display:none;">
                        <!-- Short Distance Warning Alert -->
                        <div id="short-distance-warning" style="display:none;margin-bottom:12px;padding:12px;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.3);border-radius:12px;font-size:12px;color:#d97706;animation:slideDown 0.3s ease;">
                            <div style="display:flex;align-items:center;gap:8px;font-weight:700;margin-bottom:4px;">
                                <i class="las la-exclamation-triangle" style="font-size:16px;color:#f59e0b;"></i> Distancia muy corta (<span id="short-dist-km">--</span> km)
                            </div>
                            <span>¿Es correcta la dirección? Se aplicó automáticamente la <strong>tarifa corta plana de S/ 4.00</strong>.</span>
                        </div>

                        <div class="summary-route-preview">
                            <i class="las la-route"></i>
                            <span id="summary-route-text">--</span>
                        </div>

                        <div class="summary-line">
                            <span id="summary-shipment-type">📄 Documento</span>
                            <span class="info-chip green" id="summary-shipment-badge">Inmediato</span>
                        </div>

                        <div class="summary-line" id="summary-express-line" style="display:none;">
                            <span><i class="las la-bolt"></i> Express</span>
                            <span class="info-chip amber">+50%</span>
                        </div>

                        <div class="summary-line" id="summary-fragile-line" style="display:none;">
                            <span>⚠️ Frágil</span>
                            <span class="info-chip red">Cuidado</span>
                        </div>

                        <div class="summary-line" id="summary-evidence-line">
                            <span>Evidencia</span>
                            <span id="summary-evidence-badge" class="info-chip blue">Foto</span>
                        </div>

                        <div class="summary-line" id="summary-payment-line">
                            <span>Pago</span>
                            <span id="summary-payment-badge" class="info-chip green">Mi tienda</span>
                        </div>

                        <div class="summary-line" id="summary-schedule-line" style="display:none;">
                            <span><i class="las la-clock"></i> Programado</span>
                            <span id="summary-schedule-badge" class="info-chip purple">--</span>
                        </div>

                        <table style="width:100%;font-size:13px;margin-top:12px;">
                            <tr>
                                <td style="padding:6px 0;color:var(--s-text-2);">Distancia</td>
                                <td style="text-align:right;padding:6px 0;"><strong id="fee-distance">--</strong> km</td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;color:var(--s-text-2);">Tarifa base</td>
                                <td style="text-align:right;padding:6px 0;">S/ <span id="fee-base">--</span></td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;color:var(--s-text-2);">Distancia extra</td>
                                <td style="text-align:right;padding:6px 0;">S/ <span id="fee-distance-fee">--</span></td>
                            </tr>
                            <tr>
                                <td style="padding:6px 0;color:var(--s-text-2);">Tiempo estimado</td>
                                <td style="text-align:right;padding:6px 0;"><span id="fee-time-min">--</span> min (S/ <span id="fee-time">--</span>)</td>
                            </tr>
                            <tr id="surge-row" style="display:none;">
                                <td style="padding:6px 0;color:#f59e0b;">Demanda (surge)</td>
                                <td style="text-align:right;padding:6px 0;color:#f59e0b;">×<span id="fee-surge">--</span></td>
                            </tr>
                            <tr id="express-fee-row" style="display:none;">
                                <td style="padding:6px 0;color:#f59e0b;">⚡ Express (+50%)</td>
                                <td style="text-align:right;padding:6px 0;color:#f59e0b;">S/ <span id="fee-express-extra">--</span></td>
                            </tr>
                            <tr class="total">
                                <td style="padding:12px 0;font-weight:800;">TOTAL ESTIMADO</td>
                                <td style="text-align:right;padding:12px 0;"><span class="total-price">S/ <span id="fee-total">--</span></span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Active Drivers Info -->
            <div class="s-card">
                <div style="display:flex;justify-content:space-between;font-size:12.5px;padding-bottom:8px;border-bottom:1px solid var(--s-border);">
                    <span>Repartidores activos</span>
                    <span class="s-badge s-badge-green" id="active-drivers">{{ $activeDrivers }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:12.5px;padding:8px 0;border-bottom:1px solid var(--s-border);">
                    <span>Tarifa mínima</span>
                    <strong>S/ {{ number_format(gs('delivery_min_fee') ?? 4, 2) }}</strong>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:12.5px;padding:8px 0;">
                    <span>Km base incluidos</span>
                    <span>{{ gs('delivery_base_km') ?? 3 }} km</span>
                </div>
            </div>

            <!-- Transparency / Fee Breakdown Info Card -->
            <div class="s-card" style="background:var(--s-card-bg,#fff);border:1.5px solid var(--s-border);">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid var(--s-border);">
                    <h4 style="margin:0;font-size:13.5px;font-weight:800;color:var(--s-text);display:flex;align-items:center;gap:6px;">
                        <i class="las la-info-circle" style="color:#3b82f6;font-size:18px;"></i> ¿Cómo funcionan tus tarifas?
                    </h4>
                    <span class="info-chip blue" style="font-size:10px;padding:2px 8px;">Transparencia</span>
                </div>
                <div style="font-size:12px;color:var(--s-text-2);display:flex;flex-direction:column;gap:8px;line-height:1.4;">
                    <div style="display:flex;gap:8px;align-items:start;">
                        <span style="font-size:14px;">⚡</span>
                        <div><strong style="color:var(--s-text)">Menos de 1 km:</strong> Tarifa corta fija reducida de <strong>S/ 4.00</strong>.</div>
                    </div>
                    <div style="display:flex;gap:8px;align-items:start;">
                        <span style="font-size:14px;">📍</span>
                        <div><strong style="color:var(--s-text)">Standard (1 a {{ (int) (gs('delivery_base_km') ?? 3) }} km):</strong> <strong>S/ {{ number_format(gs('delivery_min_fee') ?? 4, 2) }}</strong> base (incluye hasta {{ (int) (gs('delivery_base_km') ?? 3) }} km).</div>
                    </div>
                    <div style="display:flex;gap:8px;align-items:start;">
                        <span style="font-size:14px;">🛣️</span>
                        <div><strong style="color:var(--s-text)">Km extra (> {{ (int) (gs('delivery_base_km') ?? 3) }} km):</strong> <strong>+S/ {{ number_format(gs('delivery_fee_per_km') ?? 1.5, 2) }}</strong> por cada km adicional.</div>
                    </div>
                    <div style="display:flex;gap:8px;align-items:start;">
                        <span style="font-size:14px;">⏱️</span>
                        <div><strong style="color:var(--s-text)">Factor Tiempo:</strong> <strong>+S/ {{ number_format(gs('delivery_time_rate') ?? 0, 2) }}/min</strong> por tiempo en ruta.</div>
                    </div>
                    <div style="display:flex;gap:8px;align-items:start;">
                        <span style="font-size:14px;">⚡</span>
                        <div><strong style="color:var(--s-text)">Envío Express (+50%):</strong> Asignación prioritaria directa del repartidor más cercano.</div>
                    </div>
                </div>
            </div>

            <!-- Upgrade Plan Banner -->
            @if(!$store || !$store->is_premium)
            @php
                $storePackages = $store ? $store->storePackages->filter(fn($sp) => $sp->isActive()) : collect();
                $hasPaidPlan = $storePackages->first(fn($sp) => $sp->package && in_array($sp->package->type, ['basic', 'featured', 'premium']));
            @endphp
            @if(!$hasPaidPlan)
            <div class="s-card" style="background:linear-gradient(135deg, #111827, #1f2937);border:1px solid rgba(34,197,94,.2);color:#fff">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <span style="font-size:24px;">⭐</span>
                    <h3 class="s-card-title" style="margin:0;color:#fff;font-weight:800;">Planes Lizto</h3>
                </div>
                <p style="color:rgba(255,255,255,.7);font-size:13px;line-height:1.5;margin-bottom:18px;">
                    Desbloquea herramientas avanzadas: POS, Facturación SUNAT, Inventario y Reportes.
                </p>
                <a href="{{ route('seller.pricing') }}" class="s-btn s-btn-primary s-btn-lg" style="width:100%;justify-content:center;font-weight:700;">
                    <i class="las la-crown"></i> Ver Planes y Suscribirme
                </a>
            </div>
            @endif
            @endif
        </div>
    </div>

    <!-- ═══ TRACKING OVERLAY ═══ -->
    <div id="courier-overlay" class="courier-overlay">
        <div class="overlay-content" id="overlay-content">
            <!-- Radar Animation (searching) -->
            <div id="radar-search-wrapper">
                <div class="radar-container">
                    <div class="radar-circle"></div>
                    <div class="radar-circle"></div>
                    <div class="radar-circle"></div>
                    <div class="radar-scanner"></div>
                    <i class="las la-motorcycle radar-icon"></i>
                </div>
                <h3 style="margin-top:0;font-weight:800;font-size:22px;color:#fff;">Buscando Repartidor...</h3>
                <p style="color:var(--s-text-3);font-size:14px;margin-bottom:20px;line-height:1.5;">Notificando a los repartidores disponibles más cercanos.</p>
                <div class="s-alert s-alert-info" style="margin-bottom:20px;justify-content:center;background:rgba(34,197,94,0.1);border-color:rgba(34,197,94,0.2);display:inline-flex;padding:8px 16px;border-radius:12px;gap:8px;align-items:center;">
                    <i class="las la-info-circle" style="color:#22c55e;font-size:16px;"></i>
                    <span style="color:#22c55e">Orden: <strong id="overlay-order-no">--</strong></span>
                </div>
                <div id="dispatch-exhausted-actions" style="display:none;max-width:470px;margin:0 auto 18px;padding:18px;border:1px solid rgba(245,158,11,.35);border-radius:16px;background:rgba(245,158,11,.08);">
                    <strong style="display:block;color:#fbbf24;font-size:16px;margin-bottom:6px;">Ningún repartidor respondió</strong>
                    <span style="display:block;color:var(--s-text-3);font-size:13px;margin-bottom:15px;">Consultamos a todos los repartidores disponibles, desde los más cercanos hasta los más alejados.</span>
                    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                        <button type="button" id="retry-dispatch-btn" class="s-btn" style="min-height:48px;padding:0 20px;border-radius:13px;background:#22c55e;color:#fff;font-weight:800;border:0;"><i class="las la-redo-alt"></i> Volver a enviar</button>
                        <button type="button" id="cancel-dispatch-btn" class="s-btn" style="min-height:48px;padding:0 20px;border-radius:13px;background:#ef4444;color:#fff;font-weight:800;border:0;"><i class="las la-times"></i> Cancelar solicitud</button>
                    </div>
                </div>
                <button type="button" id="cancel-search-btn" class="s-btn" style="min-height:44px;padding:0 18px;border-radius:12px;background:transparent;color:#f87171;border:1px solid rgba(248,113,113,.45);font-weight:800;"><i class="las la-times"></i> Cancelar búsqueda</button>
            </div>

            <!-- Driver Assigned -->
            <div id="driver-assigned-wrapper" style="display:none;">
                <div style="font-size:48px;color:#22c55e;margin-bottom:10px;">
                    <i class="las la-check-circle"></i>
                </div>
                <h3 style="margin-top:0;font-weight:800;font-size:22px;color:#fff;" id="tracking-status-title">¡Repartidor Asignado!</h3>
                <p style="color:var(--s-text-3);font-size:14px;margin-bottom:6px;" id="tracking-status-desc">El pedido ha sido aceptado por:</p>

                <div id="status-timeline" style="display:flex;justify-content:center;gap:6px;flex-wrap:wrap;margin-bottom:16px;">
                    <span class="status-badge waiting" id="sb-accepted"><span class="dot yellow"></span> Asignado</span>
                    <span class="status-badge" id="sb-on_way_to_pickup" style="opacity:0.4;"><span class="dot blue"></span> En camino al recojo</span>
                    <span class="status-badge" id="sb-at_pickup" style="opacity:0.4;"><span class="dot blue"></span> En el recojo</span>
                    <span class="status-badge" id="sb-on_way_to_delivery" style="opacity:0.4;"><span class="dot purple"></span> En camino a entrega</span>
                    <span class="status-badge" id="sb-delivered" style="opacity:0.4;"><span class="dot green"></span> Entregado</span>
                </div>

                <div class="tracking-grid">
                    <!-- LEFT: Info -->
                    <div class="tracking-info">
                        <div class="driver-card show">
                            <div id="driver-avatar-container"></div>
                            <h4 class="driver-name" id="driver-name-display">--</h4>
                            <div style="display:flex;gap:8px;align-items:center;justify-content:center;margin-bottom:6px;flex-wrap:wrap;">
                                <a href="#" class="driver-phone" id="driver-phone-display" target="_blank" rel="noopener noreferrer" style="justify-content:center;margin:0;">
                                    <i class="las la-phone"></i> <span>--</span>
                                </a>
                                <a href="#" id="driver-wa-display" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:4px;padding:5px 12px;background:#25d366;color:#fff;border-radius:10px;font-size:12px;font-weight:700;text-decoration:none;box-shadow:0 2px 8px rgba(37,211,102,0.3);">
                                    <i class="lab la-whatsapp" style="font-size:16px;"></i> WhatsApp
                                </a>
                            </div>
                            <div style="display:flex;gap:16px;width:100%;border-top:1px solid rgba(255,255,255,0.1);padding-top:12px;justify-content:center;font-size:13px;color:var(--s-text-2)">
                                <div style="text-align:center;">
                                    <span style="display:block;font-size:11px;color:var(--s-text-3);">Distancia</span>
                                    <strong id="driver-distance-display" style="color:#22c55e;">-- km</strong>
                                </div>
                                <div style="width:1px;background:rgba(255,255,255,0.1);height:30px;"></div>
                                <div style="text-align:center;">
                                    <span style="display:block;font-size:11px;color:var(--s-text-3);">Llegada Estimada</span>
                                    <strong id="driver-time-display" style="color:#22c55e;">-- min</strong>
                                </div>
                            </div>
                        </div>

                        <div id="payment-info-overlay" style="padding:10px 16px;background:rgba(255,255,255,0.05);border-radius:12px;display:none;">
                            <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(255,255,255,0.8);">
                                <i class="las la-wallet" style="font-size:16px;color:#22c55e;"></i>
                                <span id="payment-info-text">--</span>
                            </div>
                        </div>

                        <div id="pin-display" style="padding:12px 16px;background:rgba(139,92,246,0.08);border:1px solid rgba(139,92,246,0.25);border-radius:14px;display:none;margin-bottom:10px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;">
                                <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(255,255,255,0.9);">
                                    <i class="las la-key" style="font-size:20px;color:#a855f7;"></i>
                                    <span>PIN de entrega: <strong id="pin-code-display" style="font-size:20px;letter-spacing:4px;color:#a855f7;font-weight:800;">----</strong></span>
                                </div>
                            </div>
                            <a id="share-pin-wa-btn" href="#" target="_blank" class="s-btn s-btn-sm" style="margin-top:10px;width:100%;justify-content:center;background:#25d366;color:#fff;border:none;border-radius:10px;font-weight:800;font-size:12.5px;padding:8px 12px;gap:6px;display:inline-flex;align-items:center;text-decoration:none;box-shadow:0 4px 12px rgba(37,211,102,0.3);">
                                <i class="lab la-whatsapp" style="font-size:18px;"></i> Compartir PIN por WhatsApp al Cliente
                            </a>
                        </div>

                        <div id="eta-display" style="padding:10px 16px;background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.2);border-radius:12px;display:none;">
                            <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(255,255,255,0.8);">
                                <i class="las la-clock" style="font-size:16px;color:#22c55e;"></i>
                                <span>Tiempo estimado: <strong id="eta-text" style="color:#22c55e;">--</strong></span>
                            </div>
                        </div>

                        <div style="padding:10px 16px;background:rgba(255,255,255,0.05);border-radius:12px;font-size:13px;color:rgba(255,255,255,0.7);">
                            <i class="las la-route" style="color:#22c55e;"></i> Ruta: <span id="route-description">Cargando...</span>
                            <strong id="route-eta" style="float:right;color:#22c55e;"></strong>
                        </div>
                        <div id="stops-list-overlay" style="display:none;"></div>
                    </div>

                    <!-- RIGHT: Map -->
                    <div class="tracking-map-wrap">
                        <div id="tracking-map"></div>
                        <button type="button" class="s-btn s-btn-primary s-btn-lg" style="width:100%;justify-content:center;font-weight:700;" onclick="closeTrackingModal()">
                            <i class="las la-times-circle"></i> Cerrar y Volver
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ MIS ENVÍOS SOLICITADOS RECIENTES ═══ -->
    <div class="s-card mt-4" style="border-radius:18px;padding:24px;box-shadow:0 10px 25px rgba(0,0,0,0.05);margin-top:28px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:12px;border-bottom:1.5px solid var(--s-border);">
            <div>
                <h3 class="s-card-title" style="margin:0;font-size:17px;font-weight:800;display:flex;align-items:center;gap:8px;">
                    <i class="las la-list-alt" style="color:#22c55e;font-size:24px;"></i> Mis Envíos Solicitados
                </h3>
                <span style="font-size:12px;color:var(--s-text-3);">Historial y seguimiento en vivo de tus solicitudes de delivery</span>
            </div>
            <span class="info-chip blue" style="font-size:12px;padding:4px 12px;font-weight:700;">{{ count($recentFavors ?? []) }} Solicitudes</span>
        </div>

        @if(!isset($recentFavors) || $recentFavors->isEmpty())
            <div style="text-align:center;padding:40px 16px;color:var(--s-text-3);">
                <i class="las la-box-open" style="font-size:48px;opacity:0.3;display:block;margin-bottom:10px;"></i>
                <span style="font-weight:600;font-size:14px;">Aún no has realizado solicitudes de envío.</span>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="s-table" style="width:100%;font-size:13.5px;border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:2px solid var(--s-border);text-align:left;">
                            <th style="padding:10px 12px;font-weight:800;">N° Orden</th>
                            <th style="padding:10px 12px;font-weight:800;">Fecha</th>
                            <th style="padding:10px 12px;font-weight:800;">Destino</th>
                            <th style="padding:10px 12px;font-weight:800;">Destinatario</th>
                            <th style="padding:10px 12px;font-weight:800;">Monto COD / Pago</th>
                            <th style="padding:10px 12px;font-weight:800;">Estado</th>
                            <th style="padding:10px 12px;font-weight:800;text-align:right;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentFavors as $favor)
                        <tr style="border-bottom:1px solid var(--s-border);">
                            <td style="padding:12px;"><strong>{{ $favor->order_no }}</strong></td>
                            <td style="padding:12px;color:var(--s-text-2);">{{ $favor->created_at->format('d/m/Y H:i') }}</td>
                            <td style="padding:12px;color:var(--s-text-2);max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                {{ $favor->delivery_address }}
                            </td>
                            <td style="padding:12px;font-weight:600;">{{ $favor->recipient_name }}</td>
                            <td style="padding:12px;">
                                @if($favor->payer_type === 'recipient')
                                    <span style="color:#d97706;font-weight:800;">S/ {{ number_format($favor->cod_amount ?? $favor->total, 2) }} (COD)</span>
                                @else
                                    <span style="color:#16a34a;font-weight:700;">S/ {{ number_format($favor->total ?? $favor->delivery_fee, 2) }}</span>
                                @endif
                            </td>
                            <td style="padding:12px;">
                                @php
                                    $badgeClass = match($favor->status) {
                                        'accepted', 'on_way_to_pickup', 'at_pickup', 'on_way_to_delivery' => 's-badge-blue',
                                        'delivered' => 's-badge-green',
                                        'cancelled' => 's-badge-red',
                                        default => 's-badge-yellow'
                                    };
                                    $statusLabel = match($favor->status) {
                                        'searching_courier' => '⚡ Buscando repartidor',
                                        'accepted' => '✓ Aceptado',
                                        'on_way_to_pickup' => '🛵 En camino a recojo',
                                        'at_pickup' => '🏪 En recojo',
                                        'on_way_to_delivery' => '📦 En camino a entrega',
                                        'delivered' => '🎉 Entregado',
                                        'cancelled' => '✕ Cancelado',
                                        default => $favor->status
                                    };
                                @endphp
                                <span class="s-badge {{ $badgeClass }}" style="font-size:11.5px;padding:4px 10px;font-weight:700;">{{ $statusLabel }}</span>
                            </td>
                            <td style="padding:12px;text-align:right;">
                                <button type="button" class="s-btn s-btn-sm s-btn-primary" style="padding:6px 14px;font-size:12px;border-radius:10px;font-weight:800;background:linear-gradient(135deg,#3b82f6,#2563eb);" onclick="reopenTracking({{ $favor->id }}, '{{ $favor->status }}')">
                                    <i class="las la-eye" style="font-size:15px;"></i> Ver Seguimiento
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Map Selector Modal -->
    <div id="map-selector-modal" class="map-selector-overlay">
        <div class="map-selector-content">
            <h4 style="margin-top:0; color:#fff; font-weight:800; font-size:16px; margin-bottom:6px; display:flex; align-items:center; gap:8px;">
                <i class="las la-map-marked-alt" style="color:#22c55e; font-size:20px;"></i> Seleccionar Ubicación de Destino
            </h4>
            <p style="color:#94a3b8; font-size:12px; margin-bottom:12px; line-height:1.4;">Escribe la dirección en el buscador o arrastra el marcador para fijar el destino exacto.</p>
            
            <!-- Address Search Bar inside Modal -->
            <div style="margin-bottom:12px;position:relative;">
                <input type="text" id="modal-map-search-input" class="s-input touch-input-lg" placeholder="🔍 Buscar calle, avenida, tienda o referencia..." style="width:100%;background:#0f172a !important;color:#fff !important;border:1.5px solid rgba(255,255,255,0.2) !important;border-radius:12px !important;padding:12px 16px !important;font-size:14px !important;" autocomplete="off">
            </div>

            <div id="selector-map" style="width:100%; height:380px; border-radius:12px; margin-bottom:16px; border:1px solid rgba(255,255,255,0.1); background:#334155;"></div>
            
            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" class="s-btn s-btn-secondary" id="close-map-selector-btn" style="background:#334155; border:1px solid rgba(255,255,255,0.1); color:#fff; border-radius:10px; padding:10px 18px;">Cancelar</button>
                <button type="button" class="s-btn s-btn-primary" id="confirm-map-selector-btn" style="background:#22c55e; border:none; color:#fff; border-radius:10px; padding:10px 18px; font-weight:700;">Confirmar Ubicación</button>
            </div>
        </div>
    </div>
</div>
@endsection

@if(gs('google_maps_api'))
@push('script-lib')
<script src="https://maps.googleapis.com/maps/api/js?key={{ gs('google_maps_api') }}&libraries=places" defer></script>
@endpush
@endif

@push('script')
<script src="https://js.pusher.com/8.2/pusher.min.js"></script>
<script>
    var pollingInterval = null;
    var favorId = null;

    // ════════════════════════════════
    //  TRACKING MODAL HELPERS
    // ════════════════════════════════
    window.closeTrackingModal = function() {
        var courierOverlay = document.getElementById('courier-overlay');
        if (courierOverlay) courierOverlay.classList.remove('active');
        if (pollingInterval) clearInterval(pollingInterval);
    };

    window.reopenTracking = function(fvId, initialStatus) {
        favorId = fvId;
        var courierOverlay = document.getElementById('courier-overlay');
        var radarSearchWrapper = document.getElementById('radar-search-wrapper');
        var driverAssignedWrapper = document.getElementById('driver-assigned-wrapper');
        var overlayContent = document.getElementById('overlay-content');
        var overlayOrderNo = document.getElementById('overlay-order-no');

        if (courierOverlay) courierOverlay.classList.add('active');

        var st = initialStatus || 'searching_courier';
        if (st === 'completed') st = 'delivered';
        currentFavorStatus = st;

        if (['accepted', 'on_way_to_pickup', 'at_pickup', 'on_way_to_delivery', 'delivered'].includes(st)) {
            if (radarSearchWrapper) radarSearchWrapper.style.display = 'none';
            if (driverAssignedWrapper) driverAssignedWrapper.style.display = 'block';
            if (overlayContent) overlayContent.classList.add('map-visible');
            if (overlayOrderNo) overlayOrderNo.textContent = '#' + fvId;
            updateStatusBadges(st);
        } else {
            if (radarSearchWrapper) radarSearchWrapper.style.display = 'block';
            if (driverAssignedWrapper) driverAssignedWrapper.style.display = 'none';
            if (overlayContent) overlayContent.classList.remove('map-visible');
            if (overlayOrderNo) overlayOrderNo.textContent = '#' + fvId;
        }

        var url = '{{ route("seller.delivery.request.status.show", ":id") }}'.replace(':id', fvId);
        fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                var rawStatus = data.favor_status || (data.favor ? data.favor.status : null) || st;
                if (rawStatus === 'completed') rawStatus = 'delivered';
                currentFavorStatus = rawStatus;
                currentFavorData = data.favor;

                if (['accepted', 'on_way_to_pickup', 'at_pickup', 'on_way_to_delivery', 'delivered'].includes(rawStatus)) {
                    if (radarSearchWrapper) radarSearchWrapper.style.display = 'none';
                    if (driverAssignedWrapper) driverAssignedWrapper.style.display = 'block';
                    if (overlayContent) overlayContent.classList.add('map-visible');
                    showTracking(data);
                } else {
                    if (radarSearchWrapper) radarSearchWrapper.style.display = 'block';
                    if (driverAssignedWrapper) driverAssignedWrapper.style.display = 'none';
                    if (overlayContent) overlayContent.classList.remove('map-visible');
                    if (overlayOrderNo) overlayOrderNo.textContent = (data.favor ? data.favor.order_no : '#' + fvId);
                }
                if (typeof startStatusPolling === 'function' && rawStatus !== 'delivered' && rawStatus !== 'cancelled') {
                    startStatusPolling(fvId);
                }
            }
        })
        .catch(function(err) {
            if (typeof startStatusPolling === 'function') {
                startStatusPolling(fvId);
            }
        });
    };

    // ════════════════════════════════
    //  STEP WIZARD NAVIGATION
    // ════════════════════════════════
    window.currentWizardStep = 1;

    window.goToStep = function(stepNum) {
        if (stepNum < 1 || stepNum > 3) return;

        // Validation for moving to Step 3
        if (stepNum === 3) {
            var destInput = document.getElementById('dest-address');
            var destLat = document.getElementById('delivery-lat').value;
            if (!destInput || !destInput.value.trim() || !destLat) {
                alert('Por favor ingresa o selecciona en el mapa una dirección de destino válida antes de continuar al paso de pago.');
                if (destInput) destInput.focus();
                return;
            }
        }

        window.currentWizardStep = stepNum;

        // Hide all steps
        for (var i = 1; i <= 3; i++) {
            var stepEl = document.getElementById('wizard-step-' + i);
            var indicatorEl = document.getElementById('wizard-step-indicator-' + i);
            if (stepEl) {
                stepEl.classList.remove('active');
            }
            if (indicatorEl) {
                indicatorEl.classList.remove('active');
                if (i < stepNum) {
                    indicatorEl.classList.add('completed');
                } else {
                    indicatorEl.classList.remove('completed');
                }
            }
        }

        // Show active step
        var activeStepEl = document.getElementById('wizard-step-' + stepNum);
        var activeIndicatorEl = document.getElementById('wizard-step-indicator-' + stepNum);
        if (activeStepEl) activeStepEl.classList.add('active');
        if (activeIndicatorEl) activeIndicatorEl.classList.add('active');

        // Scroll smooth to top of wizard
        var wizardCard = document.querySelector('.wizard-progress-bar');
        if (wizardCard) {
            wizardCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    };

    window.nextStep = function(stepNum) {
        window.goToStep(stepNum);
    };

    window.prevStep = function(stepNum) {
        window.goToStep(stepNum);
    };

    // ════════════════════════════════
    //  STATE
    // ════════════════════════════════
    var state = {
        shipmentType: 'document',
        evidenceType: 'photo',
        payerType: 'sender',
        paymentMethod: 'cash',
        isExpress: false,
        isFragile: false,
        isHeavy: false,
        isTempControlled: false,
        pickupLat: parseFloat(document.getElementById('pickup-lat').value) || null,
        pickupLng: parseFloat(document.getElementById('pickup-lng').value) || null,
        deliveryLat: null,
        deliveryLng: null,
        stops: [],
        feeEstimate: null
    };

    var pickupAddr = document.getElementById('pickup-address');
    var destAddr = document.getElementById('dest-address');

    // ════════════════════════════════
    //  SHIPMENT TYPE SELECTOR
    // ════════════════════════════════
    window.selectShipmentType = function(type, el) {
        state.shipmentType = type;
        document.getElementById('shipment-type-hidden').value = type;

        document.querySelectorAll('.shipment-type-card').forEach(function(c) { c.classList.remove('active'); });
        el.classList.add('active');

        // Show package details for non-document types
        var showPackage = type !== 'document';
        document.getElementById('package-details-section').style.display = showPackage ? '' : 'none';

        updateSummary();
    };

    // ════════════════════════════════
    //  EVIDENCE TOGGLE
    // ════════════════════════════════
    window.selectEvidence = function(type, el) {
        state.evidenceType = type;
        document.getElementById('evidence-type-hidden').value = type;

        document.querySelectorAll('.evidence-option').forEach(function(o) { o.classList.remove('active'); });
        el.classList.add('active');
        updateSummary();
    };

    // ════════════════════════════════
    //  PAYER TYPE
    // ════════════════════════════════
    window.selectPayerType = function(type, el) {
        state.payerType = type;
        document.getElementById('payer-type-hidden').value = type;

        document.querySelectorAll('.payer-type-card').forEach(function(c) { c.classList.remove('active'); });
        el.classList.add('active');

        var pmSection = document.getElementById('payment-method-section');
        var codSection = document.getElementById('cod-amount-section');
        if (type === 'recipient') {
            if (pmSection) pmSection.style.display = 'none';
            if (codSection) {
                codSection.style.display = 'block';
                codSection.classList.add('visible');
            }
        } else {
            if (pmSection) pmSection.style.display = 'block';
            if (codSection) {
                codSection.style.display = 'none';
                codSection.classList.remove('visible');
            }
        }
        updateSummary();
    };

    // ════════════════════════════════
    //  PAYMENT METHOD
    // ════════════════════════════════
    window.selectPaymentMethod = function(method, el) {
        state.paymentMethod = method;
        document.getElementById('payment-method-hidden').value = method;

        document.querySelectorAll('.pm-card').forEach(function(c) { c.classList.remove('active'); });
        el.classList.add('active');
        updateSummary();
    };

    // ════════════════════════════════
    //  EVIDENCE TYPE
    // ════════════════════════════════
    window.selectEvidenceType = function(type, el) {
        state.evidenceType = type;
        document.getElementById('evidence-type-hidden').value = type;

        var evPhotoCard = document.getElementById('ev-photo-card');
        var evPinCard = document.getElementById('ev-pin-card');
        var evBothCard = document.getElementById('ev-both-card');

        if (evPhotoCard) evPhotoCard.classList.remove('active');
        if (evPinCard) evPinCard.classList.remove('active');
        if (evBothCard) evBothCard.classList.remove('active');

        if (el) el.classList.add('active');
        updateSummary();
    };

    // ════════════════════════════════
    //  EXPRESS TOGGLE
    // ════════════════════════════════
    window.toggleExpress = function() {
        state.isExpress = !state.isExpress;
        document.getElementById('is-express-hidden').value = state.isExpress ? '1' : '0';
        var card = document.getElementById('express-card');
        if (state.isExpress) {
            card.classList.add('active');
        } else {
            card.classList.remove('active');
        }
        // Recalculate with express multiplier
        if (state.feeEstimate) updateFeeDisplay(state.feeEstimate);
        updateSummary();
    };

    // ════════════════════════════════
    //  SPECIAL HANDLING CHIPS
    // ════════════════════════════════
    window.toggleHandling = function(chip) {
        var cb = chip.querySelector('input[type="checkbox"]');
        cb.checked = !cb.checked;
        chip.classList.toggle('active', cb.checked);

        if (cb.name === 'is_fragile') state.isFragile = cb.checked;
        if (cb.name === 'is_heavy') state.isHeavy = cb.checked;
        if (cb.name === 'is_temperature_controlled') state.isTempControlled = cb.checked;
        updateSummary();
    };

    // ════════════════════════════════
    //  MULTI-STOP
    // ════════════════════════════════
    var stopCount = 0;
    window.addStop = function() {
        if (stopCount >= 4) return;
        stopCount++;

        var container = document.getElementById('destinations-container');
        var stopDiv = document.createElement('div');
        stopDiv.className = 'address-card';
        stopDiv.style.position = 'relative';
        stopDiv.style.animation = 'slideDown 0.3s ease';
        stopDiv.dataset.stopIndex = stopCount;
        stopDiv.innerHTML = '<button type="button" class="remove-stop-btn" onclick="removeStop(this, ' + stopCount + ')">×</button>' +
            '<div class="address-label"><div class="dot stop"></div><span>Parada #' + stopCount + '</span></div>' +
            '<input type="text" class="s-input dest-address-input" name="stop_address[]" placeholder="Buscar dirección..." autocomplete="off">' +
            '<input type="hidden" name="stop_lat[]" value="">' +
            '<input type="hidden" name="stop_lng[]" value="">';

        container.appendChild(stopDiv);

        // Init autocomplete for new stop
        if (window.google && google.maps && google.maps.places) {
            var input = stopDiv.querySelector('.dest-address-input');
            var ac = new google.maps.places.Autocomplete(input, { componentRestrictions: { country: 'pe' } });
            ac.addListener('place_changed', function() {
                var place = ac.getPlace();
                if (place.geometry) {
                    stopDiv.querySelector('input[name="stop_lat[]"]').value = place.geometry.location.lat();
                    stopDiv.querySelector('input[name="stop_lng[]"]').value = place.geometry.location.lng();
                }
            });
        }

        if (stopCount >= 4) {
            document.getElementById('add-stop-btn').style.display = 'none';
        }
        updateSummary();
    };

    window.removeStop = function(btn, index) {
        btn.parentElement.remove();
        stopCount--;
        document.getElementById('add-stop-btn').style.display = '';
        updateSummary();
    };

    // ════════════════════════════════
    //  SUMMARY UPDATE
    // ════════════════════════════════
    function updateSummary() {
        if (!state.deliveryLat) return;

        document.getElementById('summary-route-empty').style.display = 'none';
        document.getElementById('summary-route-details').style.display = '';

        // Shipment type label
        var typeLabels = { document: '📄 Documento', food: '🍽️ Comida', package: '📦 Paquete', pharmacy: '💊 Farmacia', grocery: '🛒 Supermercado', other: '🏷️ Otro' };
        document.getElementById('summary-shipment-type').textContent = typeLabels[state.shipmentType] || '📄 Documento';

        // Evidence badge
        var evidenceLabels = { photo: 'Foto', pin: 'PIN', both: 'Foto + PIN' };
        document.getElementById('summary-evidence-badge').textContent = evidenceLabels[state.evidenceType] || 'Foto';

        // Payment badge
        var paymentBadge = document.getElementById('summary-payment-badge');
        if (state.payerType === 'recipient') {
            paymentBadge.textContent = 'Cobra el cliente';
            paymentBadge.className = 'info-chip amber';
        } else {
            var pmLabels = { cash: 'Efectivo', yape: 'Yape', plin: 'Plin', card: 'Tarjeta' };
            paymentBadge.textContent = pmLabels[state.paymentMethod] || 'Efectivo';
            paymentBadge.className = 'info-chip green';
        }

        // Express line
        document.getElementById('summary-express-line').style.display = state.isExpress ? '' : 'none';
        document.getElementById('summary-fragile-line').style.display = state.isFragile ? '' : 'none';

        // Schedule
        var scheduled = document.querySelector('input[name="scheduled_at"]').value;
        var timeSlot = document.querySelector('select[name="time_slot"]').value;
        var schedLine = document.getElementById('summary-schedule-line');
        if (scheduled || timeSlot) {
            schedLine.style.display = '';
            var slotLabels = { morning: 'Mañana', afternoon: 'Tarde', evening: 'Noche' };
            var schedText = scheduled ? new Date(scheduled).toLocaleString('es-PE', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';
            if (timeSlot) schedText += (schedText ? ' · ' : '') + (slotLabels[timeSlot] || '');
            document.getElementById('summary-schedule-badge').textContent = schedText || 'Inmediato';
        } else {
            schedLine.style.display = 'none';
        }
    }

    // ════════════════════════════════
    //  QUICK NOTE PRESETS
    // ════════════════════════════════
    window.applyQuickNote = function(text) {
        var ta = document.querySelector('textarea[name="description"]');
        if (ta) {
            ta.value = text;
            ta.focus();
        }
    };

    function formatFeeDisplay(val) {
        var num = parseFloat(val) || 0;
        var floor = Math.floor(num);
        var dec = Math.round((num - floor) * 100) / 100;
        if (dec >= 0.46 && dec <= 0.54) {
            return (floor + 0.50).toFixed(2);
        } else if (dec > 0.54) {
            return Math.ceil(num).toString();
        } else {
            return floor.toString();
        }
    }

    function updateFeeDisplay(data) {
        var distKm = parseFloat(data.distance_km) || 0;
        var isShort = distKm > 0 && distKm < 1.0;

        var baseDeliveryFee = (isShort || data.is_short_distance) ? 4.0 : (parseFloat(data.delivery_fee) || 0);
        var total = baseDeliveryFee;
        if (state.isExpress) total = total * 1.5;

        document.getElementById('fee-distance').textContent = data.distance_km || '--';
        document.getElementById('fee-base').textContent = (isShort || data.is_short_distance) ? '4.00 (Corta)' : (data.base_fare || '--');
        document.getElementById('fee-distance-fee').textContent = (isShort || data.is_short_distance) ? '0.00' : (data.distance_fee || '0.00');
        document.getElementById('fee-time-min').textContent = data.time_min || '--';
        document.getElementById('fee-time').textContent = (isShort || data.is_short_distance) ? '0.00' : (data.time_fee || '0.00');
        document.getElementById('fee-total').textContent = formatFeeDisplay(total);

        // Short distance warning banner
        var shortAlert = document.getElementById('short-distance-warning');
        if (shortAlert) {
            if (isShort || data.is_short_distance) {
                shortAlert.style.display = 'block';
                var distTextEl = document.getElementById('short-dist-km');
                if (distTextEl) distTextEl.textContent = distKm ? distKm.toFixed(2) : '< 1';
            } else {
                shortAlert.style.display = 'none';
            }
        }

        var submitBtn = document.getElementById('submit-btn');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="las la-paper-plane"></i> Solicitar Delivery · S/ ' + formatFeeDisplay(total);
        }

        if (parseFloat(data.surge) > 1) {
            document.getElementById('surge-row').style.display = '';
            document.getElementById('fee-surge').textContent = data.surge;
        } else {
            document.getElementById('surge-row').style.display = 'none';
        }

        if (state.isExpress) {
            document.getElementById('express-fee-row').style.display = '';
            document.getElementById('fee-express-extra').textContent = (total - baseDeliveryFee).toFixed(1);
        } else {
            document.getElementById('express-fee-row').style.display = 'none';
        }
    }

    // ════════════════════════════════
    //  GOOGLE MAPS AUTOCOMPLETE
    // ════════════════════════════════
    function initAutocomplete() {
        if (!window.google || !google.maps || !google.maps.places) {
            setTimeout(initAutocomplete, 300);
            return;
        }

        // Pickup
        var pickupAC = new google.maps.places.Autocomplete(pickupAddr, { componentRestrictions: { country: 'pe' } });
        pickupAC.addListener('place_changed', function() {
            var place = pickupAC.getPlace();
            if (place.geometry) {
                state.pickupLat = place.geometry.location.lat();
                state.pickupLng = place.geometry.location.lng();
                document.getElementById('pickup-lat').value = state.pickupLat;
                document.getElementById('pickup-lng').value = state.pickupLng;
                if (state.deliveryLat && state.deliveryLng) calculateFee();
            }
        });

        // Destination
        var destAC = new google.maps.places.Autocomplete(destAddr, { componentRestrictions: { country: 'pe' } });
        destAC.addListener('place_changed', function() {
            var place = destAC.getPlace();
            if (place.geometry) {
                state.deliveryLat = place.geometry.location.lat();
                state.deliveryLng = place.geometry.location.lng();
                document.getElementById('delivery-lat').value = state.deliveryLat;
                document.getElementById('delivery-lng').value = state.deliveryLng;
                calculateFee();
            }
        });
    }

    // ════════════════════════════════
    //  MAP SELECTOR MODAL
    // ════════════════════════════════
    var mapSelectorModal = document.getElementById('map-selector-modal');
    var selectDestMapBtn = document.getElementById('select-dest-map-btn');
    var closeMapSelectorBtn = document.getElementById('close-map-selector-btn');
    var confirmMapSelectorBtn = document.getElementById('confirm-map-selector-btn');
    
    var selectorMap = null;
    var selectorMarker = null;
    var tempLat = null;
    var tempLng = null;

    if (selectDestMapBtn) {
        selectDestMapBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (mapSelectorModal) {
                mapSelectorModal.classList.add('active');
                initSelectorMap();
            }
        });
    }

    if (closeMapSelectorBtn) {
        closeMapSelectorBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (mapSelectorModal) {
                mapSelectorModal.classList.remove('active');
            }
        });
    }

    if (confirmMapSelectorBtn) {
        confirmMapSelectorBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (tempLat && tempLng) {
                state.deliveryLat = tempLat;
                state.deliveryLng = tempLng;
                document.getElementById('delivery-lat').value = tempLat;
                document.getElementById('delivery-lng').value = tempLng;

                // Reverse geocode to get formatted address
                if (window.google && google.maps) {
                    var geocoder = new google.maps.Geocoder();
                    geocoder.geocode({ location: { lat: tempLat, lng: tempLng } }, function(results, status) {
                        if (status === 'OK' && results[0]) {
                            destAddr.value = results[0].formatted_address;
                        } else {
                            destAddr.value = tempLat.toFixed(6) + ', ' + tempLng.toFixed(6);
                        }
                        calculateFee();
                        if (mapSelectorModal) mapSelectorModal.classList.remove('active');
                    });
                } else {
                    destAddr.value = tempLat.toFixed(6) + ', ' + tempLng.toFixed(6);
                    calculateFee();
                    if (mapSelectorModal) mapSelectorModal.classList.remove('active');
                }
            } else {
                if (mapSelectorModal) mapSelectorModal.classList.remove('active');
            }
        });
    }

    function initSelectorMap() {
        if (!window.google || !google.maps) return;

        var centerLat = state.deliveryLat || state.pickupLat || -6.4916;
        var centerLng = state.deliveryLng || state.pickupLng || -76.3724;

        tempLat = centerLat;
        tempLng = centerLng;

        setTimeout(function() {
            if (!selectorMap) {
                selectorMap = new google.maps.Map(document.getElementById('selector-map'), {
                    center: { lat: centerLat, lng: centerLng },
                    zoom: 15,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false
                });

                selectorMarker = new google.maps.Marker({
                    position: { lat: centerLat, lng: centerLng },
                    map: selectorMap,
                    draggable: true,
                    animation: google.maps.Animation.DROP
                });

                selectorMap.addListener('click', function(e) {
                    var latLng = e.latLng;
                    selectorMarker.setPosition(latLng);
                    tempLat = latLng.lat();
                    tempLng = latLng.lng();
                });

                selectorMarker.addListener('dragend', function() {
                    var position = selectorMarker.getPosition();
                    tempLat = position.lat();
                    tempLng = position.lng();
                });
                // Autocomplete for search input inside modal
                var modalSearchInput = document.getElementById('modal-map-search-input');
                if (modalSearchInput && !modalSearchInput.dataset.acBound && window.google && google.maps && google.maps.places) {
                    modalSearchInput.dataset.acBound = '1';
                    var modalAc = new google.maps.places.Autocomplete(modalSearchInput, { componentRestrictions: { country: 'pe' } });
                    modalAc.addListener('place_changed', function() {
                        var place = modalAc.getPlace();
                        if (place.geometry) {
                            var loc = place.geometry.location;
                            selectorMap.setCenter(loc);
                            selectorMap.setZoom(17);
                            selectorMarker.setPosition(loc);
                            tempLat = loc.lat();
                            tempLng = loc.lng();
                        }
                    });
                }
            } else {
                selectorMap.setCenter({ lat: centerLat, lng: centerLng });
                selectorMarker.setPosition({ lat: centerLat, lng: centerLng });
                google.maps.event.trigger(selectorMap, 'resize');
            }
        }, 200);
    }

    window.addEventListener('load', initAutocomplete);

    destAddr.addEventListener('input', function() {
        state.deliveryLat = null; state.deliveryLng = null;
        document.getElementById('delivery-lat').value = '';
        document.getElementById('delivery-lng').value = '';
    });

    pickupAddr.addEventListener('input', function() {
        state.pickupLat = null; state.pickupLng = null;
        document.getElementById('pickup-lat').value = '';
        document.getElementById('pickup-lng').value = '';
    });

    var stopCounter = 0;
    window.addStop = function() {
        if (stopCounter >= 3) {
            alert('Puedes agregar un máximo de 3 paradas intermedias.');
            return;
        }

        stopCounter++;
        var container = document.getElementById('intermediate-stops-container');
        if (!container) return;

        var stopId = 'stop-item-' + stopCounter;
        var stopDiv = document.createElement('div');
        stopDiv.id = stopId;
        stopDiv.className = 'address-card mb-3';
        stopDiv.style.cssText = 'border-radius:16px;padding:16px;border:1.5px dashed #fbbf24;background:rgba(251,191,36,0.03);position:relative;margin-bottom:12px;';

        stopDiv.innerHTML = `
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div class="dot" style="background:#fbbf24;box-shadow:0 0 0 3px rgba(251,191,36,0.2);"></div>
                    <span style="font-size:12px;font-weight:800;color:#fbbf24;">Parada Intermedia #${stopCounter} (+ S/ 2.50)</span>
                </div>
                <button type="button" class="s-btn s-btn-sm" onclick="removeStop('${stopId}')" style="background:rgba(239,68,68,0.15);color:#ef4444;border:none;border-radius:8px;padding:4px 10px;font-size:11.5px;font-weight:800;">
                    <i class="las la-trash"></i> Eliminar
                </button>
            </div>
            <input type="text" class="s-input touch-input-lg stop-addr-input mb-2" name="stop_address[]" id="stop-addr-${stopCounter}" placeholder="🔍 Dirección de la Parada #${stopCounter}..." autocomplete="off">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                <input type="text" class="s-input touch-input-sm" name="stop_recipient_name[]" placeholder="Persona / Nombre (opcional)">
                <input type="text" class="s-input touch-input-sm" name="stop_recipient_phone[]" placeholder="Teléfono (opcional)">
            </div>
            <input type="hidden" name="stop_lat[]" id="stop-lat-${stopCounter}">
            <input type="hidden" name="stop_lng[]" id="stop-lng-${stopCounter}">
        `;

        container.appendChild(stopDiv);

        var inputEl = document.getElementById('stop-addr-' + stopCounter);
        if (inputEl && window.google && google.maps && google.maps.places) {
            var autocomplete = new google.maps.places.Autocomplete(inputEl, {
                componentRestrictions: { country: 'pe' },
                fields: ['formatted_address', 'geometry', 'name']
            });
            var currentIdx = stopCounter;
            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();
                if (place.geometry && place.geometry.location) {
                    document.getElementById('stop-lat-' + currentIdx).value = place.geometry.location.lat();
                    document.getElementById('stop-lng-' + currentIdx).value = place.geometry.location.lng();
                    calculateFee();
                }
            });
        }

        calculateFee();
    };

    window.removeStop = function(stopId) {
        var el = document.getElementById(stopId);
        if (el) el.remove();
        calculateFee();
    };

    // ════════════════════════════════
    //  FEE CALCULATION
    // ════════════════════════════════
    function calculateFee() {
        var pLat = state.pickupLat || document.getElementById('pickup-lat').value;
        var pLng = state.pickupLng || document.getElementById('pickup-lng').value;
        var dLat = state.deliveryLat || document.getElementById('delivery-lat').value;
        var dLng = state.deliveryLng || document.getElementById('delivery-lng').value;

        if (!pLat || !pLng || !dLat || !dLng) return;

        var payload = {
            pickup_lat: pLat, pickup_lng: pLng,
            delivery_lat: dLat, delivery_lng: dLng,
            stop_lat: [], stop_lng: []
        };

        var stopLats = document.querySelectorAll('input[name="stop_lat[]"]');
        var stopLngs = document.querySelectorAll('input[name="stop_lng[]"]');
        stopLats.forEach(function(latInput, i) {
            var lVal = latInput.value;
            var gVal = stopLngs[i] ? stopLngs[i].value : null;
            if (lVal && gVal) {
                payload.stop_lat.push(lVal);
                payload.stop_lng.push(gVal);
            }
        });

        if (document.getElementById('is-express-hidden').value == '1') {
            payload.is_express = '1';
        }

        fetch('{{ route("seller.delivery.request.fee-calculate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                state.feeEstimate = data;
                updateFeeDisplay(data);
                updateSummary();
            }
        });
    }

    // ════════════════════════════════
    //  FORM SUBMISSION
    // ════════════════════════════════
    var requestForm = document.getElementById('request-form');
    var courierOverlay = document.getElementById('courier-overlay');
    var overlayContent = document.getElementById('overlay-content');
    var overlayOrderNo = document.getElementById('overlay-order-no');
    var radarSearchWrapper = document.getElementById('radar-search-wrapper');
    var driverAssignedWrapper = document.getElementById('driver-assigned-wrapper');
    var driverNameDisplay = document.getElementById('driver-name-display');
    var driverPhoneDisplay = document.getElementById('driver-phone-display');
    var driverAvatarContainer = document.getElementById('driver-avatar-container');
    var pollingInterval = null;
    var maxPollTime = 900000;
    var pollStartTime = 0;

    // Tracking state
    var map = null;
    var pickupMarker = null;
    var deliveryMarker = null;
    var courierMarker = null;
    var directionsRenderer = null;
    var directionsService = null;
    var currentFavorStatus = null;
    var currentFavorData = null;
    var courierLat = null;
    var courierLng = null;
    var pusherChannel = null;
    var favorId = null;

    requestForm.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!destAddr.value || !document.getElementById('delivery-lat').value) {
            alert('Selecciona una dirección de destino válida usando la sugerencia de Google Maps.');
            return;
        }

        var submitBtn = document.getElementById('submit-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="las la-spinner la-spin"></i> Procesando...';

        var formData = new FormData(requestForm);

        fetch(requestForm.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'success') {
                favorId = data.favor_id;
                overlayOrderNo.textContent = data.order_no;
                courierOverlay.classList.add('active');

                if (data.pin_code) {
                    document.getElementById('pin-code-display').textContent = data.pin_code;
                    document.getElementById('pin-display').style.display = 'block';
                }

                startStatusPolling(favorId);
            } else {
                alert(data.message || 'Ocurrió un error al procesar la solicitud.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="las la-paper-plane"></i> Solicitar Delivery';
            }
        })
        .catch(function(err) {
            console.error(err);
            alert('Error de conexión al servidor.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="las la-paper-plane"></i> Solicitar Delivery';
        });
    });

    // ════════════════════════════════
    //  POLLING & TRACKING
    // ════════════════════════════════
    function startStatusPolling(fvId) {
        if (pollingInterval) clearInterval(pollingInterval);
        pollStartTime = Date.now();

        pollingInterval = setInterval(function() {
            if (Date.now() - pollStartTime > maxPollTime) {
                clearInterval(pollingInterval);
                showDispatchExhausted();
                return;
            }

            var url = '{{ route("seller.delivery.request.status.show", ":id") }}'.replace(':id', fvId);
            fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'success') {
                    if (data.dispatch_mode === 'seller_exhausted') {
                        clearInterval(pollingInterval);
                        showDispatchExhausted();
                        return;
                    }
                    if (['accepted', 'on_way_to_pickup', 'at_pickup', 'on_way_to_delivery'].includes(data.favor_status)) {
                        clearInterval(pollingInterval);
                        currentFavorData = data.favor;
                        currentFavorStatus = data.favor_status;
                        showTracking(data);
                    } else if (data.favor_status === 'cancelled') {
                        clearInterval(pollingInterval);
                        alert('La búsqueda de repartidor ha sido cancelada.');
                        window.location.reload();
                    }
                }
            })
            .catch(function(err) { console.error('Poll error:', err); });
        }, 5000);
    }

    function showDispatchExhausted() {
        var actions = document.getElementById('dispatch-exhausted-actions');
        var cancelSearch = document.getElementById('cancel-search-btn');
        if (actions) actions.style.display = 'block';
        if (cancelSearch) cancelSearch.style.display = 'none';
        var title = radarSearchWrapper ? radarSearchWrapper.querySelector('h3') : null;
        var text = radarSearchWrapper ? radarSearchWrapper.querySelector('p') : null;
        if (title) title.textContent = 'No encontramos repartidor';
        if (text) text.textContent = 'Puedes volver a enviar la solicitud o cancelarla.';
    }

    function cancelCurrentDispatch() {
        if (!favorId || !confirm('¿Cancelar esta solicitud de delivery?')) return;
        fetch('{{ route("seller.delivery.request.cancel", ":id") }}'.replace(':id', favorId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ cancel_reason_code: 'no_courier_available' })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.status === 'success') window.location.reload();
            else alert(data.message || 'No se pudo cancelar la solicitud.');
        }).catch(function() { alert('No se pudo cancelar la solicitud.'); });
    }

    document.getElementById('cancel-search-btn')?.addEventListener('click', cancelCurrentDispatch);
    document.getElementById('cancel-dispatch-btn')?.addEventListener('click', cancelCurrentDispatch);
    document.getElementById('retry-dispatch-btn')?.addEventListener('click', function() {
        if (!favorId) return;
        var button = this;
        button.disabled = true;
        button.innerHTML = '<i class="las la-spinner la-spin"></i> Reenviando...';
        fetch('{{ route("seller.delivery.request.retry", ":id") }}'.replace(':id', favorId), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.status !== 'success') throw new Error(data.message || 'No se pudo reenviar.');
            document.getElementById('dispatch-exhausted-actions').style.display = 'none';
            document.getElementById('cancel-search-btn').style.display = '';
            var title = radarSearchWrapper.querySelector('h3');
            var text = radarSearchWrapper.querySelector('p');
            if (title) title.textContent = 'Buscando Repartidor...';
            if (text) text.textContent = 'Notificando a todos los repartidores disponibles.';
            button.disabled = false;
            button.innerHTML = '<i class="las la-redo-alt"></i> Volver a enviar';
            startStatusPolling(favorId);
        }).catch(function(error) {
            alert(error.message || 'No se pudo reenviar la solicitud.');
            button.disabled = false;
            button.innerHTML = '<i class="las la-redo-alt"></i> Volver a enviar';
        });
    });

    function updateWhatsAppShareBtn(f, pinCode) {
        var waBtn = document.getElementById('share-pin-wa-btn');
        if (!waBtn) return;

        var recipientPhone = (f.recipient_phone || '').replace(/\D/g, '');
        if (recipientPhone.length === 9) {
            recipientPhone = '51' + recipientPhone;
        }

        var recipientName = f.recipient_name || 'Cliente';
        var orderNo = f.order_no || ('#' + f.id);
        var storeName = '{{ $store->name ?? "Nuestra tienda" }}';
        var deliveryAddress = f.delivery_address || '';
        var paymentInfo = '';

        if (f.payer_type === 'recipient') {
            var codVal = parseFloat(f.cod_amount || f.total || f.delivery_fee || 4.0).toFixed(2);
            paymentInfo = '💵 *Monto a pagar al recibir:* S/ ' + codVal + ' (en efectivo)\n';
        } else {
            paymentInfo = '💳 *Estado de pago:* Envío Pagado\n';
        }

        var msg = '¡Hola ' + recipientName + '! 👋✨\n\n' +
            'Tu pedido de *' + storeName + '* ha sido procesado y está en camino 🛵💨\n\n' +
            '📋 *Detalles del pedido:*\n' +
            '• *N° de Orden:* ' + orderNo + '\n' +
            '• *Dirección:* ' + deliveryAddress + '\n' +
            paymentInfo + '\n' +
            '🔐 *Tu PIN secreto de entrega:* *' + pinCode + '*\n\n' +
            '⚠️ *Importante:* Por favor, proporciona este PIN de 4 dígitos al repartidor únicamente al recibir tu paquete para confirmar la entrega.\n\n' +
            '¡Muchas gracias por tu preferencia! 🙌🛒';

        var waUrl = 'https://api.whatsapp.com/send?phone=' + encodeURIComponent(recipientPhone) + '&text=' + encodeURIComponent(msg);
        waBtn.href = waUrl;
    }

    function showTracking(data) {
        if (data.favor_status) {
            currentFavorStatus = data.favor_status;
        } else if (data.favor && data.favor.status) {
            currentFavorStatus = data.favor.status;
        }

        radarSearchWrapper.style.display = 'none';
        driverAssignedWrapper.style.display = 'block';
        overlayContent.classList.add('map-visible');

        if (data.courier) {
            var c = data.courier;
            driverNameDisplay.textContent = c.name || 'Conductor asignado';
            driverPhoneDisplay.href = 'tel:' + (c.phone || '');
            driverPhoneDisplay.setAttribute('target', '_blank');
            driverPhoneDisplay.querySelector('span').textContent = c.phone || 'Sin teléfono';

            var driverPhoneClean = (c.phone || '').replace(/\D/g, '');
            if (driverPhoneClean.length === 9) driverPhoneClean = '51' + driverPhoneClean;
            var driverWaBtn = document.getElementById('driver-wa-display');
            if (driverWaBtn) {
                var storeName = '{{ $store->name ?? "Tienda" }}';
                var waMsg = '¡Hola ' + (c.name || '') + '! 👋 Te escribo desde la tienda *' + storeName + '*. Pedido #' + (favorId || '');
                driverWaBtn.href = 'https://api.whatsapp.com/send?phone=' + encodeURIComponent(driverPhoneClean) + '&text=' + encodeURIComponent(waMsg);
                driverWaBtn.setAttribute('target', '_blank');
            }

            document.getElementById('driver-distance-display').textContent = (c.distance_km || '--') + ' km';
            document.getElementById('driver-time-display').textContent = (c.time_min || '--') + ' min';

            if (c.image) {
                driverAvatarContainer.innerHTML = '<img src="' + c.image + '" class="driver-avatar" alt="Foto">';
            } else {
                var initials = (c.name || 'C').split(' ').map(function(n) { return n[0]; }).join('').substring(0, 2).toUpperCase();
                driverAvatarContainer.innerHTML = '<div class="driver-avatar">' + initials + '</div>';
            }
        }

        var f = data.favor;
        if (f) {
            // Payment info
            var paymentInfoEl = document.getElementById('payment-info-overlay');
            var paymentTextEl = document.getElementById('payment-info-text');
            if (f.payer_type === 'recipient') {
                var codVal = (f.cod_amount !== null && f.cod_amount !== undefined && parseFloat(f.cod_amount) > 0) ? parseFloat(f.cod_amount) : parseFloat(f.total || f.delivery_fee || 4.0);
                paymentTextEl.textContent = 'El destinatario paga en efectivo: S/ ' + codVal.toFixed(2);
                paymentInfoEl.style.display = 'block';
                paymentInfoEl.style.border = '1px solid rgba(245,158,11,0.3)';
                paymentInfoEl.style.background = 'rgba(245,158,11,0.08)';
                paymentInfoEl.querySelector('i').style.color = '#f59e0b';
            } else {
                paymentTextEl.textContent = 'Envío pagado (' + (f.payment_method_name || 'Efectivo') + ')';
                paymentInfoEl.style.display = 'block';
                paymentInfoEl.style.border = '1px solid rgba(34,197,94,0.3)';
                paymentInfoEl.style.background = 'rgba(34,197,94,0.08)';
                paymentInfoEl.querySelector('i').style.color = '#22c55e';
            }

            // PIN & WhatsApp share
            if (f.pin_code) {
                document.getElementById('pin-code-display').textContent = f.pin_code;
                document.getElementById('pin-display').style.display = 'block';
                updateWhatsAppShareBtn(f, f.pin_code);
            }

            // ETA
            if (f.eta && f.eta.duration_text) {
                document.getElementById('eta-text').textContent = f.eta.duration_text + ' (' + f.eta.distance_text + ')';
                document.getElementById('eta-display').style.display = 'block';
            } else if (f.estimated_minutes) {
                document.getElementById('eta-text').textContent = '~' + f.estimated_minutes + ' min';
                document.getElementById('eta-display').style.display = 'block';
            }

            // Express badge
            if (f.is_express) {
                var expressBadge = document.createElement('div');
                expressBadge.style.cssText = 'margin-top:8px;padding:6px 12px;background:rgba(251,191,36,0.1);border:1px solid rgba(251,191,36,0.3);border-radius:8px;font-size:12px;font-weight:700;color:#fbbf24;text-align:center;';
                expressBadge.textContent = '⚡ ENVÍO EXPRESS — Prioridad máxima';
                document.getElementById('payment-info-overlay').parentNode.insertBefore(expressBadge, document.getElementById('payment-info-overlay').nextSibling);
            }

            // Fragile badge
            if (f.is_fragile) {
                var fragileBadge = document.createElement('div');
                fragileBadge.style.cssText = 'margin-top:8px;padding:6px 12px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;font-size:12px;font-weight:700;color:#ef4444;text-align:center;';
                fragileBadge.textContent = '⚠️ FRÁGIL — Manejar con cuidado';
                document.getElementById('payment-info-overlay').parentNode.insertBefore(fragileBadge, document.getElementById('payment-info-overlay').nextSibling);
            }

            // Intermediate Stops List
            if (f.stops && Array.isArray(f.stops) && f.stops.length > 0) {
                var stopsListContainer = document.getElementById('stops-list-overlay');
                if (stopsListContainer) {
                    var html = '<div style="margin-top:10px;padding:12px;background:rgba(251,191,36,0.06);border:1px solid rgba(251,191,36,0.25);border-radius:12px;">';
                    html += '<strong style="color:#fbbf24;font-size:12px;display:block;margin-bottom:6px;">📍 Ruta con ' + f.stops.length + ' Parada(s) Intermedia(s):</strong>';
                    html += '<div style="font-size:11.5px;color:rgba(255,255,255,0.85);line-height:1.5;">';
                    f.stops.forEach(function(s, idx) {
                        html += '<div style="margin-bottom:4px;">• <strong>Parada #' + (idx + 1) + ':</strong> ' + s.address + (s.recipient_name ? ' (' + s.recipient_name + ')' : '') + '</div>';
                    });
                    html += '</div></div>';
                    stopsListContainer.innerHTML = html;
                    stopsListContainer.style.display = 'block';
                }
            }
        }

        initTrackingMap(data);
        connectPusher(favorId);
        updateStatusBadges(currentFavorStatus);
    }

    // ════════════════════════════════
    //  MAP
    // ════════════════════════════════
    function initTrackingMap(data) {
        var f = data.favor;
        if (!f || !f.pickup_lat || !f.pickup_lng) return;

        directionsService = new google.maps.DirectionsService();

        var mapEl = document.getElementById('tracking-map');
        map = new google.maps.Map(mapEl, {
            center: { lat: f.pickup_lat, lng: f.pickup_lng },
            zoom: 14,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
            styles: [
                { elementType: 'geometry', stylers: [{ color: '#242f3e' }] },
                { elementType: 'labels.text.stroke', stylers: [{ color: '#242f3e' }] },
                { elementType: 'labels.text.fill', stylers: [{ color: '#746855' }] },
                { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#38414e' }] },
                { featureType: 'road', elementType: 'geometry.stroke', stylers: [{ color: '#212a37' }] },
                { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#17263c' }] },
            ]
        });

        pickupMarker = new google.maps.Marker({
            position: { lat: f.pickup_lat, lng: f.pickup_lng },
            map: map,
            label: { text: 'R', color: '#fff', fontWeight: 'bold', fontSize: '14px' },
            title: 'Punto de Recogida',
            icon: { path: google.maps.SymbolPath.CIRCLE, scale: 10, fillColor: '#22c55e', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2 }
        });

        deliveryMarker = new google.maps.Marker({
            position: { lat: f.delivery_lat, lng: f.delivery_lng },
            map: map,
            label: { text: 'E', color: '#fff', fontWeight: 'bold', fontSize: '14px' },
            title: 'Destino',
            icon: { path: google.maps.SymbolPath.CIRCLE, scale: 10, fillColor: '#ef4444', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2 }
        });

        if (currentFavorStatus !== 'delivered' && currentFavorStatus !== 'completed' && data.courier && data.courier.current_lat && data.courier.current_lng) {
            courierLat = data.courier.current_lat;
            courierLng = data.courier.current_lng;
            addCourierMarker(courierLat, courierLng);
        } else if (courierMarker) {
            courierMarker.setMap(null);
            courierMarker = null;
        }

        var bounds = new google.maps.LatLngBounds();
        bounds.extend({ lat: f.pickup_lat, lng: f.pickup_lng });
        bounds.extend({ lat: f.delivery_lat, lng: f.delivery_lng });
        if (currentFavorStatus !== 'delivered' && currentFavorStatus !== 'completed' && courierLat && courierLng) bounds.extend({ lat: courierLat, lng: courierLng });
        map.fitBounds(bounds);
        if (map.getZoom() > 16) map.setZoom(16);

        drawRouteForStatus(currentFavorStatus, f, data.courier);
    }

    function addCourierMarker(lat, lng) {
        if (!map) return;
        if (currentFavorStatus === 'delivered' || currentFavorStatus === 'completed') {
            if (courierMarker) {
                courierMarker.setMap(null);
                courierMarker = null;
            }
            return;
        }
        if (courierMarker) {
            animateMarker(courierMarker, lat, lng);
        } else {
            courierMarker = new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: map,
                title: 'Repartidor',
                icon: {
                    url: '{{ asset("assets/images/delivery_man_marker.png") }}',
                    scaledSize: new google.maps.Size(46, 46),
                    anchor: new google.maps.Point(23, 23)
                },
            });
        }
    }

    function animateMarker(marker, lat, lng) {
        var startLat = marker.getPosition().lat();
        var startLng = marker.getPosition().lng();
        var steps = 20;
        var step = 0;
        function animate() {
            step++;
            if (step > steps) { marker.setPosition({ lat: lat, lng: lng }); return; }
            var t = step / steps;
            marker.setPosition({ lat: startLat + (lat - startLat) * t, lng: startLng + (lng - startLng) * t });
            requestAnimationFrame(animate);
        }
        animate();
    }

    function drawRouteForStatus(status, f, courier) {
        if (!directionsService || !map) return;
        if (directionsRenderer) { directionsRenderer.setMap(null); directionsRenderer = null; }

        var origin, destination, label;
        var pickupPos = { lat: f.pickup_lat, lng: f.pickup_lng };
        var deliveryPos = { lat: f.delivery_lat, lng: f.delivery_lng };

        var waypoints = [];
        if (f.stops && Array.isArray(f.stops)) {
            f.stops.forEach(function(s, sIdx) {
                if (s.lat && s.lng) {
                    waypoints.push({
                        location: new google.maps.LatLng(s.lat, s.lng),
                        stopover: true
                    });
                    new google.maps.Marker({
                        position: { lat: s.lat, lng: s.lng },
                        map: map,
                        label: { text: '' + (sIdx + 1), color: '#000', fontWeight: 'bold', fontSize: '11px' },
                        title: 'Parada #' + (sIdx + 1) + ': ' + (s.address || ''),
                        icon: { path: google.maps.SymbolPath.CIRCLE, scale: 9, fillColor: '#fbbf24', fillOpacity: 1, strokeColor: '#000', strokeWeight: 2 }
                    });
                }
            });
        }

        if (status === 'accepted' || status === 'on_way_to_pickup') {
            if (courier && courier.current_lat && courier.current_lng) {
                origin = { lat: courier.current_lat, lng: courier.current_lng };
                destination = pickupPos;
                label = 'Repartidor → Punto de Recogida';
            } else {
                origin = pickupPos; destination = deliveryPos; label = 'Punto de Recogida → Destino';
            }
        } else if (status === 'at_pickup' || status === 'on_way_to_delivery') {
            origin = pickupPos; destination = deliveryPos; label = 'Recojo → ' + (waypoints.length > 0 ? waypoints.length + ' Parada(s) → ' : '') + 'Destino Final';
        } else if (status === 'delivered') {
            document.getElementById('route-description').textContent = '✅ Entregado';
            return;
        } else {
            return;
        }

        document.getElementById('route-description').textContent = label;

        directionsService.route({ origin: origin, destination: destination, travelMode: google.maps.TravelMode.DRIVING }, function(result, statusRes) {
            if (statusRes === 'OK') {
                directionsRenderer = new google.maps.DirectionsRenderer({
                    map: map, directions: result, suppressMarkers: true,
                    polylineOptions: { strokeColor: '#22c55e', strokeWeight: 4, strokeOpacity: 0.8 }
                });
                if (result.routes[0] && result.routes[0].legs[0]) {
                    var leg = result.routes[0].legs[0];
                    document.getElementById('route-eta').textContent = leg.duration.text + ' (' + leg.distance.text + ')';
                }
            }
        });
    }

    // ════════════════════════════════
    //  PUSHER
    // ════════════════════════════════
    function connectPusher(fvId) {
        var pusherKey = '{{ $pusherConfig["key"] }}';
        if (!pusherKey) return;

        var pusher = new Pusher(pusherKey, {
            wsHost: '{{ $pusherConfig["host"] }}',
            wsPort: {{ $pusherConfig["port"] }},
            wssPort: {{ $pusherConfig["port"] }},
            forceTLS: {{ $pusherConfig["scheme"] === 'https' ? 'true' : 'false' }},
            enabledTransports: ['ws', 'wss'],
            authorizer: function(channel) {
                return {
                    authorize: function(socketId, callback) {
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', '{{ route("seller.broadcasting.auth") }}', true);
                        xhr.setRequestHeader('Content-Type', 'application/json');
                        xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState === 4) {
                                if (xhr.status === 200) callback(null, JSON.parse(xhr.responseText));
                                else callback(new Error('Auth failed'), null);
                            }
                        };
                        xhr.send(JSON.stringify({ socket_id: socketId, channel_name: channel.name }));
                    }
                };
            }
        });

        pusherChannel = pusher.subscribe('private-favor.' + fvId);

        pusherChannel.bind('location_update', function(data) {
            if (currentFavorStatus === 'delivered' || currentFavorStatus === 'completed') return;
            if (data.latitude && data.longitude) {
                courierLat = data.latitude; courierLng = data.longitude;
                addCourierMarker(data.latitude, data.longitude);
                if (currentFavorData && currentFavorStatus) {
                    drawRouteForStatus(currentFavorStatus, currentFavorData, { current_lat: data.latitude, current_lng: data.longitude });
                }
            }
        });

        pusherChannel.bind('eta_update', function(data) {
            if (data.duration_text) {
                document.getElementById('eta-text').textContent = data.duration_text + ' (' + data.distance_text + ')';
                document.getElementById('eta-display').style.display = 'block';
            }
        });

        pusherChannel.bind('favor_status_updated', function(data) {
            if (data.status) {
                currentFavorStatus = data.status;
                updateStatusBadges(data.status);
                if (currentFavorData) {
                    drawRouteForStatus(data.status, currentFavorData, { current_lat: courierLat, current_lng: courierLng });
                }
                if (data.favor && data.favor.eta && data.favor.eta.duration_text) {
                    document.getElementById('eta-text').textContent = data.favor.eta.duration_text + ' (' + data.favor.eta.distance_text + ')';
                    document.getElementById('eta-display').style.display = 'block';
                }
                if (data.favor && data.favor.courier) {
                    driverNameDisplay.textContent = data.favor.courier.fullname || driverNameDisplay.textContent;
                }
            }
        });

        pusher.connection.bind('connected', function() {
            console.log('Tracking: Conectado a Reverb/Pusher');
        });
    }

    // ════════════════════════════════
    //  STATUS BADGES
    // ════════════════════════════════
    function updateStatusBadges(status) {
        if (!status) return;
        if (status === 'completed') status = 'delivered';

        var allSteps = ['accepted', 'on_way_to_pickup', 'at_pickup', 'on_way_to_delivery', 'delivered'];
        var idx = allSteps.indexOf(status);
        if (idx === -1) {
            if (status.includes('deliver') || status.includes('finish') || status.includes('complete')) {
                status = 'delivered';
                idx = 4;
            } else {
                idx = 0;
            }
        }

        // Dynamic Title & Description according to real status
        var titleEl = document.getElementById('tracking-status-title');
        var descEl = document.getElementById('tracking-status-desc');

        var statusTitles = {
            accepted: '¡Repartidor Asignado!',
            on_way_to_pickup: '🛵 Repartidor en Camino al Recojo',
            at_pickup: '🏪 Repartidor en el Punto de Recojo',
            on_way_to_delivery: '📦 En Camino al Destino',
            delivered: '🎉 ¡Pedido Entregado con Éxito!'
        };
        var statusDescs = {
            accepted: 'El pedido ha sido aceptado por:',
            on_way_to_pickup: 'El repartidor se dirige a tu negocio:',
            at_pickup: 'El repartidor ya está en tu tienda:',
            on_way_to_delivery: 'El repartidor se encuentra llevando el paquete al cliente:',
            delivered: 'La entrega fue realizada exitosamente por:'
        };

        if (titleEl && statusTitles[status]) titleEl.textContent = statusTitles[status];
        if (descEl && statusDescs[status]) descEl.textContent = statusDescs[status];

        var ids = ['sb-accepted', 'sb-on_way_to_pickup', 'sb-at_pickup', 'sb-on_way_to_delivery', 'sb-delivered'];
        var labels = ['Asignado', 'En camino al recojo', 'En el recojo', 'En camino a entrega', 'Entregado'];
        var dotColors = ['yellow', 'blue', 'blue', 'purple', 'green'];

        for (var i = 0; i < ids.length; i++) {
            var el = document.getElementById(ids[i]);
            if (!el) continue;
            if (i <= idx) {
                el.style.opacity = '1';
                if (i === idx) {
                    el.className = 'status-badge ' + (status === 'delivered' ? 'done' : (status === 'on_way_to_delivery' ? 'delivery' : (status === 'on_way_to_pickup' || status === 'at_pickup' ? 'pickup' : 'waiting')));
                } else {
                    el.className = 'status-badge done';
                }
                el.innerHTML = '<span class="dot ' + (i < idx ? 'green' : dotColors[i]) + '"></span> ' + labels[i];
            } else {
                el.style.opacity = '0.4';
                el.className = 'status-badge';
                el.innerHTML = '<span class="dot ' + dotColors[i] + '"></span> ' + labels[i];
            }
        }
    }

    // Cleanup
    window.addEventListener('beforeunload', function() {
        if (pusherChannel) pusherChannel.unsubscribe();
        if (pollingInterval) clearInterval(pollingInterval);
    });
</script>
@endpush
