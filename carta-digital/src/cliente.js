// src/cliente.js 
import { ref } from "vue"

export const cliente = ref(null)

export function setCliente(data) {
  const stored = {
    id: data.id,
    usuario: data.usuario,
    nombre: data.nombre ?? data.usuario
  };

  cliente.value = stored; // ✅ Hacerlo reactivo
  localStorage.setItem('cliente', JSON.stringify(stored));
}

export function getCliente() {
  return cliente.value; // ✅ ya no tomamos de localStorage
}

export function loadCliente() {
  const stored = localStorage.getItem("cliente")
  if (stored) cliente.value = JSON.parse(stored) // ✅ carga a la reactividad
}

export function logoutCliente() {
  cliente.value = null
  localStorage.removeItem("cliente")
}
