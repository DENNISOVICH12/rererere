export const createWaiterEcho = (restaurantId) => {
  if (!restaurantId || !window.Echo) return null;
  return window.Echo.private(`restaurant.${restaurantId}.waiters`);
};
