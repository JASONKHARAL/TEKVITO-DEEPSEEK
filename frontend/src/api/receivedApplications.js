import api from './client.js'

export const receivedApplications = () => api.get('/received-applications')
export const receivedApplication = (id) => api.get(`/received-applications/${id}`)
export const updateApplicationStatus = (id, status, notes = null) =>
  api.patch(`/received-applications/${id}/status`, { status, notes })