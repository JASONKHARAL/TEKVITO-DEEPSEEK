import api from './client.js'

export const submitHireRequest = (payload) => api.post('/hire-request', payload)
export const listTalentRequests = (params = {}) => api.get('/admin/talent-requests', { params })
export const getTalentRequest = (id) => api.get(`/admin/talent-requests/${id}`)
export const updateTalentRequestStatus = (id, status, adminNotes = null) =>
  api.patch(`/admin/talent-requests/${id}/status`, { status, admin_notes: adminNotes })
export const linkJobToRequest = (id, jobId) =>
  api.post(`/admin/talent-requests/${id}/link-job`, { job_id: jobId })