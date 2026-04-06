// src/cliente.js
import { ref } from "vue"

export const cliente = ref(null)

export function setCliente(data) {

  const stored = {
    id: data.id,
    nombres: data.nombres,
    apellidos: data.apellidos,
    correo: data.correo,
    token: data.token || null
  }

  cliente.value = stored

  localStorage.setItem("cliente", JSON.stringify(stored))
}

export function getCliente() {
  return cliente.value
}

export function loadCliente() {
  const stored = localStorage.getItem("cliente")

  if (stored) {
    cliente.value = JSON.parse(stored)
  }
}

function clearClienteState() {
  cliente.value = null
  localStorage.removeItem("cliente")
}

export async function logoutCliente() {
  if (!confirm('¿Seguro que deseas cerrar sesión?')) return false

  try {
    const token = document
      .querySelector('meta[name="csrf-token"]')
      ?.getAttribute('content')

    await fetch('/logout', {
      method: 'POST',
      headers: {
        ...(token ? { 'X-CSRF-TOKEN': token } : {}),
        Accept: 'application/json'
      }
    })

    clearClienteState()
    window.location.href = '/login'
    return true
  } catch (error) {
    console.error('Error al cerrar sesión:', error)
    return false
  }
}
