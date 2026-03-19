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

export async function startKitchenService(orderId, grupo) {
  const { data } = await kitchenClient.put(`/pedidos/${orderId}/servicio/${grupo}`)
  return data
}
