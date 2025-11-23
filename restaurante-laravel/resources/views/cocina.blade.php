<!DOCTYPE html>    
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cocina - Pedidos</title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
body {
    margin: 0;
    padding: 0;
    background-image:
        linear-gradient(120deg, rgba(0,0,0,0.78), rgba(0,0,0,0.78)),
        url("https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1600&q=80");
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    color: white;
    font-family: 'Inter', sans-serif;
}
.menu-container { max-width: 1200px; margin: auto; padding: 40px 20px; }
.header { display: flex; justify-content: center; margin-bottom: 30px; }
.title { font-size: 42px; font-family: 'Playfair Display', serif; color: #F8ECE4; }
.filters { display: flex; gap: 12px; justify-content: center; margin-bottom: 35px; }
.filter-btn { padding: 10px 20px; border-radius: 20px; background: rgba(255,255,255,0.10); border: 1.6px solid rgba(255,255,255,0.35); cursor: pointer; transition: 0.3s; color: #F8ECE4; }
.filter-btn.active, .filter-btn:hover { background: #8a1c2b; border-color: #F8ECE4; color: #ffffff; }
.grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 28px; }
.card { background: rgba(0,0,0,0.55); border-radius: 18px; padding: 20px; border: 1px solid rgba(255,255,255,0.2); transition: 0.3s; }
.card h2 { font-family: 'Playfair Display', serif; font-size: 22px; margin-bottom: 6px; }
.btn { background: #7a1522; color: white; width: 100%; border-radius: 10px; border: none; padding: 10px 16px; cursor: pointer; transition: .3s; margin-top: 10px; }
.btn:hover { transform: translateY(-2px); background: #9c1d2e; }
</style>
</head>

<body>

<div id="app" class="menu-container">

  <header class="header">
    <h1 class="title">Cocina - Pedidos</h1>
  </header>

  <!-- FILTROS -->
  <div class="filters">
    <button class="filter-btn" :class="{active:estado=='todos'}" @click="estado='todos'">Todos</button>
    <button class="filter-btn" :class="{active:estado=='pendiente'}" @click="estado='pendiente'">Pendientes</button>
    <button class="filter-btn" :class="{active:estado=='preparando'}" @click="estado='preparando'">En Cocina</button>
    <button class="filter-btn" :class="{active:estado=='listo'}" @click="estado='listo'">Listos</button>
    <button class="filter-btn" :class="{active:estado=='entregado'}" @click="estado='entregado'">Entregados</button>
  </div>

  <!-- LISTA -->
  <div class="grid">
    <div v-for="p in pedidosFiltrados" :key="p.id" class="card">

      <h2>Pedido #@{{ p.id }}</h2>
      <p><strong>Estado:</strong> @{{ p.estado }}</p>

      <ul>
        <!-- ✅ Cambiado: p.detalle → p.detalles, menu_item → menuItem -->
        <li v-for="d in p.detalles" :key="d.id">
          @{{ d.menuItem.nombre }} x @{{ d.cantidad }}
        </li>
      </ul>

      <button v-if="p.estado === 'pendiente'" class="btn" @click="cambiarEstado(p.id, 'preparando')">
        Comenzar 🔥
      </button>

      <button v-if="p.estado === 'preparando'" class="btn" @click="cambiarEstado(p.id, 'listo')">
        Marcar listo ✅
      </button>

      <button v-if="p.estado === 'listo'" class="btn" @click="cambiarEstado(p.id, 'entregado')">
        Entregar 🛍️
      </button>

    </div>
  </div>

</div>

<script src="https://unpkg.com/vue@3"></script>

<script>
Vue.createApp({
  data() {
    return {
      estado: 'todos',
      pedidos: []
    }
  },
  computed: {
    pedidosFiltrados() {
      return this.estado === 'todos'
        ? this.pedidos
        : this.pedidos.filter(p => p.estado === this.estado);
    }
  },
  methods: {
    async cargarPedidos() {
      try {
        const res = await fetch('/pedidos', { credentials: 'include' });
        // ✅ Cambiado: usamos JSON directo, sin .data
        this.pedidos = await res.json();
      } catch (e) {
        console.warn("⚠️ No se pudo actualizar la lista de pedidos.");
      }
    },

    async cambiarEstado(id, estado) {
      const token = document.querySelector('meta[name="csrf-token"]').content;

      await fetch(`/pedidos/${id}/estado`, {
        method: 'PUT',
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": token,
          "Accept": "application/json"
        },
        credentials: 'include',
        body: JSON.stringify({ estado })
      });

      this.cargarPedidos();
    }
  },
  mounted() {
    this.cargarPedidos();
    setInterval(this.cargarPedidos, 3000);
  }
}).mount('#app')
</script>

</body>
</html>
