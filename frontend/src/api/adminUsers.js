import api from './client.js'

export const listUsers = (params = {}) => api.get('/admin/users', { params })
export const setUserRole = (id, role) => api.patch(`/admin/users/${id}/role`, { role })
export const setUserStatus = (id, status) => api.patch(`/admin/users/${id}/status`, { status })