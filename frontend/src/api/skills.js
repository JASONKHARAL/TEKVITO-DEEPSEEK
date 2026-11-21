import api from './client.js'

export const listAllSkills = () => api.get('/skills')
export const mySkills = () => api.get('/candidate/skills')
export const addSkill = (payload) => api.post('/candidate/skills', payload)
export const removeSkill = (skillId) => api.delete(`/candidate/skills/${skillId}`)