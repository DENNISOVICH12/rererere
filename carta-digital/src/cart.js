import { ref } from "vue" 

export const cart = ref([])

export function addToCart(product) {
  const item = cart.value.find(p => p.id === product.id)
  if (item) {
    item.quantity++
  } else {
    cart.value.push({ ...product, quantity: 1 })
  }

  // 🔔 Notificar actualización visual del carrito
  window.dispatchEvent(new CustomEvent("cart-updated"))
}

export function removeFromCart(id) {
  cart.value = cart.value.filter(item => item.id !== id)
}

export function clearCart() {
  cart.value = []
}

/**
 * ✅ NUEVO:
 * Permite que cualquier parte de la interfaz pueda abrir el modal de login
 */
export function openLoginModal() {
  window.dispatchEvent(new CustomEvent("open-login"))
}
