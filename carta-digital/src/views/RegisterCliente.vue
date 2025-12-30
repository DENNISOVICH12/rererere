<template>
  <div class="login-box">
    <h2>Registrarse</h2>
    <input v-model="nombre" placeholder="Nombre" />
    <input v-model="email" placeholder="Correo" />
    <input type="password" v-model="password" placeholder="Contraseña" />
    <button @click="register">Crear Cuenta</button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { setCliente } from '../auth'

const nombre = ref('')
const email = ref('')
const password = ref('')

async function register() {
  const res = await axios.post('http://172.18.112.238:8000/api/register-cliente', {
    nombre: nombre.value,
    email: email.value,
    password: password.value
  })

  setCliente(res.data.user)
  alert("Cuenta creada ✅")
  window.location.href = "/"
}
</script>
