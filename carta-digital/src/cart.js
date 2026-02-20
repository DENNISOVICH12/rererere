import { ref } from "vue" 

const CART_STORAGE_KEY = "cart"

function readCart() {
  try {
    const stored = localStorage.getItem(CART_STORAGE_KEY)
    return stored ? JSON.parse(stored) : []
  } catch (error) {
    return []
  }
}

export const cart = ref(readCart())

export function saveCart() {
  localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart.value))
  window.dispatchEvent(new CustomEvent("cart-updated"))
}

export function addToCart(product) {
  const item = cart.value.find(p => p.id === product.id)
  if (item) {
    item.quantity++
  } else {
    cart.value.push({ ...product, quantity: 1, nota: product.nota ?? null })
  }

  saveCart()
}

export function removeFromCart(id) {
  cart.value = cart.value.filter(item => item.id !== id)
  saveCart()
}

export function clearCart() {
  cart.value = []
  saveCart()
}

/**
 * ✅ NUEVO:
 * Permite que cualquier parte de la interfaz pueda abrir el modal de login
 */
export function openLoginModal() {
  window.dispatchEvent(new CustomEvent("open-login"))
}
