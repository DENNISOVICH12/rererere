import axios from 'axios'
import { API_BASE } from '../api'

const kitchenClient = axios.create({
  baseURL: API_BASE,
  timeout: 10000,
})

export async function fetchKitchenOrders() {
  const { data } = await kitchenClient.get('/kitchen/orders')
  return data?.data ?? data ?? []
}

export async function startKitchenOrder(orderId) {
  const { data } = await kitchenClient.patch(`/kitchen/orders/${orderId}/start`)
  return data
}

export async function readyKitchenOrder(orderId) {
  const { data } = await kitchenClient.patch(`/kitchen/orders/${orderId}/ready`)
  return data
}

export async function deliverKitchenOrder(orderId) {
  const { data } = await kitchenClient.patch(`/kitchen/orders/${orderId}/deliver`)
  return data
}
