// src/cliente.js
import { ref } from "vue"

export const cliente = ref(null)

export function setCliente(data) {

  const stored = {
    id: data.id,
    nombres: data.nombres,
    apellidos: data.apellidos,
    correo: data.correo
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

export function logoutCliente() {
  cliente.value = null
  localStorage.removeItem("cliente")
}