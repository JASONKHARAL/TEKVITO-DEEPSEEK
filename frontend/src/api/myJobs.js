import api from './client.js'

export const myJobs = () => api.get('/my/jobs')
export const myJob = (id) => api.get(`/my/jobs/${id}`)