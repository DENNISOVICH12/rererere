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

  if (item.image_url) {
    return item.image_url.replace('localhost', window.location.hostname)
  }

  if (item.image_path) {
    return `/storage/${normalizePath(item.image_path)}`  }

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
    console.log(items.value)
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
  --color-primary: #0b1018;
  --color-secondary: #182231;
  --color-accent: #c23a4a;
  --text-main: #f4f6f8;
  --text-muted: rgba(244, 246, 248, 0.75);
  --line-soft: rgba(244, 246, 248, 0.15);
  --radius-card: 16px;
}

* {
  box-sizing: border-box;
}

.menu-container {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 14px 10px 132px;
  color: var(--text-main);
  overflow-x: hidden;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
}

.title {
  margin: 0;
  font-size: clamp(1.2rem, 4.6vw, 2rem);
  font-weight: 700;
  letter-spacing: 0.02em;
  color: var(--text-main);
}

.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-name {
  font-size: 0.86rem;
  font-weight: 600;
  color: var(--text-main);
}

.login-btn,
.logout-btn,
.btn-primary,
.details-add-btn,
.counter-btn {
  border: none;
  border-radius: 12px;
  background: var(--color-accent);
  color: var(--text-main);
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.2s ease, filter 0.2s ease, box-shadow 0.2s ease;
}

.login-btn,
.logout-btn {
  padding: 10px 14px;
  font-size: 0.86rem;
}

.login-btn:hover,
.logout-btn:hover,
.btn-primary:hover,
.details-add-btn:hover,
.counter-btn:hover {
  filter: brightness(1.08);
}

.login-btn:active,
.logout-btn:active,
.btn-primary:active,
.details-add-btn:active,
.counter-btn:active {
  transform: translateY(1px);
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
  border-radius: 999px;
  padding: 9px 14px;
  border: 1px solid var(--line-soft);
  background: rgba(24, 34, 49, 0.88);
  color: var(--text-main);
  font-weight: 600;
}

.filter-btn.active {
  border-color: transparent;
  background: var(--color-accent);
}

.grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
}

.card {
  display: flex;
  flex-direction: column;
  min-height: 100%;
  border-radius: var(--radius-card);
  padding: 10px;
  border: 1px solid var(--line-soft);
  background: rgba(24, 34, 49, 0.84);
  backdrop-filter: blur(8px);
  box-shadow: 0 12px 26px rgba(11, 16, 24, 0.34);
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 30px rgba(11, 16, 24, 0.4);
}

.card:active {
  transform: scale(0.985);
}

.card:focus-visible,
.details-close:focus-visible,
.details-add-btn:focus-visible,
.counter-btn:focus-visible,
.filter-btn:focus-visible,
.btn-primary:focus-visible,
.btn-secondary:focus-visible,
.login-btn:focus-visible,
.logout-btn:focus-visible {
  outline: 2px solid var(--color-accent);
  outline-offset: 2px;
}

.media {
  position: relative;
  width: 100%;
  height: clamp(168px, 39vw, 194px);
  margin-bottom: 8px;
  border-radius: 13px;
  overflow: hidden;
  background: rgba(11, 16, 24, 0.5);
}

.card-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.3s ease;
}

.card:hover .card-img {
  transform: scale(1.03);
}

.media-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(11, 16, 24, 0.06), rgba(11, 16, 24, 0.4));
}

.card-body {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 0 2px;
}

