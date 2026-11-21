import api from './client.js'

export const listNotifications = () => api.get('/notifications')
export const getUnreadCount = () => api.get('/notifications/unread-count')
export const markNotificationRead = (id) => api.patch(`/notifications/${id}/read`)
export const markAllRead = () => api.patch('/notifications/read-all')