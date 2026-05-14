import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

// Inicialización lazy: solo crea Echo una vez y lo reutiliza.
// Si el WS server no está disponible, no bloquea ni lanza errores fatales.
function getEcho() {
  if (window.Echo) return window.Echo

  try {
    window.Echo = new Echo({
      broadcaster:  'pusher',
      key:          'local',
      cluster:      'mt1',
      wsHost:       window.location.hostname,
      wsPort:       6001,
      wssPort:      6001,
      forceTLS:     false,
      disableStats: true,
      enabledTransports: ['ws'],
    })
  } catch (e) {
    console.warn('[Echo] No se pudo inicializar:', e.message)
    window.Echo = null
  }

  return window.Echo
}

/**
 * Suscribe al canal privado de notificaciones del mesero.
 * Retorna una función de cleanup para desuscribirse.
 *
 * Uso en páginas (MesasView, MesaDetalleView):
 *   const stop = bindWaiterRealtime(1, { onNotification: (e) => ... })
 *   // para limpiar: stop()
 *
 * Uso en WaiterNotifications (acceso directo al canal):
 *   const { channel, stop } = bindWaiterRealtimeChannel(1)
 */
export const bindWaiterRealtime = (restaurantId, handlers = {}) => {
  const echo = getEcho()
  if (!echo) return () => {}

  let channel
  try {
    channel = echo.private(`restaurant.${restaurantId}.waiters`)
  } catch (e) {
    console.warn('[Echo] Error al unirse al canal:', e.message)
    return () => {}
  }

  const onNotification = (event) => {
    handlers.onNotification?.(event)
  }

  channel.listen('.waiter.notification.created', onNotification)

  return () => {
    try {
      channel.stopListening('.waiter.notification.created')
    } catch (_) {}
  }
}

/**
 * Versión que expone el canal directamente (para WaiterNotifications).
 * Retorna { channel, stop }
 */
export const bindWaiterRealtimeChannel = (restaurantId) => {
  const echo = getEcho()
  if (!echo) return { channel: null, stop: () => {} }

  let channel
  try {
    channel = echo.private(`restaurant.${restaurantId}.waiters`)
  } catch (e) {
    console.warn('[Echo] Error al unirse al canal:', e.message)
    return { channel: null, stop: () => {} }
  }

  const stop = () => {
    try { echo.leave(`restaurant.${restaurantId}.waiters`) } catch (_) {}
  }

  return { channel, stop }
}