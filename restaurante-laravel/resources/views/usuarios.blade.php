@extends('layouts.admin')

@section('content')
<style>
/* ── Usando exactamente las variables del design system del admin ── */
.pm-wrap {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* HEADER */
.pm-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}
.pm-header h1 {
  margin: 0 0 4px;
  font-size: clamp(1.4rem, 2.2vw, 1.9rem);
  font-weight: 700;
  color: var(--ds-text);
}
.pm-header p { color: var(--ds-muted); font-size: .9rem; }
.pm-header-right { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

/* TABS */
.pm-tabs {
  display: flex;
  gap: 4px;
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: var(--ds-radius-sm);
  padding: 4px;
}
.pm-tab {
  padding: 8px 18px;
  border-radius: 10px;
  border: none;
  background: transparent;
  color: var(--ds-muted);
  font-family: var(--ds-font-sans);
  font-size: .88rem;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--ds-transition);
  white-space: nowrap;
}
.pm-tab.active {
  background: rgba(156, 32, 48, 0.28);
  border: 1px solid rgba(156, 32, 48, 0.55);
  color: #ffd7aa;
  box-shadow: 0 4px 14px rgba(156, 32, 48, .22);
}

/* BTN NUEVO */
.btn-nuevo {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: var(--ds-radius-sm);
  border: none;
  background: linear-gradient(145deg, var(--ds-primary), #7a1522);
  color: #fff;
  font-family: var(--ds-font-sans);
  font-size: .9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--ds-transition);
  box-shadow: 0 8px 20px rgba(156, 32, 48, .32);
}
.btn-nuevo:hover { transform: scale(1.02); box-shadow: 0 12px 28px rgba(156, 32, 48, .44); }

