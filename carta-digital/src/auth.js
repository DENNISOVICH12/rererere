import { ref } from 'vue'

export const cliente = ref(JSON.parse(localStorage.getItem('cliente')) || null)

export function setCliente(data) {
  cliente.value = data
  localStorage.setItem('cliente', JSON.stringify(data))
}

export function logoutCliente() {
  cliente.value = null
  localStorage.removeItem('cliente')
}
