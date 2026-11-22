import api from './client.js'

export const getJobSkills = (jobId) => api.get(`/jobs/${jobId}/skills`)
export const setJobSkills = (jobId, skillIds) => api.put(`/jobs/${jobId}/skills`, { skill_ids: skillIds })