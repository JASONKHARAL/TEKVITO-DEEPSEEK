import { useEffect, useRef, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext.jsx'
import { listNotifications, getUnreadCount, markNotificationRead, markAllRead } from '../api/notifications.js'

export default function NotificationBell() {
  const { user } = useAuth()
  const navigate = useNavigate()
  const [open, setOpen] = useState(false)
  const [notifications, setNotifications] = useState([])
  const [unread, setUnread] = useState(0)
  const [loading, setLoading] = useState(false)
  const wrapRef = useRef(null)

  useEffect(() => {
    if (!user) return
    let cancelled = false
    const load = () => {
      getUnreadCount()
        .then((r) => { if (!cancelled) setUnread(r.data.unread_count || 0) })
        .catch(() => {})
    }
    load()
    const interval = setInterval(load, 30000)
    return () => { cancelled = true; clearInterval(interval) }
  }, [user])

  useEffect(() => {
    const onClick = (e) => {
      if (wrapRef.current && !wrapRef.current.contains(e.target)) setOpen(false)
    }
    document.addEventListener('mousedown', onClick)
    return () => document.removeEventListener('mousedown', onClick)
  }, [])

  const toggle = async () => {
    const willOpen = !open
    setOpen(willOpen)
    if (willOpen) {
      setLoading(true)
      try {
        const r = await listNotifications()
        setNotifications(r.data.data || [])
        setUnread(r.data.unread_count || 0)
      } catch {} finally { setLoading(false) }
    }
  }

  const handleClick = async (n) => {
    if (!n.read_at) {
      try {
        const r = await markNotificationRead(n.id)
        setUnread(r.data.unread_count || 0)
        setNotifications((ns) => ns.map((x) => x.id === n.id ? { ...x, read_at: new Date().toISOString() } : x))
      } catch {}
    }
    if (n.action_url) navigate(n.action_url)
    setOpen(false)
  }

  const handleMarkAll = async () => {
    try {
      await markAllRead()
      setUnread(0)
      setNotifications((ns) => ns.map((x) => ({ ...x, read_at: x.read_at || new Date().toISOString() })))
    } catch {}
  }

  if (!user) return null

  return (
    <div className="notif-wrap" ref={wrapRef}>
      <button className="notif-bell" onClick={toggle} aria-label="Notifications">
        🔔
        {unread > 0 && <span className="notif-badge">{unread > 99 ? '99+' : unread}</span>}
      </button>

      {open && (
        <div className="notif-dropdown">
          <div className="notif-header">
            <strong>Notifications</strong>
            {unread > 0 && (
              <button className="btn-link" onClick={handleMarkAll}>Mark all read</button>
            )}
          </div>

          {loading && <p className="muted" style={{ padding: 16 }}>Loading…</p>}

          {!loading && notifications.length === 0 && (
            <p className="muted" style={{ padding: 16 }}>No notifications yet.</p>
          )}

          <div className="notif-list">
            {notifications.slice(0, 8).map((n) => (
              <button
                key={n.id}
                className={`notif-item ${n.read_at ? '' : 'unread'}`}
                onClick={() => handleClick(n)}
              >
                <div style={{ fontWeight: n.read_at ? 400 : 600 }}>{n.title}</div>
                <div className="muted" style={{ fontSize: 13, marginTop: 2 }}>{n.message}</div>
                <div className="muted" style={{ fontSize: 11, marginTop: 4 }}>
                  {new Date(n.created_at.replace(' ', 'T')).toLocaleString()}
                </div>
              </button>
            ))}
          </div>

          <Link to="/notifications" className="notif-footer" onClick={() => setOpen(false)}>
            View all notifications →
          </Link>
        </div>
      )}
    </div>
  )
}