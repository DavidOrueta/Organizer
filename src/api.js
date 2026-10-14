const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || ''
const tokenKey = 'organizer-api-token'

export const apiEnabled = Boolean(apiBaseUrl)

export function setApiToken(token) {
  if (token) localStorage.setItem(tokenKey, token)
  else localStorage.removeItem(tokenKey)
  localStorage.removeItem('nexo-api-token')
}

export async function apiRequest(path, options = {}) {
  const headers = { Accept: 'application/json', ...options.headers }
  const token = localStorage.getItem(tokenKey) || localStorage.getItem('nexo-api-token')
  if (token) headers.Authorization = `Bearer ${token}`
  if (options.body) headers['Content-Type'] = 'application/json'

  const response = await fetch(`${apiBaseUrl}${path}`, { ...options, headers })
  const data = await response.json().catch(() => ({}))
  if (!response.ok) throw new Error(data.message || 'No se pudo completar la solicitud.')
  return data
}