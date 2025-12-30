<template>
  <div class="login-box">
    <h2>Iniciar Sesión</h2>
    <input v-model="email" placeholder="Correo" />
    <input type="password" v-model="password" placeholder="Contraseña" />
    <button @click="login">Ingresar</button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { setCliente } from '../auth'

const email = ref('')
const password = ref('')

async function login() {
  const res = await axios.post('http://192.168.1.160:8000/api/login', {
    email: email.value,
    password: password.value
  })

  setCliente(res.data.user) // Guardamos al cliente
  alert("Sesión iniciada ✅")
  window.location.href = "/"
}
</script>
