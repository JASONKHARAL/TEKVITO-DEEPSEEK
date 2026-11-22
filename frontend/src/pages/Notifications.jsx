import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { listNotifications, markNotificationRead, markAllRead } from '../api/notifications.js'

export default function Notifications() {
  const [items, setItems] = useState([])
  const [unread, setUnread] = useState(0)
  const [loading, setLoading] = useState(true)

  const load = () => {
    setLoading(true)
    listNotifications()
      .then((r) => { setItems(r.data.data || []); setUnread(r.data.unread_count || 0) })
      .catch(() => {})
      .finally(() => setLoading(false))
  }

  useEffect(load, [])

  const markOne = async (id) => {
    try { await markNotificationRead(id); load() } catch {}
  }

  const markAll = async () => {
    try { await markAllRead(); load() } catch {}
  }

  return (
    <div className="page">
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <h1>Notifications</h1>
        {unread > 0 && <button className="btn btn-secondary" onClick={markAll}>Mark all read ({unread})</button>}
      </div>

            {loading && (
        <div style={{ marginTop: 16 }}>
          {[1, 2, 3, 4].map((n) => (
            <div key={n} className="skeleton-row">
              <div className="skeleton skeleton-circle"></div>
              <div className="skeleton-row-content">
                <div className="skeleton skeleton-text short"></div>
                <div className="skeleton skeleton-text"></div>
              </div>
            </div>
          ))}
        </div>
      )}

      {!loading && items.length === 0 && (
        <div className="card"><p>No notifications yet.</p></div>
      )}

      <div style={{ marginTop: 16 }}>
        {items.map((n) => (
          <div key={n.id} className={`notif-row ${n.read_at ? '' : 'unread'}`}>
            <div style={{ flex: 1 }}>
              <div style={{ fontWeight: n.read_at ? 400 : 600 }}>{n.title}</div>
              <div className="muted" style={{ marginTop: 4 }}>{n.message}</div>
              <div className="muted" style={{ fontSize: 12, marginTop: 6 }}>
                {new Date(n.created_at.replace(' ', 'T')).toLocaleString()}
              </div>
            </div>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 6, alignItems: 'flex-end' }}>
              {n.action_url && (
                <Link to={n.action_url} className="btn btn-secondary">View</Link>
              )}
              {!n.read_at && (
                <button className="btn-link" onClick={() => markOne(n.id)}>Mark read</button>
              )}
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}