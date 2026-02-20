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
        <span class="user-name">👤 {{ cliente.nombre }}</span>
        <button class="logout-btn" @click="logoutCliente">
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
            v-if="hasItemImage(item)"
            :src="getItemImage(item)"
            class="card-img img-center"
            loading="lazy"
            @error="markImageError(item.id)"
          />
          <div v-else class="placeholder-img">
            <span>Sin imagen</span>
          </div>
          <div class="media-overlay"></div>
        </div>

        <div class="card-body">
          <h2>{{ item.nombre }}</h2>
          <p class="tap-hint">Toca para ver</p>
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
              v-if="hasItemImage(activeItem)"
              :src="getItemImage(activeItem)"
              class="details-img"
              loading="lazy"
              @error="markImageError(activeItem.id)"
            />
            <div v-else class="placeholder-img details-placeholder">
              <span>Sin imagen</span>
            </div>
            <div class="details-gradient"></div>
          </div>

          <div class="details-content">
            <span class="category-chip">{{ String(activeItem.categoria || 'producto').toUpperCase() }}</span>
            <h3 class="details-title">{{ activeItem.nombre }}</h3>
            <p class="details-description">{{ activeItem.descripcion }}</p>

            <div class="details-footer">
              <p class="details-price">${{ Number(activeItem.precio).toLocaleString() }}</p>
              <button class="details-add-btn" @click="addToCart(activeItem)">
                Agregar 🛒
              </button>
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
          <input class="input" :class="{ error: showErrors && !form.usuario }" placeholder="Usuario" v-model="form.usuario">
          <input class="input" :class="{ error: showErrors && !form.password }" type="password" placeholder="Contraseña" v-model="form.password">

          <p v-if="loginMessage" class="status-msg">{{ loginMessage }}</p>

          <button class="btn-primary" @click="login" :disabled="loadingLogin">
            <span v-if="!loadingLogin">Entrar</span>
            <span v-else>Cargando...</span>
          </button>
        </div>

        <div v-if="modalTab === 'register'">
          <input class="input" placeholder="Usuario" v-model="register.usuario">
          <input class="input" placeholder="Nombres" v-model="register.nombres">
          <input class="input" placeholder="Apellidos" v-model="register.apellidos">
          <input class="input" type="email" placeholder="Correo" v-model="register.correo">
          <input class="input" type="password" placeholder="Contraseña" v-model="register.password">

          <p v-if="registerMessage" class="status-msg">{{ registerMessage }}</p>

          <button class="btn-primary" @click="registerUser">
            Crear cuenta
          </button>
        </div>

        <button class="btn-secondary" @click="cerrarModal">Cerrar</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import axios from 'axios'
import { API_BASE } from '../api.js'
import { addToCart } from '../cart.js'
import { cliente, setCliente, logoutCliente } from '../cliente.js'

const items = ref([])
const selectedCategory = ref('todos')
const categories = ['todos', 'plato', 'bebida']

const showLogin = ref(false)
const modalTab = ref('login')
const activeItem = ref(null)

const form = ref({ usuario: '', password: '' })
const register = ref({ usuario: '', nombres: '', apellidos: '', correo: '', password: '' })

const loadingLogin = ref(false)
const showErrors = ref(false)
const loginMessage = ref('')
const registerMessage = ref('')

const imageErrors = ref({})

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
  if (item?.image_url) return item.image_url
  if (item?.image_path) return `${backendBaseUrl()}/storage/${normalizePath(item.image_path)}`
  return item?.imagen || null
}

function markImageError(itemId) {
  imageErrors.value = { ...imageErrors.value, [itemId]: true }
}

function hasItemImage(item) {
  return Boolean(getItemImage(item)) && !imageErrors.value[item.id]
}

async function login() {
  showErrors.value = true
  loginMessage.value = ''

  if (!form.value.usuario || !form.value.password) {
    loginMessage.value = '⚠️ Completa todos los campos.'
    return
  }

  loadingLogin.value = true

  try {
    const res = await axios.post(`${API_BASE}/login-cliente`, form.value)
    setCliente(res.data.cliente)
    loginMessage.value = '✅ Sesión iniciada'
    setTimeout(() => {
      showLogin.value = false
    }, 800)
  } catch {
    loginMessage.value = '❌ Usuario o contraseña incorrectos'
  } finally {
    loadingLogin.value = false
  }
}

