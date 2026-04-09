<template>
  <div class="menu-container">
    <!-- HEADER -->
    <header class="header">
      <h1 class="title">🍽 ODER EASY</h1>

      <div v-if="!cliente">
        <button class="login-btn" @click="showLogin = true">
          Iniciar sesión
        </button>
      </div>

      <div v-else class="user-info">
        <span class="user-name">👤 {{ cliente.nombres }}</span>
        <button class="logout-btn" @click="handleLogout">
          Cerrar sesión
        </button>
      </div>
    </header>

    <!-- FILTROS -->
    <div class="filters">
      <button
        v-for="cat in categories"
        :key="cat"
        @click="selectedCategory = cat"
        class="filter-btn"
        :class="{ active: selectedCategory === cat }"
      >
        {{ cat.toUpperCase() }}
      </button>
    </div>

    <!-- LISTA DE PRODUCTOS -->
    <div class="grid">
      <div
        v-for="item in filteredItems"
        :key="item.id"
        class="card"
        role="button"
        tabindex="0"
        @click="openDetails(item)"
        @keydown.enter.prevent="openDetails(item)"
        @keydown.space.prevent="openDetails(item)"
      >
        <div class="media">
          <img
            :src="getItemImage(item)"
            :alt="item.nombre"
            class="card-img img-center"
            loading="lazy"
            @error="markImageError(item.id)"
          />
          <div class="media-overlay"></div>
        </div>

        <div class="card-body">
          <h2>{{ item.nombre }}</h2>
          <p class="card-price">${{ Number(item.precio || 0).toLocaleString() }}</p>
        </div>
      </div>
    </div>

    <!-- MODAL DETALLE PRODUCTO -->
    <transition name="details-fade">
      <div
        v-if="activeItem"
        class="details-backdrop"
        @click.self="closeDetails"
      >
        <div class="details-modal" role="dialog" aria-modal="true" :aria-label="`Detalle de ${activeItem.nombre}`">
          <button class="details-close" aria-label="Cerrar detalles" @click="closeDetails">
            ✕
          </button>

          <div class="details-media">
            <img
              :src="getItemImage(activeItem)"
              :alt="activeItem.nombre"
              class="details-img"
              loading="lazy"
              @error="markImageError(activeItem.id)"
            />
            <div class="details-gradient"></div>
          </div>

          <div class="details-content">
            <span class="category-chip">{{ String(activeItem.categoria || 'producto').toUpperCase() }}</span>
            <h3 class="details-title">{{ activeItem.nombre }}</h3>
            <p class="details-description">{{ activeItem.descripcion }}</p>

            <div class="details-footer">
              <p class="details-price">${{ Number(activeItem.precio).toLocaleString() }}</p>

              <transition name="add-counter-swap" mode="out-in">
                <button
                  v-if="getItemQuantity(activeItem.id) === 0"
                  key="add"
                  class="details-add-btn"
                  @click="addToCart(activeItem)"
                >
                  Agregar 🛒
                </button>

                <div v-else key="counter" class="details-counter" aria-label="Control de cantidad">
                  <button class="counter-btn" @click="decrease(activeItem)">−</button>
                  <span class="counter-value">{{ getItemQuantity(activeItem.id) }}</span>
                  <button class="counter-btn" @click="addToCart(activeItem)">+</button>
                </div>
              </transition>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- MODAL LOGIN / REGISTRO -->
    <div v-if="showLogin" class="auth-backdrop">
      <div class="auth-panel">
        <div class="tabs">
          <button :class="{ active: modalTab === 'login' }" @click="modalTab = 'login'">
            Iniciar sesión
          </button>
          <button :class="{ active: modalTab === 'register' }" @click="modalTab = 'register'">
            Registrarse
          </button>
        </div>

        <div v-if="modalTab === 'login'">
          <input class="input" :class="{ error: showErrors && !form.correo }" type="email" placeholder="Correo" v-model="form.correo">
          <input class="input" :class="{ error: showErrors && !form.password }" type="password" placeholder="Contraseña" v-model="form.password">

          <p v-if="loginMessage" class="status-msg">{{ loginMessage }}</p>

          <button class="btn-primary" @click="login" :disabled="loadingLogin">
            <span v-if="!loadingLogin">Entrar</span>
            <span v-else>Cargando...</span>
          </button>
        </div>

        <div v-if="modalTab === 'register'">
            <input class="input" placeholder="Nombres" v-model="register.nombres">
            <input class="input" placeholder="Apellidos" v-model="register.apellidos">
            <input class="input" type="email" placeholder="Correo" v-model="register.correo">
            <input class="input" type="password" placeholder="Contraseña" v-model="register.password">
            <input class="input" placeholder="Teléfono" v-model="register.telefono">

          <p v-if="registerMessage" class="status-msg">{{ registerMessage }}</p>

          <button class="btn-primary" @click="registerUser">
            Crear cuenta
          </button>
        </div>

        <button class="btn-secondary" @click="cerrarModal">Cerrar</button>
      </div>
    </div>

    <ConfirmModal ref="logoutConfirmModalRef" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import axios from 'axios'
