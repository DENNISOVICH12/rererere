<h2>Factura</h2>

<p>Cliente: {{ $clienteId }}</p>

<table border="1" width="100%">
    <tr>
        <th>Producto</th>
        <th>Cantidad</th>
        <th>Precio</th>
    </tr>

    @foreach($pedidos as $pedido)
        @foreach($pedido->detalle as $item)
            <tr>
                <td>{{ $item->menuItem->nombre ?? 'Producto' }}</td>
                <td>{{ $item->cantidad }}</td>
                <td>{{ $item->importe }}</td>
            </tr>
        @endforeach
    @endforeach
</table>

<h3>Total: ${{ $total }}</h3>