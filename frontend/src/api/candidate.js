import api from './client.js'

export const getProfile = () => api.get('/candidate/profile')
export const updateProfile = (payload) => api.put('/candidate/profile', payload)