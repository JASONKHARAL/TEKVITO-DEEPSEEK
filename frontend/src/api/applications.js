import api from './client.js'

export const applyToJob = (jobId, coverLetter) =>
  api.post(`/jobs/${jobId}/apply`, { cover_letter: coverLetter })

export const myApplications = () => api.get('/applications')
export const getApplication = (id) => api.get(`/applications/${id}`)
export const hasApplied = (jobId) => api.get(`/jobs/${jobId}/has-applied`)
export const withdrawApplication = (id) => api.post(`/applications/${id}/withdraw`)