<template>
  <div style="display:flex;justify-content:center;align-items:center;height:100vh;background:#111;">
    <div style="background:#222;padding:30px;border-radius:10px;color:white;width:300px;text-align:center;">
      <h2>Login Staff</h2>

      <input v-model="email" placeholder="Usuario" style="width:100%;margin:10px 0;padding:10px" />
      <input v-model="password" type="password" placeholder="Contraseña" style="width:100%;margin:10px 0;padding:10px" />

      <button @click="login" style="width:100%;padding:10px;background:red;color:white;">
        Entrar
      </button>

      <p v-if="error" style="color:red">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { ref } from 'vue'

const email = ref('')
const password = ref('')
const error = ref('')

const login = async () => {
  try {
    const res = await axios.post('/api/login', {
      email: email.value,
      password: password.value
    })

    const user = res.data.user

    // 🔥 REDIRECCIÓN POR ROL
    if (user.rol === 'admin') {
      window.location.href = '/admin'
    } else if (user.rol === 'mesero') {
      window.location.href = '/mesero'
    } else if (user.rol === 'cocinero') {
      window.location.href = '/cocina'
    }

  } catch (e) {
    error.value = 'Credenciales incorrectas'
  }
}
</script>