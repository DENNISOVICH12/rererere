@php
  $serviceArea = 'bebida';
  $serviceAreaLabel = 'Barra';
@endphp

@include('cocina', [
  'serviceArea' => 'bebida',
  'serviceAreaLabel' => 'Barra'
])
