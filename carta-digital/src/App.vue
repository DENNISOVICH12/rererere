<script setup>
import { ref, computed, onMounted, onUnmounted  } from 'vue'
import CartaDigital from './components/CartaDigital.vue'
import { cart, removeFromCart, clearCart, openLoginModal } from './cart.js'
import axios from 'axios'
import { setCliente,logoutCliente  } from './cliente.js'
import { loadCliente } from "./cliente.js"
import { getCliente } from "./cliente.js"
import { cliente } from "./cliente.js"
loadCliente()

const showCart = ref(false)
const showLogin = ref(false)
const showRegister = ref(false)

const email = ref("")
const password = ref("")
const cartButton = ref(null)

function openLogin() {
window.dispatchEvent(new CustomEvent("open-login"))
}
function handleLogout() {
  logoutCliente()
  alert("👋 Sesión cerrada correctamente")
}
async function handleLogin() {
  try {
    await login(email.value, password.value)
    showLogin.value = false
    alert("✅ Sesión iniciada, tus pedidos ahora estarán asociados.")
  } catch {
    alert("❌ Usuario o contraseña incorrectos")
  }
}

const registerForm = ref({
  usuario: "",
  correo: "",
  password: "",
  nombres: "",
  apellidos: "",
  dni: "",
  edad: ""
})

async function handleRegister() {
  try {
    await axios.post("http://172.18.112.238:8000/api/register-cliente", registerForm.value)
    alert("✅ Registro exitoso, ahora inicia sesión ✅")
    showRegister.value = false
    showLogin.value = true
  } catch {
    alert("❌ Error al registrarse")
  }
}

const totalPrice = computed(() =>
  cart.value.reduce((sum, item) => sum + item.precio * item.quantity, 0)
)

async function sendOrder() {
  try {
    const cliente = getCliente(); // ✅ obtiene { id, usuario, nombre }

    await axios.post('http://172.18.112.238:8000/api/orders', {
      mesa: null,
      cliente_id: cliente ? cliente.id : null, // ✅ ahora SIEMPRE manda el ID correcto
      restaurant_id: 1,
      items: cart.value.map(i => ({
        menu_item_id: i.id,
        cantidad: i.quantity,
        precio_unitario: i.precio
      }))
    });

    alert(cliente
      ? "✅ Pedido enviado (Cliente identificado)."
      : "✅ Pedido enviado como invitado.");

    window.dispatchEvent(new CustomEvent("pedido-creado"));

    clearCart(); // mejor que hacer cart.value = []

  } catch (error) {
    console.log(error.response?.data);
    alert("❌ Error enviando pedido");
  }
}


/* 🔥 Rebote cuando se añade producto */
onMounted(() => {

  // 🔔 Animación cuando se agrega al carrito
  const cartBounceHandler = () => {
    if (!cartButton.value) return

    cartButton.value.classList.remove("cart-bounce")
    void cartButton.value.offsetWidth // fuerza reflow para reiniciar animación
    cartButton.value.classList.add("cart-bounce")
  }

  // 📌 Escuchar evento global del carrito
  window.addEventListener("cart-updated", cartBounceHandler)


  // 🔓 Escuchar la solicitud global para abrir login
  const openLoginHandler = () => {
    showLogin.value = true
  }

  window.addEventListener("open-login", openLoginHandler)


  // 🧹 Quitar eventos al destruir el componente (buena práctica)
  onUnmounted(() => {
    window.removeEventListener("cart-updated", cartBounceHandler)
    window.removeEventListener("open-login", openLoginHandler)
  })
})

</script>

<template>
  <div>

    <button ref="cartButton" class="cart-floating" @click="showCart = !showCart">
      🛒 <span>{{ cart.length }}</span>
    </button>

    <CartaDigital />

    <div v-if="showCart" class="cart-panel">

      <button class="close-cart" @click="showCart = false">✦</button>

      <h2 class="cart-title">Tu Pedido</h2>

      <div v-if="!cliente" class="login-hint">
        ¿Quieres guardar historial y obtener puntos?<br>
        <button class="login-link" @click="openLoginModal">Iniciar sesión</button>

        </div>
      <div v-if="cart.length === 0" class="empty-cart">🛍 Carrito vacío</div>

      <div v-for="item in cart" :key="item.id" class="cart-item">
        <div>
          <strong>{{ item.nombre }}</strong>
          <p class="qty">Cantidad: {{ item.quantity }}</p>
        </div>
        <button class="remove-item" @click="removeFromCart(item.id)">🗑</button>
      </div>

      <div v-if="cart.length > 0" class="cart-footer">
        <p class="total">Total: <strong>${{ totalPrice.toLocaleString() }}</strong></p>
        <button @click="clearCart" class="clear-btn">Vaciar Carrito</button>
        <button @click="sendOrder" class="send-btn">Enviar a cocina</button>
      </div>

    </div>

    <!-- modals se quedan IGUAL -->
  </div>