import { API_BASE } from '../api.js'
import { addToCart, cart, removeFromCart, saveCart } from '../cart.js'
import { cliente, setCliente, logoutCliente } from '../cliente.js'
import ConfirmModal from './ConfirmModal.vue'

const items = ref([])
const selectedCategory = ref('todos')
const categories = ['todos', 'plato', 'bebida']

const showLogin = ref(false)
const modalTab = ref('login')
const activeItem = ref(null)

const form = ref({ correo: '', password: '' })
const register = ref({
  nombres: '',
  apellidos: '',
  correo: '',
  password: '',
  telefono: ''
})
const loadingLogin = ref(false)
const showErrors = ref(false)
const loginMessage = ref('')
const registerMessage = ref('')
const logoutConfirmModalRef = ref(null)

const imageErrors = ref({})
const FOOD_PLACEHOLDER = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 900 600'%3E%3Cdefs%3E%3ClinearGradient id='bg' x1='0' x2='1' y1='0' y2='1'%3E%3Cstop offset='0%25' stop-color='%23222834'/%3E%3Cstop offset='100%25' stop-color='%2332354a'/%3E%3C/linearGradient%3E%3ClinearGradient id='plate' x1='0' x2='1'%3E%3Cstop offset='0%25' stop-color='%23f5d7a5'/%3E%3Cstop offset='100%25' stop-color='%23e2b96f'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='900' height='600' fill='url(%23bg)'/%3E%3Ccircle cx='450' cy='315' r='170' fill='url(%23plate)' opacity='.92'/%3E%3Ccircle cx='450' cy='315' r='126' fill='%23fff7ea' opacity='.9'/%3E%3Cg fill='%23b2463d'%3E%3Ccircle cx='395' cy='285' r='24'/%3E%3Ccircle cx='472' cy='278' r='21'/%3E%3Ccircle cx='441' cy='342' r='22'/%3E%3C/g%3E%3Cg fill='%2368a357'%3E%3Cellipse cx='506' cy='329' rx='26' ry='14' transform='rotate(-22 506 329)'/%3E%3Cellipse cx='378' cy='334' rx='24' ry='13' transform='rotate(20 378 334)'/%3E%3C/g%3E%3Ctext x='50%25' y='520' text-anchor='middle' fill='%23f8ece4' font-family='Inter,Arial,sans-serif' font-size='44' font-weight='700'%3EDelicioso y reci%C3%A9n preparado%3C/text%3E%3C/svg%3E"

function openDetails(item) {
  activeItem.value = item
}

function closeDetails() {
  activeItem.value = null
}

function handleKeydown(event) {
  if (event.key === 'Escape') {
    if (activeItem.value) {
      closeDetails()
      return
    }

    if (showLogin.value) {
      cerrarModal()
    }
  }
}

function backendBaseUrl() {
  return (API_BASE || '').replace(/\/?api\/?$/, '')
}

function normalizePath(path) {
  return String(path)
    .split('/')
    .map(segment => encodeURIComponent(segment))
    .join('/')
}