/* KPI STRIP */
.pm-kpis {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}
.pm-kpi {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: var(--ds-radius-md);
  padding: 20px 22px;
  backdrop-filter: blur(14px);
  box-shadow: var(--ds-shadow);
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.pm-kpi-icon { font-size: 1.3rem; }
.pm-kpi-label {
  font-size: .75rem;
  font-weight: 600;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: var(--ds-muted);
}
.pm-kpi-val {
  font-size: 2rem;
  font-weight: 700;
  line-height: 1;
  color: var(--ds-accent);
}
.pm-kpi-val.green { color: #86efac; }
.pm-kpi-val.red { color: #fca5a5; }
.pm-kpi-val.muted { color: var(--ds-muted); font-size: 1.4rem; }

/* TABLE CARD */
.pm-table-card {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: var(--ds-radius-md);
  backdrop-filter: blur(14px);
  box-shadow: var(--ds-shadow);
  overflow: hidden;
}
.pm-table-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 22px;
  border-bottom: 1px solid var(--ds-border);
  flex-wrap: wrap;
  gap: 10px;
}
.pm-table-head h2 { margin: 0; font-size: 1.1rem; font-weight: 600; color: var(--ds-text); }
.pm-table-head .meta { color: var(--ds-muted); font-size: .82rem; margin-top: 3px; }

.pm-table { width: 100%; border-collapse: collapse; }
.pm-table th {
  color: var(--ds-accent);
  font-size: .75rem;
  letter-spacing: .05em;
  text-transform: uppercase;
  padding: 12px 16px;
  border-bottom: 1px solid var(--ds-border);
  text-align: left;
  background: rgba(15, 23, 42, .5);
}
.pm-table td {
  padding: 16px;
  border-bottom: 1px solid rgba(148, 163, 184, .12);
  vertical-align: middle;
}
.pm-table tr:last-child td { border-bottom: none; }
.pm-table tbody tr:hover td { background: rgba(148, 163, 184, .06); }

.u-name { font-weight: 600; font-size: .95rem; color: var(--ds-text); }
.u-full { color: var(--ds-muted); font-size: .82rem; margin-top: 2px; }
.u-email { color: rgba(148, 163, 184, .7); font-size: .78rem; }

/* BADGES */
.pm-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 11px;
  border-radius: 999px;
  font-size: .78rem;
  font-weight: 600;
}
.pm-badge.on { background: rgba(34, 197, 94, .16); border: 1px solid rgba(34, 197, 94, .38); color: #86efac; }
.pm-badge.off { background: rgba(239, 68, 68, .14); border: 1px solid rgba(239, 68, 68, .35); color: #fca5a5; }
.pm-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
.pm-dot.on { background: #86efac; }
.pm-dot.off { background: #fca5a5; }

.pm-role {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 8px;
  font-size: .78rem;
  font-weight: 600;
  background: rgba(156, 32, 48, .18);
  border: 1px solid rgba(255, 215, 170, .2);
  color: var(--ds-accent);
}

.pm-date { font-size: .84rem; color: var(--ds-muted); }
.pm-date-ago { font-size: .74rem; color: rgba(148, 163, 184, .6); }

/* ACTIONS */
.pm-acts { display: flex; gap: 8px; flex-wrap: wrap; }
.pm-btn-edit {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border-radius: 10px;
  border: 1px solid rgba(255, 215, 170, .28);
  background: rgba(15, 23, 42, .5);
  color: #ffe7c8;
  font-size: .82rem;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--ds-transition);
  font-family: var(--ds-font-sans);
}
.pm-btn-edit:hover { border-color: rgba(255, 215, 170, .58); background: rgba(255, 215, 170, .08); }
.pm-btn-dis {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border-radius: 10px;
  border: 1px solid rgba(239, 68, 68, .35);
  background: rgba(127, 29, 29, .14);
  color: #fca5a5;
  font-size: .82rem;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--ds-transition);
  font-family: var(--ds-font-sans);
}
.pm-btn-dis:hover { background: rgba(153, 27, 27, .26); border-color: rgba(239, 68, 68, .55); }
.pm-btn-en {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border-radius: 10px;
  border: 1px solid rgba(34, 197, 94, .35);
  background: rgba(6, 78, 59, .16);
  color: #86efac;
  font-size: .82rem;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--ds-transition);
  font-family: var(--ds-font-sans);
}
.pm-btn-en:hover { background: rgba(6, 78, 59, .28); }

/* EDIT DRAWER */
.pm-edit {
  display: none;
  background: rgba(2, 6, 23, .6);
  border: 1px solid var(--ds-border);
  border-radius: var(--ds-radius-sm);
  padding: 18px;
  margin-top: 10px;
}
.pm-edit.open { display: block; animation: pmFade .18s ease; }
@keyframes pmFade { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
.pm-edit-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.pm-ef { display: flex; flex-direction: column; gap: 5px; }
.pm-ef label { color: var(--ds-accent); font-size: .8rem; font-weight: 500; }
.pm-ef input, .pm-ef select {
  padding: 9px 12px;
  border-radius: var(--ds-radius-sm);
  border: 1px solid var(--ds-border);
  background: rgba(15, 23, 42, .65);
  color: var(--ds-text);
  font-family: var(--ds-font-sans);
  font-size: .88rem;
}
.pm-ef input:focus, .pm-ef select:focus {
  outline: none;
  border-color: rgba(255, 215, 170, .7);
  box-shadow: 0 0 0 3px rgba(156, 32, 48, .2);
}
.pm-edit-btns { display: flex; gap: 8px; margin-top: 14px; }

/* MODAL */
.pm-overlay {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, .82);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 500;
  padding: 20px;
  opacity: 0;
  pointer-events: none;
  transition: opacity .24s ease;
}
.pm-overlay.open { opacity: 1; pointer-events: all; }
.pm-modal {
  background: rgba(15, 23, 42, .96);
  border: 1px solid var(--ds-border);
  border-radius: var(--ds-radius-lg);
  width: min(540px, 100%);
  box-shadow: 0 32px 80px rgba(2, 6, 23, .8);
  transform: scale(.96) translateY(10px);
  transition: transform .24s ease;
  overflow: hidden;
  backdrop-filter: blur(20px);
}
.pm-overlay.open .pm-modal { transform: scale(1) translateY(0); }
.pm-modal-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 22px 24px 18px;
  border-bottom: 1px solid var(--ds-border);
}
.pm-modal-head h3 { margin: 0; font-size: 1.2rem; font-weight: 700; color: var(--ds-text); }
.pm-modal-close {
  width: 34px; height: 34px;
  border-radius: 10px;
  border: 1px solid var(--ds-border);
  background: rgba(15, 23, 42, .5);
  color: var(--ds-muted);
  font-size: 1rem;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all var(--ds-transition);
}
.pm-modal-close:hover { color: var(--ds-text); border-color: var(--ds-border); background: rgba(148, 163, 184, .12); }
.pm-modal-body { padding: 22px 24px; display: flex; flex-direction: column; gap: 14px; }
.pm-mf { display: flex; flex-direction: column; gap: 6px; }
.pm-mf label { color: var(--ds-accent); font-size: .84rem; font-weight: 500; }
.pm-mf input, .pm-mf select {
  padding: 11px 14px;
  border-radius: var(--ds-radius-sm);
  border: 1px solid var(--ds-border);
  background: rgba(15, 23, 42, .65);
  color: var(--ds-text);
  font-family: var(--ds-font-sans);
  font-size: .95rem;
  transition: all var(--ds-transition);
}
.pm-mf input:focus, .pm-mf select:focus {
  outline: none;
  border-color: rgba(255, 215, 170, .7);
  box-shadow: 0 0 0 3px rgba(156, 32, 48, .2);
}
.pm-mf-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.pm-toggle {
  display: flex; align-items: center; gap: 10px;
  padding: 11px 14px;
  border-radius: var(--ds-radius-sm);
  border: 1px solid var(--ds-border);
  background: rgba(15, 23, 42, .45);
}
.pm-toggle label { color: var(--ds-text); font-size: .9rem; font-weight: 500; cursor: pointer; }
.pm-modal-foot {
  padding: 14px 24px 22px;
  display: flex; gap: 12px;
  border-top: 1px solid var(--ds-border);
}
.pm-btn-cancel {
  flex: 1; padding: 11px;
  border-radius: var(--ds-radius-sm);
  border: 1px solid var(--ds-border);
  background: rgba(15, 23, 42, .5);
  color: var(--ds-muted);
  font-family: var(--ds-font-sans);
  font-size: .9rem; font-weight: 600;
  cursor: pointer; transition: all var(--ds-transition);
}
.pm-btn-cancel:hover { color: var(--ds-text); }
.pm-btn-submit {
  flex: 2; padding: 11px;
  border-radius: var(--ds-radius-sm);
  border: none;
  background: linear-gradient(145deg, var(--ds-primary), #7a1522);
  color: #fff;
  font-family: var(--ds-font-sans);
  font-size: .95rem; font-weight: 600;
  cursor: pointer; transition: all var(--ds-transition);
  box-shadow: 0 8px 20px rgba(156, 32, 48, .32);
}
.pm-btn-submit:hover { transform: scale(1.02); box-shadow: 0 12px 28px rgba(156, 32, 48, .44); }

/* TIMELINE */
.pm-tl-header {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: var(--ds-radius-md);
  padding: 16px 20px;
  display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
  backdrop-filter: blur(14px);
  box-shadow: var(--ds-shadow);
}
.pm-mn { display: flex; align-items: center; gap: 10px; }
.pm-mn-lbl { font-size: 1rem; font-weight: 600; color: var(--ds-text); min-width: 160px; text-align: center; }
.pm-mn-btn {
  width: 36px; height: 36px;
  border-radius: 10px;
  border: 1px solid var(--ds-border);
  background: rgba(15, 23, 42, .5);
  color: var(--ds-text); font-size: 1rem;
  cursor: pointer; display: flex; align-items: center; justify-content: center;
  transition: all var(--ds-transition);
}
.pm-mn-btn:hover { border-color: rgba(255, 215, 170, .45); color: var(--ds-accent); }
.pm-fchips { display: flex; gap: 8px; }
.pm-fchip {
  padding: 6px 14px; border-radius: 999px;
  border: 1px solid var(--ds-border);
  background: transparent;
  color: var(--ds-muted); font-size: .82rem; font-weight: 600;
  cursor: pointer; font-family: var(--ds-font-sans); transition: all var(--ds-transition);
}
.pm-fchip.active.fc-all { border-color: rgba(255, 215, 170, .5); color: var(--ds-accent); background: rgba(156, 32, 48, .14); }
.pm-fchip.active.fc-on { border-color: rgba(34, 197, 94, .45); color: #86efac; background: rgba(34, 197, 94, .1); }
.pm-fchip.active.fc-off { border-color: rgba(239, 68, 68, .45); color: #fca5a5; background: rgba(239, 68, 68, .1); }

.pm-tl-grid { display: flex; flex-direction: column; gap: 12px; }
.pm-tl-card {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: var(--ds-radius-md);
  overflow: hidden;
  backdrop-filter: blur(14px);
  box-shadow: var(--ds-shadow);
  transition: all var(--ds-transition);
}
.pm-tl-card:hover { border-color: rgba(148, 163, 184, .4); transform: translateY(-1px); }
.pm-tl-card.on { border-left: 3px solid rgba(34, 197, 94, .6); }
.pm-tl-card.off { border-left: 3px solid rgba(239, 68, 68, .5); }
.pm-tl-top { display: flex; align-items: center; gap: 16px; padding: 18px 22px; }
.pm-tl-av {
  width: 48px; height: 48px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; font-weight: 700; flex-shrink: 0;
}
.pm-tl-av.av-on { background: rgba(34, 197, 94, .14); color: #86efac; }
.pm-tl-av.av-off { background: rgba(239, 68, 68, .12); color: #fca5a5; }
.pm-tl-info { flex: 1; }
.pm-tl-name { font-weight: 700; font-size: 1rem; color: var(--ds-text); }
.pm-tl-sub { color: var(--ds-muted); font-size: .82rem; margin-top: 2px; }
.pm-tl-stats {
  display: grid; grid-template-columns: repeat(4, 1fr);
  border-top: 1px solid var(--ds-border);
}
.pm-tl-stat {
  padding: 14px 20px;
  border-right: 1px solid var(--ds-border);
}
.pm-tl-stat:last-child { border-right: none; }
.pm-tl-lbl { font-size: .72rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--ds-muted); margin-bottom: 5px; }
.pm-tl-val { font-size: .95rem; font-weight: 600; color: var(--ds-text); }
.pm-tl-val.accent { color: var(--ds-accent); }
.pm-tl-val.green { color: #86efac; }
.pm-tl-val.muted { color: var(--ds-muted); }
.pm-tl-bar { padding: 10px 22px 16px; border-top: 1px solid var(--ds-border); }
.pm-tl-bar-lbl { font-size: .72rem; color: var(--ds-muted); letter-spacing: .05em; text-transform: uppercase; margin-bottom: 7px; }
.pm-tl-track { height: 6px; background: rgba(15, 23, 42, .8); border-radius: 999px; overflow: hidden; }
.pm-tl-fill { height: 100%; border-radius: 999px; transition: width .6s ease; }
.fill-on { background: linear-gradient(90deg, #22c55e, #16a34a); }
.fill-off { background: linear-gradient(90deg, #ef4444, #dc2626); }
.pm-empty {
  text-align: center; padding: 50px 20px;
  color: var(--ds-muted); font-size: .95rem;
  background: var(--ds-surface); border: 1px solid var(--ds-border);
  border-radius: var(--ds-radius-md);
  backdrop-filter: blur(14px);
}

@media (max-width: 1100px) { .pm-kpis { grid-template-columns: repeat(2,1fr); } .pm-tl-stats { grid-template-columns: repeat(2,1fr); } .pm-edit-grid { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 700px) { .pm-kpis { grid-template-columns: 1fr 1fr; } .pm-mf-row { grid-template-columns: 1fr; } .pm-tl-stats { grid-template-columns: 1fr 1fr; } .pm-edit-grid { grid-template-columns: 1fr 1fr; } }
@media (max-width: 480px) { .pm-kpis { grid-template-columns: 1fr; } }
</style>

<div class="pm-wrap">

  {{-- HEADER --}}
  <div class="pm-header">
    <div>
      <h1>👥 Personal del Restaurante</h1>
      <p>Gestiona empleados, historial de trabajo y estado del equipo.</p>
    </div>
    <div class="pm-header-right">
      <div class="pm-tabs">
        <button class="pm-tab active" id="tabU" onclick="switchTab('u')">👤 Usuarios</button>
        <button class="pm-tab" id="tabT" onclick="switchTab('t')">📅 Historial</button>
      </div>
      <button class="btn-nuevo" onclick="openModal()">＋ Nuevo empleado</button>
    </div>
  </div>

  {{-- ALERTS --}}
  @if(session('status'))
    <div class="alert alert-success">✓ {{ session('status') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-error">
      <strong>Revisa los datos:</strong>
      <ul style="margin:6px 0 0 16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  {{-- KPI --}}
  <div class="pm-kpis">
    <div class="pm-kpi">
      <span class="pm-kpi-icon">👥</span>
      <span class="pm-kpi-label">Total empleados</span>
      <span class="pm-kpi-val">{{ $todos->count() }}</span>
    </div>
    <div class="pm-kpi">
      <span class="pm-kpi-icon">✅</span>
      <span class="pm-kpi-label">Activos ahora</span>
      <span class="pm-kpi-val green">{{ $todos->where('activo', true)->count() }}</span>
    </div>
    <div class="pm-kpi">
      <span class="pm-kpi-icon">⏸</span>
      <span class="pm-kpi-label">Dados de baja</span>
      <span class="pm-kpi-val red">{{ $todos->where('activo', false)->count() }}</span>
    </div>
    <div class="pm-kpi">
      <span class="pm-kpi-icon">⏱</span>
      <span class="pm-kpi-label">Promedio antigüedad</span>
      <span class="pm-kpi-val muted">
        @php
          $cf = $todos->filter(fn($u) => $u->fecha_ingreso);
          $pm = $cf->count() > 0 ? round($cf->avg(fn($u) => \Carbon\Carbon::parse($u->fecha_ingreso)->diffInDays(now())) / 30) : 0;
        @endphp
        {{ $pm > 0 ? $pm.' meses' : 'N/A' }}
      </span>
    </div>
  </div>

  {{-- TAB USUARIOS --}}
  <div id="tabUContent">
    <div class="pm-table-card">
      <div class="pm-table-head">
        <div>
          <h2>Equipo registrado</h2>
          <p class="meta">{{ $usuarios->total() }} empleados &nbsp;·&nbsp; <span style="color:#86efac;">{{ $usuarios->filter(fn($u)=>$u->activo)->count() }} activos</span> &nbsp;·&nbsp; <span style="color:#fca5a5;">{{ $usuarios->filter(fn($u)=>!$u->activo)->count() }} inactivos</span></p>
        </div>
      </div>
      <div style="overflow-x:auto;">
        <table class="pm-table">
          <thead>
            <tr>
              <th>Empleado</th>
              <th>Rol</th>
              <th>Estado</th>
              <th>Fecha ingreso</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse($usuarios as $u)
            <tr>
              <td>
                <div class="u-name">{{ $u->usuario }}</div>
                <div class="u-full">{{ trim($u->nombre.' '.$u->apellido) }}</div>
                <div class="u-email">{{ $u->correo }}</div>
              </td>
              <td><span class="pm-role">{{ $roles[$u->rol] ?? $u->rol }}</span></td>
              <td>
                <span class="pm-badge {{ $u->activo ? 'on' : 'off' }}">
                  <span class="pm-dot {{ $u->activo ? 'on' : 'off' }}"></span>
                  {{ $u->activo ? 'Activo' : 'Inactivo' }}
                </span>
                @if(!$u->activo && $u->fecha_salida)
                  <div style="font-size:.74rem;color:var(--ds-muted);margin-top:4px;">Salida: {{ \Carbon\Carbon::parse($u->fecha_salida)->format('d/m/Y') }}</div>
                @endif
              </td>
              <td>
                @if($u->fecha_ingreso)
                  <div class="pm-date">{{ \Carbon\Carbon::parse($u->fecha_ingreso)->format('d/m/Y') }}</div>
                  <div class="pm-date-ago">hace {{ \Carbon\Carbon::parse($u->fecha_ingreso)->diffForHumans(['parts'=>1,'short'=>true]) }}</div>
                @else
                  <span style="color:var(--ds-muted);">—</span>
                @endif
              </td>
              <td>
                <div class="pm-acts">
                  <button class="pm-btn-edit" onclick="toggleEdit({{ $u->id }})">✏ Editar</button>
                  @if($u->activo)
                    <form method="POST" action="{{ route('usuarios.update', $u->id) }}" style="display:inline;"
                      data-confirm-title="Deshabilitar empleado"
                      data-confirm-message="¿Deshabilitar a {{ $u->usuario }}? Podrás reactivarlo cuando quieras."
                      data-confirm-accept="Deshabilitar" data-confirm-cancel="Cancelar">
                      @csrf @method('PUT')
                      <input type="hidden" name="activo" value="0">
                      <input type="hidden" name="fecha_salida" value="{{ date('Y-m-d') }}">
                      <button type="submit" class="pm-btn-dis">⏸ Deshabilitar</button>
                    </form>
                  @else
                    <form method="POST" action="{{ route('usuarios.update', $u->id) }}" style="display:inline;"
                      data-confirm-title="Reactivar empleado"
                      data-confirm-message="¿Reactivar a {{ $u->usuario }}?"
                      data-confirm-accept="Reactivar" data-confirm-cancel="Cancelar">
                      @csrf @method('PUT')
                      <input type="hidden" name="activo" value="1">
                      <input type="hidden" name="fecha_salida" value="">
                      <button type="submit" class="pm-btn-en">▶ Reactivar</button>
                    </form>
                  @endif
                </div>
                <div class="pm-edit" id="edit-{{ $u->id }}">
                  <form method="POST" action="{{ route('usuarios.update', $u->id) }}">
                    @csrf @method('PUT')
                    <div class="pm-edit-grid">
                      <div class="pm-ef"><label>Usuario</label><input name="usuario" value="{{ $u->usuario }}" required></div>
                      <div class="pm-ef"><label>Correo</label><input name="correo" type="email" value="{{ $u->correo }}" required></div>
                      <div class="pm-ef"><label>Nombre</label><input name="nombre" value="{{ $u->nombre }}"></div>
                      <div class="pm-ef"><label>Apellido</label><input name="apellido" value="{{ $u->apellido }}"></div>
                      <div class="pm-ef"><label>Rol</label><select name="rol" required>@foreach($roles as $k=>$l)<option value="{{ $k }}" {{ $u->rol===$k?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
                      <div class="pm-ef"><label>Contraseña</label><input name="password" type="password" placeholder="(sin cambios)"></div>
                      <div class="pm-ef"><label>Fecha ingreso</label><input name="fecha_ingreso" type="date" value="{{ $u->fecha_ingreso }}"></div>
                      <div class="pm-ef"><label>Fecha salida</label><input name="fecha_salida" type="date" value="{{ $u->fecha_salida }}"></div>
                    </div>
                    <div class="pm-edit-btns">
                      <button type="submit" class="btn btn-primary" style="padding:9px 20px;">Guardar cambios</button>
                      <button type="button" class="btn btn-secondary" onclick="toggleEdit({{ $u->id }})" style="padding:9px 16px;">Cancelar</button>
                    </div>
                  </form>
                </div>
              </td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;color:var(--ds-muted);padding:40px;">Sin empleados registrados.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($usuarios->hasPages())
        <div style="padding:14px 20px;border-top:1px solid var(--ds-border);">{{ $usuarios->links() }}</div>
      @endif
    </div>
  </div>

  {{-- TAB TIMELINE --}}
  <div id="tabTContent" style="display:none;">
    <div style="display:flex;flex-direction:column;gap:16px;">
      <div class="pm-tl-header">
        <div class="pm-mn">
          <button class="pm-mn-btn" onclick="changeMonth(-1)">‹</button>
          <span class="pm-mn-lbl" id="mnLbl"></span>
          <button class="pm-mn-btn" onclick="changeMonth(1)">›</button>
        </div>
        <div class="pm-fchips">
          <button class="pm-fchip fc-all active" onclick="setFilter('all',this)">Todos</button>
          <button class="pm-fchip fc-on" onclick="setFilter('on',this)">● Activos</button>
          <button class="pm-fchip fc-off" onclick="setFilter('off',this)">● Inactivos</button>
        </div>
      </div>
      <div class="pm-tl-grid" id="tlGrid"></div>
    </div>
  </div>

</div>

{{-- MODAL --}}
<div class="pm-overlay" id="pmModal" onclick="handleOverlayClick(event)">
  <div class="pm-modal">
    <div class="pm-modal-head">
      <h3>Agregar nuevo empleado</h3>
      <button class="pm-modal-close" onclick="closeModal()">✕</button>
    </div>
    <form method="POST" action="{{ route('usuarios.store') }}">
      @csrf
      <div class="pm-modal-body">
        <div class="pm-mf"><label>Usuario</label><input name="usuario" value="{{ old('usuario') }}" required placeholder="ej: ana.torres"></div>
        <div class="pm-mf-row">
          <div class="pm-mf"><label>Nombre</label><input name="nombre" value="{{ old('nombre') }}" placeholder="Ana"></div>
          <div class="pm-mf"><label>Apellido</label><input name="apellido" value="{{ old('apellido') }}" placeholder="Torres"></div>
        </div>
        <div class="pm-mf"><label>Correo electrónico</label><input name="correo" type="email" value="{{ old('correo') }}" required placeholder="ana@restaurante.com"></div>
        <div class="pm-mf-row">
          <div class="pm-mf"><label>Rol</label><select name="rol" required><option value="" disabled selected>Selecciona un rol</option>@foreach($roles as $k=>$l)<option value="{{ $k }}" {{ old('rol')===$k?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
          <div class="pm-mf"><label>Contraseña</label><input name="password" type="password" required placeholder="••••••••"></div>
        </div>
        <div class="pm-mf"><label>Fecha de ingreso</label><input name="fecha_ingreso" type="date" value="{{ old('fecha_ingreso', date('Y-m-d')) }}"></div>
        <div class="pm-toggle">
          <input type="hidden" name="activo" value="0">
          <input type="checkbox" id="activo_chk" name="activo" value="1" checked style="width:16px;height:16px;cursor:pointer;accent-color:var(--ds-primary);">
          <label for="activo_chk">Empleado activo desde el inicio</label>
        </div>
      </div>
      <div class="pm-modal-foot">
        <button type="button" class="pm-btn-cancel" onclick="closeModal()">Cancelar</button>
        <button type="submit" class="pm-btn-submit">＋ Agregar empleado</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
function switchTab(t) {
  document.getElementById('tabUContent').style.display = t==='u'?'':'none';
  document.getElementById('tabTContent').style.display = t==='t'?'':'none';
  document.getElementById('tabU').classList.toggle('active', t==='u');
  document.getElementById('tabT').classList.toggle('active', t==='t');
  if(t==='t') renderTl();
}
function openModal() { document.getElementById('pmModal').classList.add('open'); document.body.style.overflow='hidden'; }
function closeModal() { document.getElementById('pmModal').classList.remove('open'); document.body.style.overflow=''; }
function handleOverlayClick(e) { if(e.target===document.getElementById('pmModal')) closeModal(); }
document.addEventListener('keydown', e => { if(e.key==='Escape') closeModal(); });
@if($errors->any()) document.addEventListener('DOMContentLoaded', () => openModal()); @endif
function toggleEdit(id) { document.getElementById('edit-'+id).classList.toggle('open'); }
document.querySelectorAll('form[data-confirm-message]').forEach(f => {
  f.addEventListener('submit', async e => {
    e.preventDefault();
    const ok = await window.showConfirm(f.dataset.confirmMessage, {
      title: f.dataset.confirmTitle||'Confirmar',
      confirmText: f.dataset.confirmAccept||'Aceptar',
      cancelText: f.dataset.confirmCancel||'Cancelar'
    });
    if(ok) f.submit();
  });
});

const USERS = @json($todosJson);
const RLBLS = @json($roles);
const MNS = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
let cY=new Date().getFullYear(), cM=new Date().getMonth(), cF='all';

function changeMonth(d) { cM+=d; if(cM<0){cM=11;cY--;} if(cM>11){cM=0;cY++;} renderTl(); }
function setFilter(f,btn) { cF=f; document.querySelectorAll('.pm-fchip').forEach(c=>c.classList.remove('active')); btn.classList.add('active'); renderTl(); }
function dur(i,s) {
  if(!i) return null;
  const d=Math.max(0,Math.floor((new Date(s||Date.now())-new Date(i))/86400000));
  if(d<30) return d+' día'+(d!==1?'s':'');
  const m=Math.floor(d/30);
  if(m<12) return m+' mes'+(m!==1?'es':'');
  const y=Math.floor(m/12),r=m%12;
  return y+' año'+(y!==1?'s':'')+(r?' '+r+'m':'');
}
function inMonth(u,y,m) {
  if(!u.fecha_ingreso) return false;
  const i=new Date(u.fecha_ingreso), s=u.fecha_salida?new Date(u.fecha_salida):new Date(9999,0,1);
  return i<=new Date(y,m+1,0) && s>=new Date(y,m,1);
}
function renderTl() {
  document.getElementById('mnLbl').textContent = MNS[cM]+' '+cY;
  let list=USERS.filter(u=>inMonth(u,cY,cM));
  if(cF==='on') list=list.filter(u=>u.activo);
  if(cF==='off') list=list.filter(u=>!u.activo);
  list.sort((a,b)=>(b.activo-a.activo)||((a.fecha_ingreso||'').localeCompare(b.fecha_ingreso||'')));
  const g=document.getElementById('tlGrid');
  if(!list.length){g.innerHTML='<div class="pm-empty">Sin empleados para este mes con el filtro seleccionado.</div>';return;}
  const fd=d=>d?new Date(d).toLocaleDateString('es-CO',{day:'2-digit',month:'short',year:'numeric'}):'—';
  g.innerHTML=list.map(u=>{
    const init=(u.nombre||u.usuario).split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
    const d=dur(u.fecha_ingreso,u.activo?null:u.fecha_salida);
    let pct=0;
    if(u.fecha_ingreso){
      const s=new Date(Math.max(new Date(u.fecha_ingreso),new Date(cY,0,1)));
      const e=u.fecha_salida?new Date(Math.min(new Date(u.fecha_salida),new Date(cY,11,31))):new Date();
      pct=Math.min(100,Math.max(0,Math.round((e-s)/86400000/365*100)));
    }
    return `<div class="pm-tl-card ${u.activo?'on':'off'}">
      <div class="pm-tl-top">
        <div class="pm-tl-av ${u.activo?'av-on':'av-off'}">${init}</div>
        <div class="pm-tl-info">
          <div class="pm-tl-name">${u.nombre||u.usuario}</div>
          <div class="pm-tl-sub">@${u.usuario} · ${RLBLS[u.rol]||u.rol}</div>
        </div>
        <span class="pm-badge ${u.activo?'on':'off'}"><span class="pm-dot ${u.activo?'on':'off'}"></span>${u.activo?'Activo':'Inactivo'}</span>
      </div>
      <div class="pm-tl-stats">
        <div class="pm-tl-stat"><div class="pm-tl-lbl">Fecha ingreso</div><div class="pm-tl-val">${fd(u.fecha_ingreso)}</div></div>
        <div class="pm-tl-stat"><div class="pm-tl-lbl">${u.activo?'Antigüedad':'Trabajó'}</div><div class="pm-tl-val ${u.activo?'green':''}">${d||'—'}</div></div>
        <div class="pm-tl-stat"><div class="pm-tl-lbl">Fecha salida</div><div class="pm-tl-val ${u.fecha_salida?'':'muted'}">${u.fecha_salida?fd(u.fecha_salida):(u.activo?'Sigue activo':'—')}</div></div>
        <div class="pm-tl-stat"><div class="pm-tl-lbl">Rol</div><div class="pm-tl-val accent">${RLBLS[u.rol]||u.rol}</div></div>
      </div>
      <div class="pm-tl-bar">
        <div class="pm-tl-bar-lbl">Tiempo en el restaurante este año · ${pct}%</div>
        <div class="pm-tl-track"><div class="pm-tl-fill ${u.activo?'fill-on':'fill-off'}" style="width:${pct}%"></div></div>
      </div>
    </div>`;
  }).join('');
}
</script>
@endpush
@endsection