</template>

<style>

/* --- Rebote estilo iPhone al agregar productos --- */
.cart-bounce {
  animation: cartBounceAnim 0.45s cubic-bezier(.22,.61,.36,1);
}

@keyframes cartBounceAnim {
  0%   { transform: translateY(0) scale(1); }
  30%  { transform: translateY(-6px) scale(1.07); }
  60%  { transform: translateY(2px) scale(0.95); }
  100% { transform: translateY(0) scale(1); }
}

/* === BOTÓN CARRITO FLOTANTE === */
.cart-floating {
  position: fixed;
  bottom: 26px;
  right: 26px;
  background: rgba(255,255,255,0.18);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255,255,255,0.35);
  padding: 12px 18px;
  border-radius: 14px;
  font-size: 18px;
  cursor: pointer;
  color: #F8ECE4;
  display: flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.45);
  transition: .3s;
  z-index: 3000;
}
.cart-floating:hover { transform: translateY(-3px) scale(1.03); }

/* === PANEL CARRITO === */
.cart-panel {
  position: fixed;
  top: 0;
  right: 0;
  width: 380px;
  height: 100vh;
  background: rgba(0,0,0,0.65);
  backdrop-filter: blur(18px);
  border-left: 1px solid rgba(255,255,255,0.18);
  padding: 32px;
  color: #fff;
  overflow-y: auto;
  z-index: 4000;
  display: flex;
  flex-direction: column;
  gap: 22px;
}

/* Cerrar */
.close-cart {
  align-self: flex-end;
  background: rgba(255,255,255,0.12);
  border: 1px solid rgba(255,255,255,0.25);
  padding: 8px 12px;
  border-radius: 10px;
  cursor: pointer;
  color: #fff;
  transition: .25s;
}
.close-cart:hover { background: #ff4b4b; transform: rotate(90deg) scale(1.18); }

/* Texto sugerencia login */
.login-hint { text-align:center; opacity:.9; font-size:15px; }
.login-link { color:#ffd7aa; background:none; border:none; cursor:pointer; font-weight:600; }
.login-link:hover { text-shadow:0 0 6px #ffd7aa; }

/* ITEMS */
.cart-item {
  display: flex;
  justify-content: space-between;
  padding: 14px 0;
  border-bottom: 1px solid rgba(255,255,255,0.15);
}
.qty { opacity: 0.8; font-size: 14px; }

/* Botón eliminar */
.remove-item {
  background: rgba(255,255,255,0.15);
  border: 1px solid rgba(255,255,255,0.25);
  padding: 6px 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: .25s;
  color: #ff9c9c;
}
.remove-item:hover { background: #ff4b4b; color: white; transform: scale(1.1); }

/* BOTONES CARRITO */
.clear-btn,
.send-btn {
  padding: 14px;
  width: 100%;
  border-radius: 14px;
  border: none;
  font-weight: 600;
  font-size: 15px;
  cursor: pointer;
  margin-top: 14px;
  transition: transform .24s cubic-bezier(.22,.61,.36,1);
}

.clear-btn:hover,
.send-btn:hover {
  transform: translateY(-4px) scale(1.03);
  box-shadow: 0 8px 20px rgba(0,0,0,.35);
}

.clear-btn:active,
.send-btn:active {
  transform: translateY(2px) scale(.97);
}

/* Vaciar */
.clear-btn {
  background: rgba(255,255,255,0.12);
  color: #ffbaba;
}
.clear-btn:hover { background: rgba(255,119,119,0.30); }

/* Enviar */
.send-btn {
  background: #9c2030;
  color: #fff;
}
.send-btn:hover { background: #b5263d; }

/* MODALES */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.65);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 6000;
}

.modal {
  background: rgba(0,0,0,0.55);
  border-radius: 20px;
  padding: 32px;
  width: 340px;
  border: 1px solid rgba(255,255,255,0.18);
  text-align: center;
  animation: fadeZoom .35s ease;
  box-shadow: 0 12px 40px rgba(0,0,0,0.55);
}
@keyframes fadeZoom {
  from { transform: scale(.90); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

.input {
  width: 100%;
  padding: 12px;
  border-radius: 12px;
  border: none;
  margin-top: 10px;
  background: rgba(255,255,255,0.14);
  color: white;
}

.modal-confirm {
  margin-top: 18px;
  padding: 12px;
  border-radius: 12px;
  width: 100%;
  background:#9c2030;
  color:#fff;
  border:none;
}
.modal-confirm:hover { background:#b5263d; }

.modal-close {
  margin-top: 10px;
  padding: 12px;
  width: 100%;
  border-radius: 12px;
  background: rgba(255,255,255,0.12);
  color: white;
  border: none;
}
.modal-close:hover { background: rgba(255,255,255,0.25); }
</style>
