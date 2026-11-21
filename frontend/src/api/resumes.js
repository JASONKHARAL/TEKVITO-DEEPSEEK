import api from './client.js'

export const listResumes = () => api.get('/resumes')

export const uploadResume = (file) => {
  const form = new FormData()
  form.append('resume', file)
  return api.post('/resumes', form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}

export const deleteResume = (id) => api.delete(`/resumes/${id}`)
export const makePrimary = (id) => api.patch(`/resumes/${id}/primary`)

/**
 * Download a resume with authentication.
 * Triggers a browser download without navigating away.
 */
export const downloadResume = async (id, filename = 'resume') => {
  const res = await api.get(`/resumes/${id}/download`, {
    responseType: 'blob',
  })
  const url = window.URL.createObjectURL(new Blob([res.data]))
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', filename)
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}