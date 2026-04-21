export const createWaiterEcho = (restaurantId) => {
  if (!restaurantId || !window.Echo) return null;
  return window.Echo.private(`restaurant.${restaurantId}.waiters`);
};

export const bindWaiterRealtime = (restaurantId, handlers = {}) => {
  const channel = createWaiterEcho(restaurantId);
  if (!channel) return () => {};

  const onNotification = (event) => {
    handlers.onNotification?.(event);
  };

  channel.listen('.waiter.notification.created', onNotification);

  return () => {
    if (channel?.stopListening) {
      channel.stopListening('.waiter.notification.created');
    }
  };
};