async function registerUser() {
  registerMessage.value = ''

  try {
    await axios.post(`${API_BASE}/register-cliente`, register.value)

    const loginRes = await axios.post(`${API_BASE}/login-cliente`, {
      usuario: register.value.usuario,
      password: register.value.password
    })

    setCliente(loginRes.data.cliente)
    registerMessage.value = '✅ Cuenta creada'
    setTimeout(() => {
      showLogin.value = false
    }, 800)
  } catch {
    registerMessage.value = '❌ Error registrando usuario'
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
.input.error {
  border: 2px solid #ff4d4d !important;
  background: #ffeaea !important;
}

.status-msg {
  text-align: center;
  font-weight: 600;
  margin-bottom: 8px;
  color: #ffd7aa;
}

.menu-container {
  max-width: 1200px;
  margin: auto;
  padding: 40px 20px;
  color: #fff;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.65);
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}

.title {
  color: #f8ece4 !important;
  font-family: 'Playfair Display', serif;
}

.login-btn {
  padding: 10px 24px;
  border-radius: 30px;
  font-weight: 600;
  color: #fff;
  border: 1px solid rgba(255, 215, 170, 0.8);
  background: #7a1522;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.45);
  transition: 0.35s;
}

.login-btn:hover {
  background: #9c2030;
  border-color: #ffd7aa;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 14px;
}

.user-name {
  font-weight: 700;
  color: #ffd7aa;
}

.logout-btn {
  background: #5a101a;
  padding: 8px 18px;
  border-radius: 20px;
  color: #fff;
  border: none;
  cursor: pointer;
  transition: 0.3s;
}

.logout-btn:hover {
  background: #9c2030;
}

.filters {
  display: flex;
  gap: 12px;
  justify-content: center;
  margin-bottom: 30px;
}

.filter-btn {
  padding: 8px 18px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.08);
  color: #f8ece4;
  border: 1.5px solid rgba(255, 255, 255, 0.35);
  transition: 0.3s;
}

.filter-btn.active,
.filter-btn:hover {
  background: #8a1c2b;
  border-color: #f8ece4;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 28px;
}

.card {
  background: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(14px);
  border-radius: 18px;
  padding: 18px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: transform 0.28s ease, box-shadow 0.28s ease;
  cursor: pointer;
  outline: none;
}

.card:hover {
  transform: translateY(-6px);
  box-shadow: 0 14px 36px rgba(0, 0, 0, 0.45);
}

.card:focus-visible,
.details-close:focus-visible,
.details-add-btn:focus-visible {
  outline: 2px solid #ffd7aa;
  outline-offset: 3px;
}

.media {
  position: relative;
  width: 100%;
  border-radius: 14px;
  overflow: hidden;
  aspect-ratio: 16 / 9;
  min-height: 170px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.1);
  margin-bottom: 14px;
}

.card-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  display: block;
  transform: scale(1);
  transition: transform 0.35s ease, filter 0.25s ease;
}

.media-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, rgba(0, 0, 0, 0.06), rgba(0, 0, 0, 0.38));
  pointer-events: none;
}

.card:hover .card-img {
  transform: scale(1.04);
  filter: brightness(1.06) contrast(1.03);
}

.card-body h2 {
  font-size: 1.22rem;
  margin: 0;
}

.tap-hint {
  margin: 8px 0 0;
  font-size: 0.84rem;
  opacity: 0.72;
  letter-spacing: 0.02em;
  text-transform: uppercase;
}

.placeholder-img {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(248, 236, 228, 0.75);
  font-weight: 600;
  letter-spacing: 0.3px;
}

.img-center {
  object-position: center;
}

.details-backdrop {
  position: fixed;
  inset: 0;
  z-index: 4100;
  background: rgba(0, 0, 0, 0.65);
  backdrop-filter: blur(18px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 18px;
}

.details-modal {
  width: min(560px, 92vw);
  max-height: 78vh;
  overflow: hidden;
  border-radius: 22px;
  background: rgba(10, 10, 10, 0.72);
  border: 1px solid rgba(255, 255, 255, 0.12);
  box-shadow: 0 18px 60px rgba(0, 0, 0, 0.65);
  backdrop-filter: blur(10px);
  position: relative;
  display: flex;
  flex-direction: column;
}

.details-media {
  position: relative;
  height: 240px;
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
  background: linear-gradient(to bottom, rgba(0, 0, 0, 0.08), rgba(0, 0, 0, 0.5));
  pointer-events: none;
}

.details-close {
  position: absolute;
  top: 12px;
  right: 12px;
  z-index: 2;
  width: 34px;
  height: 34px;
  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: 999px;
  background: rgba(0, 0, 0, 0.5);
  color: #fff;
  cursor: pointer;
}

.details-content {
  padding: 18px 20px 20px;
  overflow-y: auto;
}

.category-chip {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.24);
  font-size: 0.7rem;
  letter-spacing: 0.07em;
  opacity: 0.86;
  margin-bottom: 10px;
}

.details-title {
  margin: 0;
  font-size: clamp(1.3rem, 3.6vw, 1.5rem);
  font-weight: 700;
}

.details-description {
  margin: 12px 0 0;
  font-size: 0.95rem;
  opacity: 0.86;
  line-height: 1.4;
}

.details-footer {
  margin-top: 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.details-price {
  margin: 0;
  font-size: 1.12rem;
  font-weight: 700;
  color: #ffd7aa;
}

.details-add-btn {
  border: none;
  border-radius: 12px;
  padding: 11px 18px;
  color: #fff;
  font-weight: 600;
  background: linear-gradient(135deg, #6f1220 0%, #9f2132 55%, #bb3143 100%);
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.details-add-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 26px rgba(158, 40, 58, 0.48);
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
  padding: 28px;
  width: 350px;
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.18);
}

.tabs {
  display: flex;
  margin-bottom: 18px;
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
  transition: 0.25s;
}

.tabs button.active {
  opacity: 1;
  border-bottom: 2px solid #ffd7aa;
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

@media (max-width: 520px) {
  .media {
    aspect-ratio: 4 / 3;
    min-height: 190px;
  }

  .details-media {
    height: 200px;
  }

  .details-footer {
    flex-direction: column;
    align-items: stretch;
  }

  .details-add-btn {
    width: 100%;
  }
}

@media (min-width: 900px) {
  .media {
    aspect-ratio: 16 / 8;
    min-height: 190px;
  }
}
</style>
