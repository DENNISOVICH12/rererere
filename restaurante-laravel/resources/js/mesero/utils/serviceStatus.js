const SERVICE_STEPS = ['pendiente', 'preparando', 'listo', 'entregado'];

export const normalizeStatus = (status) => {
  const normalized = String(status || 'pendiente').toLowerCase();
  return SERVICE_STEPS.includes(normalized) ? normalized : 'pendiente';
};

export const normalizeGroupKey = (group) => {
  const normalized = String(group || '').toLowerCase();
  if (normalized === 'bebida' || normalized === 'bar' || normalized === 'barra') return 'bebida';
  if (normalized === 'plato' || normalized === 'comida' || normalized === 'cocina') return 'plato';
  return normalized || 'plato';
};

export const getGroupStatus = (items = [], grupo) => {
  const normalizedGroup = normalizeGroupKey(grupo);
  const groupItems = items.filter((item) => normalizeGroupKey(item?.grupo_servicio || item?.categoria) === normalizedGroup);

  if (!groupItems.length) return 'pendiente';

  const statuses = groupItems.map((item) => normalizeStatus(item?.estado_servicio || item?._serviceStatus || item?.estado));

  if (statuses.every((status) => status === 'pendiente')) return 'pendiente';
  if (statuses.some((status) => status === 'preparando')) return 'preparando';
  if (statuses.every((status) => status === 'listo')) return 'listo';
  if (statuses.every((status) => status === 'entregado')) return 'entregado';

  if (statuses.some((status) => status === 'pendiente')) return 'pendiente';
  if (statuses.some((status) => status === 'listo')) return 'listo';
  return 'pendiente';
};

export { SERVICE_STEPS };
