@extends('layouts.admin')

@section('content')
<style>
.cfg-wrap { display: flex; flex-direction: column; gap: 24px; max-width: 860px; }
.cfg-header h1 { margin: 0 0 4px; font-size: clamp(1.4rem, 2vw, 1.9rem); font-weight: 700; }
.cfg-header p { color: var(--ds-muted); font-size: .9rem; margin: 0; }

.cfg-card {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: var(--ds-radius-md);
  backdrop-filter: blur(14px);
  box-shadow: var(--ds-shadow);
  overflow: hidden;
}
.cfg-card-head {
  display: flex; align-items: center; gap: 12px;
  padding: 18px 22px;
  border-bottom: 1px solid var(--ds-border);
  background: rgba(15,23,42,.5);
}
.cfg-card-head h2 { margin: 0; font-size: 1rem; font-weight: 600; color: var(--ds-text); }
.cfg-card-head p { margin: 2px 0 0; color: var(--ds-muted); font-size: .82rem; }
.cfg-icon {
  width: 38px; height: 38px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; flex-shrink: 0;
}
.cfg-icon.red { background: rgba(156,32,48,.2); border: 1px solid rgba(255,215,170,.15); }
.cfg-icon.blue { background: rgba(37,99,235,.18); border: 1px solid rgba(96,165,250,.2); }
.cfg-body { padding: 22px; display: flex; flex-direction: column; gap: 16px; }
.cfg-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.cfg-field { display: flex; flex-direction: column; gap: 6px; }
.cfg-field label { color: var(--ds-accent); font-size: .84rem; font-weight: 500; }
.cfg-field input, .cfg-field select {
  padding: 11px 14px;
  border-radius: var(--ds-radius-sm);
  border: 1px solid var(--ds-border);
  background: rgba(15,23,42,.65);
  color: var(--ds-text);
  font-family: var(--ds-font-sans);
  font-size: .95rem;
  transition: all var(--ds-transition);
}
.cfg-field input:focus, .cfg-field select:focus {
  outline: none;
  border-color: rgba(255,215,170,.7);
  box-shadow: 0 0 0 3px rgba(156,32,48,.2);
}
.cfg-field.full { grid-column: 1 / -1; }
.cfg-hint { color: var(--ds-muted); font-size: .8rem; margin: 0; }

/* WiFi preview */
.wifi-preview {
  background: rgba(2,6,23,.5);
  border: 1px solid var(--ds-border);
  border-radius: var(--ds-radius-sm);
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
}
.wifi-preview-icon { font-size: 1.4rem; }
.wifi-preview-info { flex: 1; }
.wifi-preview-ssid { font-weight: 600; font-size: .95rem; color: var(--ds-text); }
.wifi-preview-type { font-size: .78rem; color: var(--ds-muted); margin-top: 2px; }
.wifi-qr-preview {
  width: 64px; height: 64px;
  background: rgba(255,255,255,.06);
  border: 1px dashed rgba(255,215,170,.3);
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: .65rem; color: var(--ds-muted); text-align: center;
}

.cfg-footer { padding: 16px 22px; border-top: 1px solid var(--ds-border); display: flex; gap: 12px; justify-content: flex-end; }
</style>

<div class="cfg-wrap">
  <div class="cfg-header">
    <h1>⚙️ Configuración del Restaurante</h1>
    <p>Información general y configuración del WiFi para los códigos QR de mesa.</p>
  </div>

  @if(session('status'))
    <div class="alert alert-success">✓ {{ session('status') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-error">
      <ul style="margin:0;padding-left:16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  <form method="POST" action="{{ route('admin.config.update') }}">
    @csrf @method('PUT')

    {{-- INFO GENERAL --}}
    <div class="cfg-card" style="margin-bottom:16px;">
      <div class="cfg-card-head">
        <div class="cfg-icon red">🍽️</div>
        <div>
          <h2>Información general</h2>
          <p>Nombre, dirección y teléfono del restaurante.</p>
        </div>
      </div>
      <div class="cfg-body">
        <div class="cfg-grid">
          <div class="cfg-field full">
            <label>Nombre del restaurante</label>
            <input name="nombre" value="{{ old('nombre', $restaurant->nombre) }}" required placeholder="Ej: La Buena Mesa">
          </div>
          <div class="cfg-field">
            <label>Dirección</label>
            <input name="direccion" value="{{ old('direccion', $restaurant->direccion) }}" placeholder="Calle 123 #45-67">
          </div>
          <div class="cfg-field">
            <label>Teléfono</label>
            <input name="telefono" value="{{ old('telefono', $restaurant->telefono) }}" placeholder="+57 300 000 0000">
          </div>
        </div>
      </div>
    </div>

    {{-- WIFI CONFIG --}}
    <div class="cfg-card">
      <div class="cfg-card-head">
        <div class="cfg-icon blue">📶</div>
        <div>
          <h2>Configuración WiFi</h2>
          <p>Esta información se incluirá en el QR de WiFi de cada mesa.</p>
        </div>
      </div>
      <div class="cfg-body">
        <div class="cfg-grid">
          <div class="cfg-field">
            <label>Nombre de la red (SSID)</label>
            <input name="wifi_ssid" id="wifiSsid" value="{{ old('wifi_ssid', $restaurant->wifi_ssid) }}" placeholder="Ej: Restaurante_WiFi" oninput="updatePreview()">
          </div>
          <div class="cfg-field">
            <label>Contraseña del WiFi</label>
            <input name="wifi_password" id="wifiPass" type="password" value="{{ old('wifi_password', $restaurant->wifi_password) }}" placeholder="••••••••" oninput="updatePreview()">
          </div>
          <div class="cfg-field">
            <label>Tipo de seguridad</label>
            <select name="wifi_security" id="wifiSec" onchange="updatePreview()">
              <option value="WPA" {{ ($restaurant->wifi_security ?? 'WPA') === 'WPA' ? 'selected' : '' }}>WPA / WPA2 (recomendado)</option>
              <option value="WEP" {{ ($restaurant->wifi_security ?? '') === 'WEP' ? 'selected' : '' }}>WEP (antiguo)</option>
              <option value="nopass" {{ ($restaurant->wifi_security ?? '') === 'nopass' ? 'selected' : '' }}>Sin contraseña (abierta)</option>
            </select>
          </div>
        </div>

        <p class="cfg-hint">📱 El QR de WiFi es reconocido automáticamente por iOS 11+ y Android 10+ — el cliente solo escanea y se conecta sin escribir la contraseña.</p>

        <div class="wifi-preview" id="wifiPreview">
          <span class="wifi-preview-icon">📶</span>
          <div class="wifi-preview-info">
            <div class="wifi-preview-ssid" id="prevSsid">{{ $restaurant->wifi_ssid ?: 'Sin configurar' }}</div>
            <div class="wifi-preview-type" id="prevType">{{ $restaurant->wifi_security ?: 'WPA' }} · Toca para conectar</div>
          </div>
          <div class="wifi-qr-preview">QR<br>WiFi</div>
        </div>
      </div>
      <div class="cfg-footer">
        <button type="button" class="btn btn-ghost" onclick="history.back()">Cancelar</button>
        <button type="submit" class="btn btn-primary">💾 Guardar configuración</button>
      </div>
    </div>

  </form>
</div>

<script>
function updatePreview() {
  const ssid = document.getElementById('wifiSsid').value;
  const sec = document.getElementById('wifiSec').value;
  document.getElementById('prevSsid').textContent = ssid || 'Sin configurar';
  document.getElementById('prevType').textContent = sec + ' · Toca para conectar';
}
</script>
@endsection