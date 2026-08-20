<!-- BOTTOM SHEET INVOICE PREVIEW MODAL -->
<div id="s-invoice-preview-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); z-index: 999999; opacity: 0; transition: opacity 0.3s ease;">
    <div id="s-invoice-preview-sheet" style="position: fixed; bottom: 0; left: 50%; transform: translate(-50%, 100%); width: min(850px, 100vw); height: 88vh; max-height: 94vh; background: #ffffff; border-radius: 20px 20px 0 0; box-shadow: 0 -12px 40px rgba(0,0,0,0.25); display: flex; flex-direction: column; overflow: hidden; transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1); z-index: 1000000; border: 1px solid var(--s-border);">
        
        <!-- Sheet Handle & Header -->
        <div style="padding: 14px 20px 10px; background: #ffffff; border-bottom: 1px solid var(--s-border); display: flex; flex-direction: column; gap: 10px;">
            <div style="width: 44px; height: 5px; background: #cbd5e1; border-radius: 999px; margin: 0 auto; cursor: grab;"></div>
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(37,99,235,0.1); color: var(--s-primary); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                        <i class="las la-file-invoice" id="s-preview-icon"></i>
                    </div>
                    <div>
                        <h4 id="s-preview-title" style="margin: 0; font-size: 15px; font-weight: 800; color: var(--s-text-primary); line-height: 1.2;">Vista Previa de Comprobante</h4>
                        <small id="s-preview-subtitle" style="font-size: 11px; color: var(--s-text-muted);">Comprobante electrónico</small>
                    </div>
                </div>

                <!-- Format Switcher Buttons -->
                <div style="display: inline-flex; background: var(--s-surface-2, #f1f5f9); padding: 3px; border-radius: 10px; gap: 2px;">
                    <button type="button" class="s-preview-format-tab" data-format="a4" onclick="switchInvoicePreviewFormat('a4')" style="padding: 6px 12px; font-size: 12px; font-weight: 800; border: none; border-radius: 8px; cursor: pointer; background: #fff; color: var(--s-primary); box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.2s;">
                        <i class="las la-file-pdf" style="color:#dc2626;"></i> PDF A4
                    </button>
                    <button type="button" class="s-preview-format-tab" data-format="a5" onclick="switchInvoicePreviewFormat('a5')" style="padding: 6px 12px; font-size: 12px; font-weight: 800; border: none; border-radius: 8px; cursor: pointer; background: transparent; color: var(--s-text-secondary); transition: all 0.2s;">
                        <i class="las la-file-pdf" style="color:#f59e0b;"></i> PDF A5
                    </button>
                    <button type="button" class="s-preview-format-tab" data-format="ticket" onclick="switchInvoicePreviewFormat('ticket')" style="padding: 6px 12px; font-size: 12px; font-weight: 800; border: none; border-radius: 8px; cursor: pointer; background: transparent; color: var(--s-text-secondary); transition: all 0.2s;">
                        <i class="las la-receipt" style="color:var(--s-info);"></i> Ticket (80mm)
                    </button>
                </div>

                <!-- Action buttons -->
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" class="s-btn s-btn-success s-btn-sm" onclick="printInvoicePreviewFrame()" style="gap: 6px; font-weight: 700; height: 34px; padding: 0 12px;">
                        <i class="las la-print" style="font-size: 16px;"></i> Imprimir
                    </button>
                    <a id="s-preview-newtab-btn" href="#" target="_blank" class="s-btn s-btn-outline s-btn-sm" style="gap: 6px; font-weight: 700; height: 34px; padding: 0 10px;" title="Abrir en pestaña nueva">
                        <i class="las la-external-link-alt" style="font-size: 15px;"></i>
                    </a>
                    <button type="button" onclick="closeInvoicePreview()" style="width: 34px; height: 34px; border-radius: 8px; border: 1px solid var(--s-border); background: #fff; color: var(--s-text-secondary); font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center;" title="Cerrar (Esc)">
                        ✕
                    </button>
                </div>
            </div>
        </div>

        <!-- Frame Loading & Container -->
        <div style="flex: 1; position: relative; background: #e2e8f0; overflow: hidden; display: flex; align-items: center; justify-content: center;">
            <div id="s-preview-loader" style="position: absolute; display: flex; flex-direction: column; align-items: center; gap: 8px; color: var(--s-text-muted); font-size: 13px; font-weight: 600;">
                <i class="las la-spinner la-spin" style="font-size: 32px; color: var(--s-primary);"></i>
                <span>Cargando previsualización...</span>
            </div>
            <iframe id="s-preview-iframe" name="s_preview_iframe" src="" style="width: 100%; height: 100%; border: none; background: #fff; opacity: 0; transition: opacity 0.25s ease;" onload="onInvoicePreviewLoaded()"></iframe>
        </div>
    </div>