function getItemImage(item) {
  if (!item) return FOOD_PLACEHOLDER
  if (imageErrors.value[item.id]) return FOOD_PLACEHOLDER
  if (item.image_url) return item.image_url
  if (item.image_path) return `${backendBaseUrl()}/storage/${normalizePath(item.image_path)}`
  if (item.imagen) return item.imagen
  return FOOD_PLACEHOLDER
}

function markImageError(itemId) {
  imageErrors.value = { ...imageErrors.value, [itemId]: true }
}

function getItemQuantity(itemId) {
  return cart.value.find(i => i.id === itemId)?.quantity || 0
}

function decrease(item) {
  const existing = cart.value.find(i => i.id === item.id)
  if (!existing) return

  if (existing.quantity > 1) {
    existing.quantity--
  } else {
    removeFromCart(item.id)
    return
  }

  const index = cart.value.findIndex(i => i.id === item.id)
  if (index > -1) {
    cart.value[index] = { ...existing }
  }

  saveCart()
}

async function handleLogout() {
  const ok = await logoutConfirmModalRef.value?.open('¿Seguro que deseas cerrar sesión?')
  if (!ok) return
  await logoutCliente()
}

async function login() {
  showErrors.value = true
  loginMessage.value = ''

  if (!form.value.correo || !form.value.password) {
    loginMessage.value = '⚠️ Completa todos los campos.'
    return
  }

  loadingLogin.value = true

  try {
    const res = await axios.post(`${API_BASE}/cliente/login`, {
      correo: form.value.correo,
      password: form.value.password
    })

    setCliente({ ...res.data.cliente, token: res.data.token || null })
    loginMessage.value = '✅ Sesión iniciada'

    setTimeout(() => {
      showLogin.value = false
    }, 800)
  } catch {
    loginMessage.value = '❌ Correo o contraseña incorrectos'
  } finally {
    loadingLogin.value = false
  }
}

async function registerUser() {
  registerMessage.value = ''

  try {
    await axios.post(`${API_BASE}/cliente/register`, {
      nombres: register.value.nombres,
      apellidos: register.value.apellidos,
      correo: register.value.correo,
      password: register.value.password,
      telefono: register.value.telefono || null,
      restaurant_id: 1
    })

    const loginRes = await axios.post(`${API_BASE}/cliente/login`, {
      correo: register.value.correo,
      password: register.value.password
    })

    setCliente({ ...loginRes.data.cliente, token: loginRes.data.token || null })
    registerMessage.value = '✅ Cuenta creada'

    setTimeout(() => {
      showLogin.value = false
    }, 800)
  } catch {
    registerMessage.value = '❌ Error registrando cliente'
  }
}

function cerrarModal() {
  showLogin.value = false
}

const filteredItems = computed(() =>
  selectedCategory.value === 'todos'
    ? items.value
    : items.value.filter(i => i.categoria === selectedCategory.value)
)

watch(activeItem, value => {
  document.body.style.overflow = value ? 'hidden' : ''
})

onMounted(async () => {
  try {
    const res = await fetch(`${API_BASE}/menu-items`)
    if (!res.ok) throw new Error('Error cargando menú')
    const data = await res.json()
    items.value = data.data || data
  } catch (err) {
    console.error(err)
  }

  const openLogin = () => {
    showLogin.value = true
    modalTab.value = 'login'
  }

  window.addEventListener('open-login', openLogin)
  window.addEventListener('keydown', handleKeydown)

  onUnmounted(() => {
    window.removeEventListener('open-login', openLogin)
    window.removeEventListener('keydown', handleKeydown)
    document.body.style.overflow = ''
  })
})
</script>

<style scoped>
:root {
  --bg: #10131d;
  --surface: #1a1f2d;
  --surface-soft: #242b3c;
  --line: rgba(255, 255, 255, 0.16);
  --text-main: #f8ece4;
  --text-muted: rgba(248, 236, 228, 0.78);
  --accent: #b82136;
  --accent-soft: #ffd7aa;
}

* {
  box-sizing: border-box;
}

.input.error {
  border: 2px solid #ff4d4d !important;
  background: #ffeaea !important;
}

