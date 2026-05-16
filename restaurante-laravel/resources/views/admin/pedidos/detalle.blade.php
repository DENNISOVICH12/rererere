@extends('layouts.admin')

@section('content')
<h1 class="title">Pedido #{{ $pedido->id }}</h1>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="info-box">
    <p><b>Cliente:</b>
        {{ trim(($pedido->cliente->nombres ?? '') . ' ' . ($pedido->cliente->apellidos ?? '')) ?: 'Invitado' }}
    </p>
    <p><b>Mesa:</b> {{ $pedido->mesa?->numero ?? '-' }}</p>
    <p><b>Estado actual:</b> <span class="estado {{ strtolower($pedido->estado) }}">{{ ucfirst($pedido->estado) }}</span></p>
    <p><b>Total:</b> ${{ number_format($pedido->total, 0, ',', '.') }}</p>
    <p><b>Fecha:</b> {{ $pedido->created_at->format('d/m/Y H:i') }}</p>
</div>

<h3>Productos</h3>
<ul class="productos">
@foreach($pedido->detalles as $d)
<li>
    {{ $d->cantidad }} × <b>{{ $d->menuItem->nombre ?? "Ítem #{$d->menu_item_id}" }}</b>
    <span>${{ number_format($d->importe, 0, ',', '.') }}</span>
    @if($d->nota)
        <small class="nota">📝 {{ $d->nota }}</small>
    @endif
</li>
@endforeach
</ul>

<h3>Cambiar estado</h3>
<form action="{{ route('admin.pedidos.estado', $pedido->id) }}" method="POST">
@csrf
<div class="form-row">
    <select name="estado" class="select">
        @foreach($estados as $e)
        <option value="{{ $e }}" {{ $pedido->estado === $e ? 'selected' : '' }}>{{ ucfirst($e) }}</option>
        @endforeach
    </select>
    <button class="btn">Actualizar Estado</button>
</div>

<div class="form-group" style="margin-top:12px;">
    <label><b>Justificación del cambio</b> <span style="color:#9ca3af;font-size:.85rem;">(recomendado para auditoría)</span></label>
    <textarea name="justificacion" class="textarea" rows="3"
        placeholder="Ej: El cliente solicitó cancelar porque cambió de opinión..."
        maxlength="500"></textarea>
</div>
</form>

<div style="margin-top:16px;">
    <a href="{{ route('admin.pedidos.index') }}" class="btn btn-secondary">← Volver a pedidos</a>
</div>

@endsection