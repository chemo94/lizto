@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <section class="complaints-section py-120 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm text-center p-5 rounded-4 bg-white" style="border-radius: 16px;">
                        <div class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle mb-4" style="width: 72px; height: 72px; font-size: 36px; box-shadow: 0 8px 16px rgba(34,197,94,0.25);">
                            <i class="las la-check-circle"></i>
                        </div>
                        <h2 class="text-dark fw-bold mb-2">¡Reclamación Registrada!</h2>
                        <p class="text-muted mb-4 fs-14">Tu reclamo o queja ha sido recibido correctamente bajo la regulación de INDECOPI.</p>
                        
                        <div class="bg-light p-4 rounded-3 mb-4 mx-auto" style="max-width: 460px; border-radius: 10px;">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Número de Ticket:</span>
                                <strong class="text-dark">{{ $complaint->ticket_number }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Fecha y Hora:</span>
                                <span class="text-dark fw-medium">{{ $complaint->created_at->format('d/m/Y H:i A') }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Tipo:</span>
                                <span class="badge bg-dark text-white">{{ $complaint->type_name }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Estado Inicial:</span>
                                <span class="badge bg-warning text-dark">{{ $complaint->status_name }}</span>
                            </div>
                        </div>

                        <p class="text-muted fs-13 mb-4" style="line-height: 1.6;">
                            Hemos enviado una copia a tu dirección de correo electrónico registrado. De acuerdo a ley, el proveedor tiene un plazo máximo de quince (15) días hábiles improrrogables para dar respuesta a su reclamación.
                        </p>

                        <div class="d-flex flex-wrap justify-content-center gap-3">
                            <a href="{{ route('complaints.pdf', $complaint->ticket_number) }}" class="btn btn--base-two px-4 py-2" style="border-radius: 8px;">
                                <i class="las la-file-pdf me-2" style="font-size: 18px;"></i> Descargar Copia en PDF
                            </a>
                            <a href="{{ route('home') }}" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 8px;">
                                <i class="las la-home me-2" style="font-size: 18px;"></i> Volver al Inicio
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
