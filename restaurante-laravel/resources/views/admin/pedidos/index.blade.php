@extends('layouts.admin')

@section('content')
<h1 class="title">📦 Pedidos</h1>

<div class="table-wrapper">
<table class="table">
    <thead>
    <tr>
        <th>ID</th>
        <th>Cliente</th>
        <th>Mesa</th>
        <th>Total</th>
        <th>Estado</th>
        <th>Fecha</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    @foreach($pedidos as $p)
        <tr>
            <td>#{{ $p->id }}</td>
            <td>
                @if($pedido->cliente)
                    {{ $pedido->cliente->nombre }} {{ $pedido->cliente->apellido }}
                @else
                    Invitado
                @endif
            </td>
            <td>{{ $p->mesa ?? '-' }}</td>
            <td>${{ number_format($p->total, 0, ',', '.') }}</td>
            <td><span class="estado {{ strtolower($p->estado) }}">{{ ucfirst($p->estado) }}</span></td>
            <td>{{ $p->created_at->format('d/m/y H:i') }}</td>
            <td><a href="{{ route('admin.pedidos.detalle', $p->id) }}" class="btn">Ver</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>

@endsection
