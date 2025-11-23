@extends('layouts.admin')

@section('content')
<h1 class="title">Pedido #{{ $pedido->id }}</h1>

<div class="info-box">
    <p><b>Cliente:</b> {{ $pedido->cliente->nombres ?? 'Invitado' }}</p>
    <p><b>Mesa:</b> {{ $pedido->mesa ?? '-' }}</p>
    <p><b>Total:</b> ${{ number_format($pedido->total, 0, ',', '.') }}</p>
</div>

<h3>Productos</h3>
<ul class="productos">
@foreach($pedido->detalles as $d)
<li>
    {{ $d->cantidad }} × <b>{{ $d->menuItem->nombre }}</b>
    <span>${{ number_format($d->importe, 0, ',', '.') }}</span>
</li>
@endforeach
</ul>

<form action="{{ route('admin.pedidos.estado', $pedido->id) }}" method="POST">
@csrf
<select name="estado" class="select">
    <option {{ $pedido->estado == 'pendiente' ? 'selected' : '' }}>pendiente</option>
    <option {{ $pedido->estado == 'preparando' ? 'selected' : '' }}>preparando</option>
    <option {{ $pedido->estado == 'entregado' ? 'selected' : '' }}>entregado</option>
    <option {{ $pedido->estado == 'cancelado' ? 'selected' : '' }}>cancelado</option>
</select>
<button class="btn">Actualizar Estado</button>
</form>

@endsection