.card-body h2 {
  margin: 0;
  font-size: clamp(0.94rem, 3.5vw, 1.04rem);
  line-height: 1.3;
  min-height: 2.4em;
  font-weight: 700;
  color: var(--text-main);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-price,
.details-price,
.counter-value,
.status-msg {
  color: var(--color-accent);
  font-weight: 800;
}

.card-price {
  margin: 2px 0 0;
  font-size: clamp(1.08rem, 4.8vw, 1.3rem);
}

.details-backdrop,
.auth-backdrop {
  position: fixed;
  inset: 0;
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 5000;
  background: rgba(11, 16, 24, 0.74);
  backdrop-filter: blur(8px);
}

.details-backdrop {
  z-index: 4100;
  padding: 14px;
}

.details-modal {
  width: min(560px, 96vw);
  max-height: 84vh;
  overflow: hidden;
  border-radius: 20px;
  border: 1px solid var(--line-soft);
  background: rgba(24, 34, 49, 0.96);
  box-shadow: 0 20px 42px rgba(11, 16, 24, 0.52);
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
  background: linear-gradient(to bottom, rgba(11, 16, 24, 0.06), rgba(11, 16, 24, 0.54));
}

.details-close {
  position: absolute;
  top: 12px;
  right: 12px;
  z-index: 2;
  width: 36px;
  height: 36px;
  border-radius: 999px;
  border: 1px solid var(--line-soft);
  background: rgba(11, 16, 24, 0.72);
  color: var(--text-main);
  cursor: pointer;
}

.details-content {
  padding: 16px;
  overflow-y: auto;
}

.category-chip {
  display: inline-block;
  margin-bottom: 10px;
  padding: 4px 10px;
  border-radius: 999px;
  border: 1px solid var(--line-soft);
  color: var(--text-muted);
  font-size: 0.7rem;
  letter-spacing: 0.07em;
}

.details-title {
  margin: 0;
  font-size: clamp(1.2rem, 4.8vw, 1.48rem);
  font-weight: 800;
  color: var(--text-main);
}

.details-description {
  margin: 10px 0 0;
  color: var(--text-muted);
  line-height: 1.5;
  font-size: 0.95rem;
}

.details-footer {
  margin-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.details-price {
  margin: 0;
  font-size: 1.2rem;
}

.details-add-btn {
  padding: 12px 16px;
}

.details-counter {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 6px;
  border-radius: 999px;
  background: rgba(11, 16, 24, 0.7);
  border: 1px solid var(--line-soft);
  width: fit-content;
}

.counter-btn {
  width: 36px;
  height: 36px;
  border-radius: 999px;
  font-size: 1.1rem;
}

.counter-value {
  min-width: 24px;
  text-align: center;
  font-size: 1.04rem;
}

.auth-panel {
  width: min(350px, 92vw);
  padding: 20px;
  border-radius: 18px;
  border: 1px solid var(--line-soft);
  background: rgba(24, 34, 49, 0.95);
  box-shadow: 0 20px 34px rgba(11, 16, 24, 0.5);
}

.tabs {
  display: flex;
  margin-bottom: 16px;
  border-bottom: 1px solid var(--line-soft);
}

.tabs button {
  flex: 1;
  padding: 10px;
  border: none;
  background: none;
  color: var(--text-muted);
  font-weight: 600;
  cursor: pointer;
}

.tabs button.active {
  color: var(--text-main);
  border-bottom: 2px solid var(--color-accent);
}

.input {
  width: 100%;
  margin-bottom: 14px;
  padding: 12px;
  border-radius: 10px;
  border: 1px solid var(--line-soft);
  background: rgba(11, 16, 24, 0.58);
  color: var(--text-main);
  font-size: 15px;
  font-weight: 500;
  text-align: center;
}

.input::placeholder {
  color: var(--text-muted);
}

.input.error {
  border-color: var(--color-accent);
}

.btn-primary,
.btn-secondary {
  width: 100%;
  margin-top: 8px;
  padding: 11px;
}

.btn-secondary {
  margin-top: 10px;
  border: 1px solid var(--line-soft);
  background: rgba(24, 34, 49, 0.75);
  color: var(--text-main);
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
  transform: translateY(8px) scale(0.97);
  opacity: 0;
}

@media (max-width: 639px) {
  .card {
    padding: 9px;
  }

  .media {
    height: clamp(172px, 45vw, 198px);
  }
}

@media (min-width: 640px) {
  .menu-container {
    padding: 20px 14px 136px;
  }

  .grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
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
    gap: 18px;
  }
}
</style>