.status-msg {
  text-align: center;
  font-weight: 600;
  margin-bottom: 8px;
  color: var(--accent-soft);
}

.menu-container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 12px 10px 132px;
  color: var(--text-main);
  text-shadow: none;
  overflow-x: hidden;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  margin-bottom: 14px;
}

.title {
  margin: 0;
  color: var(--text-main) !important;
  font-family: 'Playfair Display', serif;
  font-size: clamp(1.2rem, 4.8vw, 2rem);
}

.login-btn,
.logout-btn {
  padding: 10px 14px;
  border-radius: 12px;
  font-weight: 700;
  font-size: 0.87rem;
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.26);
  background: linear-gradient(135deg, #7a1522 0%, #b82136 100%);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.34);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-name {
  font-weight: 700;
  color: var(--accent-soft);
  font-size: 0.86rem;
}

.filters {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
  overflow-x: auto;
  padding-bottom: 4px;
  scrollbar-width: thin;
}

.filter-btn {
  white-space: nowrap;
  padding: 9px 14px;
  border-radius: 999px;
  background: var(--surface-soft);
  color: var(--text-main);
  border: 1px solid var(--line);
  font-weight: 600;
  transition: transform 0.18s ease, background 0.2s ease;
}

.filter-btn.active,
.filter-btn:hover {
  background: linear-gradient(135deg, #8a1c2b 0%, #b82136 100%);
  border-color: rgba(255, 255, 255, 0.34);
}

.grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 10px;
}

.card {
  background: linear-gradient(170deg, rgba(22, 27, 39, 0.98) 0%, rgba(16, 20, 31, 0.98) 100%);
  border-radius: 16px;
  padding: 10px;
  border: 1px solid var(--line);
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.24);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  cursor: pointer;
  outline: none;
}

.card:active {
  transform: scale(0.985);
}

.card:hover {
  transform: translateY(-3px);
  box-shadow: 0 16px 34px rgba(0, 0, 0, 0.34);
}

.card:focus-visible,
.details-close:focus-visible,
.details-add-btn:focus-visible,
.counter-btn:focus-visible {
  outline: 2px solid var(--accent-soft);
  outline-offset: 3px;
}

.media {
  position: relative;
  width: 100%;
  border-radius: 12px;
  overflow: hidden;
  height: clamp(150px, 36vw, 170px);
  background: #262b39;
  border: 1px solid rgba(255, 255, 255, 0.14);
  margin-bottom: 10px;
}

.card-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  display: block;
  transform: scale(1);
  transition: transform 0.32s ease;
}

.card:hover .card-img {
  transform: scale(1.04);
}

.media-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(0, 0, 0, 0.03), rgba(0, 0, 0, 0.32));
  pointer-events: none;
}