</div>

<script>
let currentPreviewInvoiceId = null;
let currentPreviewFormat = 'a4';
let currentPreviewBaseUrl = '{{ route("seller.invoice.pdf", ["__ID__", "__FORMAT__"]) }}';

function openInvoicePreview(invoiceId, format = 'a4', title = '', subtitle = '') {
    currentPreviewInvoiceId = invoiceId;
    currentPreviewFormat = format || 'a4';
    
    const overlay = document.getElementById('s-invoice-preview-overlay');
    const sheet = document.getElementById('s-invoice-preview-sheet');
    const titleEl = document.getElementById('s-preview-title');
    const subtitleEl = document.getElementById('s-preview-subtitle');
    
    if (titleEl && title) titleEl.textContent = title;
    if (subtitleEl && subtitle) subtitleEl.textContent = subtitle;
    
    updatePreviewFormatTabs(currentPreviewFormat);
    loadInvoicePreviewFrame();

    overlay.style.display = 'block';
    requestAnimationFrame(() => {
        overlay.style.opacity = '1';
        sheet.style.transform = 'translate(-50%, 0)';
    });
}

function closeInvoicePreview() {
    const overlay = document.getElementById('s-invoice-preview-overlay');
    const sheet = document.getElementById('s-invoice-preview-sheet');
    const iframe = document.getElementById('s-preview-iframe');
    
    if (!overlay || !sheet) return;
    overlay.style.opacity = '0';
    sheet.style.transform = 'translate(-50%, 100%)';
    setTimeout(() => {
        overlay.style.display = 'none';
        if (iframe) iframe.src = 'about:blank';
    }, 320);
}

function switchInvoicePreviewFormat(format) {
    if (currentPreviewFormat === format) return;
    currentPreviewFormat = format;
    updatePreviewFormatTabs(format);
    loadInvoicePreviewFrame();
}

function updatePreviewFormatTabs(format) {
    document.querySelectorAll('.s-preview-format-tab').forEach(tab => {
        const isSelected = tab.getAttribute('data-format') === format;
        tab.style.background = isSelected ? '#fff' : 'transparent';
        tab.style.color = isSelected ? 'var(--s-primary)' : 'var(--s-text-secondary)';
        tab.style.boxShadow = isSelected ? '0 1px 3px rgba(0,0,0,0.1)' : 'none';
    });
}

function loadInvoicePreviewFrame() {
    if (!currentPreviewInvoiceId) return;
    const iframe = document.getElementById('s-preview-iframe');
    const loader = document.getElementById('s-preview-loader');
    const newTabBtn = document.getElementById('s-preview-newtab-btn');
    
    if (loader) loader.style.display = 'flex';
    if (iframe) iframe.style.opacity = '0';
    
    const url = currentPreviewBaseUrl.replace('__ID__', currentPreviewInvoiceId).replace('__FORMAT__', currentPreviewFormat);
    if (iframe) iframe.src = url;
    if (newTabBtn) newTabBtn.href = url;
}

function onInvoicePreviewLoaded() {
    const iframe = document.getElementById('s-preview-iframe');
    const loader = document.getElementById('s-preview-loader');
    if (iframe && iframe.src !== 'about:blank') {
        if (loader) loader.style.display = 'none';
        iframe.style.opacity = '1';
    }
}

function printInvoicePreviewFrame() {
    const iframe = document.getElementById('s-preview-iframe');
    if (iframe && iframe.contentWindow) {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    }
}

// Close on clicking backdrop or Escape
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('s-invoice-preview-overlay');
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closeInvoicePreview();
            }
        });
    }
});

// Intercept invoice PDF preview links across the seller panel
document.addEventListener('click', function(e) {
    const link = e.target.closest('a[href*="/invoicing/invoice/"][href*="/pdf/"]');
    if (link) {
        e.preventDefault();
        const href = link.getAttribute('href');
        const match = href.match(/\/invoicing\/invoice\/(\d+)\/pdf\/([a-zA-Z0-9]+)/);
        if (match) {
            const invoiceId = match[1];
            const format = match[2] || 'a4';
            const row = link.closest('tr');
            let docLabel = 'Comprobante Electrónico';
            if (row) {
                const badge = row.querySelector('.s-badge, td:first-child');
                if (badge) docLabel = badge.textContent.trim();
            }
            openInvoicePreview(invoiceId, format, docLabel, 'Formato ' + format.toUpperCase());
        }
    }
});
</script>
