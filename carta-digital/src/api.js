const DEFAULT_API_PORT = 8000

function buildDefaultApiBase() {
  if (typeof window === 'undefined') {
    return `http://localhost:${DEFAULT_API_PORT}/api`
  }

  return `${window.location.protocol}//${window.location.hostname}:${DEFAULT_API_PORT}/api`
}

export const API_BASE = (import.meta.env.VITE_API_URL || buildDefaultApiBase()).replace(/\/$/, '')
