<div class="cocina-card">
    <h3>Pedido #{{ $pedido->id }}</h3>

    <span class="estado estado-{{ $pedido->estado }}">
        {{ ucfirst($pedido->estado) }}
    </span>

    <ul>
        @foreach($pedido->items as $item)
            <li>{{ $item->nombre }} x {{ $item->cantidad }}</li>
        @endforeach
    </ul>

    <form action="{{ route('cocina.marcar-listo', $pedido->id) }}" method="POST">
        @csrf
        <button class="btn-cocina">Marcar como listo ✅</button>
    </form>
</div>
