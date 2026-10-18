(function () {
    'use strict';

    var nativeAlert = window.alert.bind(window);
    var nativeConfirm = window.confirm.bind(window);
    var activeSubmittingForm = null;
    var activeClickedForm = null;
    var approvedForms = new WeakSet();

    // Replace native confirm dialogs on form submissions with an async Swal dialog,
    // then safely resume the same form submission after the user approves it.
    document.addEventListener('submit', function (event) {
        activeSubmittingForm = event.target;
        window.setTimeout(function () { activeSubmittingForm = null; }, 0);
    }, true);
    document.addEventListener('click', function (event) {
        activeClickedForm = event.target.closest('form');
        window.setTimeout(function () { activeClickedForm = null; }, 0);
    }, true);

    window.alert = function (message) {
        if (!window.Swal || typeof window.Swal.fire !== 'function') {
            return nativeAlert(message);
        }

        var text = message === undefined || message === null ? '' : String(message);
        var normalized = text.toLocaleLowerCase();
        var icon = /error|falló|fallo|no se pudo|no pudimos|inválid|invalido|incorrect/.test(normalized)
            ? 'error'
            : (/guardad|exitos|correctamente|copiado|registrad/.test(normalized) ? 'success' : 'info');

        return window.Swal.fire({
            title: icon === 'error' ? 'Ocurrió un problema' : (icon === 'success' ? 'Listo' : 'Aviso'),
            text: text,
            icon: icon,
            confirmButtonText: 'Entendido',
            buttonsStyling: false,
            customClass: {
                popup: 'lizto-swal-popup',
                title: 'lizto-swal-title',
                htmlContainer: 'lizto-swal-message',
                confirmButton: 'lizto-swal-confirm'
            }
        });
    };

    window.confirm = function (message) {
        var form = activeSubmittingForm || activeClickedForm;
        if (!form || !window.Swal || typeof window.Swal.fire !== 'function') {
            return nativeConfirm(message);
        }
        if (approvedForms.has(form)) {
            approvedForms.delete(form);
            return true;
        }

        window.Swal.fire({
            title: '¿Confirmas esta acción?',
            text: message === undefined || message === null ? '' : String(message),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            focusCancel: true,
            buttonsStyling: false,
            customClass: {
                popup: 'lizto-swal-popup',
                title: 'lizto-swal-title',
                htmlContainer: 'lizto-swal-message',
                confirmButton: 'lizto-swal-confirm',
                cancelButton: 'lizto-swal-cancel'
            }
        }).then(function (result) {
            if (!result.isConfirmed) return;
            approvedForms.add(form);
            form.requestSubmit();
            window.setTimeout(function () { approvedForms.delete(form); }, 0);
        });
        return false;
    };
})();