.card-body {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.card-body h2 {
  font-size: clamp(0.95rem, 3.8vw, 1.1rem);
  margin: 0;
  line-height: 1.3;
  min-height: 2.5em;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-price {
  margin: 0;
  font-size: clamp(1rem, 4.2vw, 1.16rem);
  font-weight: 800;
  color: var(--accent-soft);
}



@media (max-width: 639px) {
  .card {
    padding: 9px;
  }

  .media {
    margin-bottom: 8px;
  }

  .media {
    height: clamp(150px, 42vw, 180px);
  }
}

.details-backdrop {
  position: fixed;
  inset: 0;
  z-index: 4100;
  background: rgba(0, 0, 0, 0.68);
  backdrop-filter: blur(12px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 14px;
}

.details-modal {
  width: min(560px, 96vw);
  max-height: 84vh;
  overflow: hidden;
  border-radius: 20px;
  background: linear-gradient(170deg, #191f2c 0%, #131925 100%);
  border: 1px solid rgba(255, 255, 255, 0.16);
  box-shadow: 0 18px 54px rgba(0, 0, 0, 0.55);
  position: relative;
  display: flex;
  flex-direction: column;
}

.details-media {
  position: relative;
  height: 220px;
  flex-shrink: 0;
}

.details-img,
.details-placeholder {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.details-gradient {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(0, 0, 0, 0.08), rgba(0, 0, 0, 0.54));
  pointer-events: none;
}

.details-close {
  position: absolute;
  top: 12px;
  right: 12px;
  z-index: 2;
  width: 36px;
  height: 36px;
  border: 1px solid rgba(255, 255, 255, 0.32);
  border-radius: 999px;
  background: rgba(0, 0, 0, 0.54);
  color: #fff;
  cursor: pointer;
}

.details-content {
  padding: 16px;
  overflow-y: auto;
}

.category-chip {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.24);
  font-size: 0.7rem;
  letter-spacing: 0.07em;
  opacity: 0.9;
  margin-bottom: 10px;
}

.details-title {
  margin: 0;
  font-size: clamp(1.2rem, 4.8vw, 1.5rem);
  font-weight: 800;
}

.details-description {
  margin: 10px 0 0;
  font-size: 0.96rem;
  color: var(--text-muted);
  line-height: 1.5;
}

.details-footer {
  margin-top: 16px;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 12px;
}

.details-price {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 800;
  color: var(--accent-soft);
}

.details-add-btn {
  border: none;
  border-radius: 12px;
  padding: 12px 16px;
  color: #fff;
  font-weight: 700;
  background: linear-gradient(135deg, #7a1522 0%, #b82136 100%);
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.details-add-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(184, 33, 54, 0.42);
}

.details-counter {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 6px;
  border-radius: 999px;
  background: rgba(18, 18, 18, 0.72);
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: 0 10px 26px rgba(0, 0, 0, 0.34);
  width: fit-content;
}

.counter-btn {
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 999px;
  color: #fff;
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
  background: linear-gradient(135deg, #7a1522 0%, #b82136 100%);
}

.counter-value {
  min-width: 24px;
  text-align: center;
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--accent-soft);
}

.add-counter-swap-enter-active,
.add-counter-swap-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.add-counter-swap-enter-from,
.add-counter-swap-leave-to {
  opacity: 0;
  transform: translateY(5px) scale(0.97);
}

.details-fade-enter-active,
.details-fade-leave-active {
  transition: opacity 0.24s ease;
}

.details-fade-enter-active .details-modal,
.details-fade-leave-active .details-modal {
  transition: transform 0.25s ease, opacity 0.25s ease;
}

.details-fade-enter-from,
.details-fade-leave-to {
  opacity: 0;
}

.details-fade-enter-from .details-modal,
.details-fade-leave-to .details-modal {
  transform: translateY(8px) scale(0.96);
  opacity: 0;
}

.auth-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(6px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 5000;
}

.auth-panel {
  background: rgba(0, 0, 0, 0.65);
  padding: 20px;
  width: min(350px, 92vw);
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.18);
}

.tabs {
  display: flex;
  margin-bottom: 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.25);
}

.tabs button {
  flex: 1;
  padding: 10px;
  background: none;
  border: none;
  color: #fff;
  opacity: 0.6;
  font-weight: 600;
  cursor: pointer;
}

.tabs button.active {
  opacity: 1;
  border-bottom: 2px solid var(--accent-soft);
}

.input {
  width: 100%;
  padding: 12px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.95);
  margin-bottom: 14px;
  border: none;
  text-align: center;
  font-size: 15px;
  color: #2a2a2a;
  font-weight: 600;
}

.btn-primary {
  width: 100%;
  padding: 12px;
  background: #8a1c2b;
  color: #fff;
  border-radius: 10px;
  border: none;
  margin-top: 8px;
}

.btn-secondary {
  width: 100%;
  padding: 11px;
  background: #2a2a2a;
  color: white;
  border-radius: 10px;
  border: none;
  margin-top: 10px;
}

@media (min-width: 640px) {
  .menu-container {
    padding: 20px 14px 136px;
  }

  .grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
  }

  .details-footer {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }

  .details-add-btn {
    min-width: 160px;
  }
}

@media (min-width: 1024px) {
  .menu-container {
    padding: 32px 22px 120px;
  }

  .grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22px;
  }

  .card:hover {
    transform: translateY(-6px);
  }
}
</style>
