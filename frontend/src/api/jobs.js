import api from './client.js'

export const listJobs = (params = {}) => api.get('/jobs', { params })
export const getJob = (id) => api.get(`/jobs/${id}`)
export const getCategories = () => api.get('/jobs/categories')
export const createJob = (payload) => api.post('/jobs', payload)
export const updateJob = (id, payload) => api.put(`/jobs/${id}`, payload)
export const updateJobStatus = (id, status, notes = null) =>
  api.patch(`/jobs/${id}/status`, { status, notes })
export const getJobVersions = (id) => api.get(`/jobs/${id}/versions`)
export const getJobVersionDetail = (id, vnum) => api.get(`/jobs/${id}/versions/${vnum}`)