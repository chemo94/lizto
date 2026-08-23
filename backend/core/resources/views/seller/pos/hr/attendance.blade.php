@extends('seller.layouts.app')

@section('page-title')
<span class="s-title-icon"><i class="las la-calendar-check"></i></span> Control de Asistencia - RR.HH
@endsection

@section('seller-content')
<div class="s-content">
    <section class="module-hero attendance">
        <div>
            <div class="module-crumb"><i class="las la-home"></i> Seller / RR.HH / Asistencia</div>
            <h2>Control de asistencia</h2>
            <p>Registra entradas, salidas y jornada diaria de todo el equipo.</p>
        </div>
        <div class="module-hero-stats">
            <div><b>{{ $staff->count() }}</b><small>Personal</small></div>
            <div><b>{{ $attendances->where('status', 'present')->count() }}</b><small>Presentes</small></div>
            <div><b>{{ $attendances->whereNotNull('clock_out')->count() }}</b><small>Salidas</small></div>
        </div>
    </section>
    <div class="hr-workspace" style="display: grid; grid-template-columns: 360px 1fr; gap: 14px; align-items: start;">
        
        <!-- PANEL DE MARCACIÓN RÁPIDA (DNI/RUC) -->
        <div class="s-card seller-work-card" style="border: 1px solid var(--s-primary-light); background: linear-gradient(145deg, var(--s-bg-card), var(--s-bg-light));">
            <h3 style="margin-bottom: 15px; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
                <i class="las la-clock" style="color: var(--s-primary); font-size: 24px;"></i> Marcación de Entrada/Salida
            </h3>
            <p style="font-size: 12px; color: var(--s-text-muted); margin-bottom: 20px;">
                Los empleados pueden marcar su entrada o salida ingresando su documento de identidad.
            </p>

            <form method="POST" action="{{ route('seller.pos.hr.attendance.clock') }}">
                @csrf
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label class="s-label">Número de Documento (DNI/RUC)</label>
                        <input class="s-input" name="document_number" placeholder="Ingrese el DNI/RUC del trabajador" required style="font-size: 16px; text-align: center; letter-spacing: 2px; height: 46px;" autofocus autocomplete="off">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <button type="submit" name="action" value="clock_in" class="s-btn s-btn-primary" style="height: 48px; justify-content: center; font-weight: 700;">
                            <i class="las la-sign-in-alt" style="font-size: 20px;"></i> Entrada
                        </button>
                        <button type="submit" name="action" value="clock_out" class="s-btn s-btn-outline" style="height: 48px; justify-content: center; font-weight: 700; border-color: var(--s-danger); color: var(--s-danger);">
                            <i class="las la-sign-out-alt" style="font-size: 20px;"></i> Salida
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- LISTADO Y CONTROL DE ASISTENCIA DIARIA -->
        <div class="s-card seller-work-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                <h3 style="margin: 0; font-weight: 700; font-size: 16px; color: var(--s-text-primary); display: flex; align-items: center; gap: 8px;">
                    <i class="las la-user-check" style="color: var(--s-primary); font-size: 20px;"></i> Asistencia por Fecha
                </h3>
                
                <form method="GET" action="{{ route('seller.pos.hr.attendance') }}" style="display: flex; align-items: center; gap: 8px;">
                    <label class="s-label" style="margin: 0; font-weight: 600;">Fecha:</label>
                    <input type="date" name="date" value="{{ $date }}" class="s-input" style="width: auto; height: 36px; padding: 4px 10px;" onchange="this.form.submit()">
                </form>
            </div>

            <form method="POST" action="{{ route('seller.pos.hr.attendance.store') }}">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">
                
                <div style="overflow-x: auto; margin-bottom: 20px;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                        <thead>
                          <tr style="border-bottom: 1.5px solid var(--s-border); color: var(--s-text-muted); font-weight: 700;">
                              <th style="padding: 10px 14px; width: 250px;">Colaborador</th>
                              <th style="padding: 10px 14px; width: 160px;">Estado</th>
                              <th style="padding: 10px 14px; width: 120px;">Entrada (H)</th>
                              <th style="padding: 10px 14px; width: 120px;">Salida (H)</th>
                              <th style="padding: 10px 14px; width: 100px;">Horas Trab.</th>
                              <th style="padding: 10px 14px;">Notas</th>
                          </tr>
                        </thead>
                        <tbody>
                            @forelse($staff as $member)
                            @php
                                $att = $attendances->get($member->id);
                                $status = $att ? $att->status : 'present';
                                $clockInTime = $att && $att->clock_in ? \Carbon\Carbon::parse($att->clock_in)->format('H:i') : '';
                                $clockOutTime = $att && $att->clock_out ? \Carbon\Carbon::parse($att->clock_out)->format('H:i') : '';
                                $hours = $att ? $att->hours_worked : 0;
                            @endphp
                            <tr style="border-bottom: 1px solid var(--s-border); transition: background 0.15s;" onmouseover="this.style.background='var(--s-bg-light)'" onmouseout="this.style.background=''">
                                <td style="padding: 12px 14px;">
                                    <div style="font-weight: 700; color: var(--s-text-primary);">{{ $member->name }}</div>
                                    <span class="s-badge s-badge-gray" style="font-size: 9px; padding: 1px 5px; text-transform: uppercase; font-weight: 800;">
                                        {{ $member->position }}
                                    </span>
                                </td>
                                <td style="padding: 12px 14px;">
                                    <select name="attendance[{{ $member->id }}][status]" class="s-input" style="height: 32px; padding: 2px 6px; font-size: 12px;">
                                        <option value="present" @selected($status === 'present')>Presente</option>
                                        <option value="late" @selected($status === 'late')>Tardanza</option>
                                        <option value="absent" @selected($status === 'absent')>Falta</option>
                                        <option value="holiday" @selected($status === 'holiday')>Feriado / Libre</option>
                                        <option value="sick_leave" @selected($status === 'sick_leave')>Licencia Médica</option>
                                    </select>
                                </td>
                                <td style="padding: 12px 14px;">
                                    <input type="time" name="attendance[{{ $member->id }}][clock_in]" value="{{ $clockInTime }}" class="s-input" style="height: 32px; padding: 2px 6px; font-size: 12px;">
                                </td>
                                <td style="padding: 12px 14px;">
                                    <input type="time" name="attendance[{{ $member->id }}][clock_out]" value="{{ $clockOutTime }}" class="s-input" style="height: 32px; padding: 2px 6px; font-size: 12px;">
                                </td>
                                <td style="padding: 12px 14px; text-align: center; font-weight: 700; color: var(--s-text-secondary);">
                                    {{ number_format($hours, 1) }}
                                </td>
                                <td style="padding: 12px 14px;">
                                    <input type="text" name="attendance[{{ $member->id }}][notes]" value="{{ $att ? $att->notes : '' }}" class="s-input" placeholder="Opcional..." style="height: 32px; padding: 2px 8px; font-size: 12px;">
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px 10px; color: var(--s-text-muted);">
                                    <i class="las la-user-slash" style="font-size: 48px; display: block; margin-bottom: 12px; color: var(--s-border);"></i>
                                    No hay personal activo registrado para registrar asistencia.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($staff->isNotEmpty())
                <div style="text-align: right;">
                    <button type="submit" class="s-btn s-btn-primary" style="padding: 10px 24px;">
                        <i class="las la-save" style="font-size: 18px;"></i> Guardar Asistencias de la Fecha
                    </button>
                </div>
                @endif
            </form>
        </div>

    </div>
</div>
@endsection
