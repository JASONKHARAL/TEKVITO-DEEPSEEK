import api from './client.js'

export const listSavedJobs = () => api.get('/saved-jobs')
export const saveJob = (id) => api.post(`/jobs/${id}/save`)
export const unsaveJob = (id) => api.delete(`/jobs/${id}/save`)
export const isJobSaved = (id) => api.get(`/jobs/${id}/is-saved`)