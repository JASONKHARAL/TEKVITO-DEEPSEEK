import { useEffect, useState } from 'react'
import { listUsers, setUserRole, setUserStatus } from '../api/adminUsers.js'
import { useAuth } from '../context/AuthContext.jsx'

const ROLES = ['super_admin', 'recruiter', 'candidate', 'employer', 'vendor']
const STATUSES = ['active', 'pending', 'suspended', 'deactivated']

export default function AdminUsers() {
  const { user } = useAuth()
  const [users, setUsers] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [filters, setFilters] = useState({ role: '', status: '', q: '' })
  const [busyId, setBusyId] = useState(null)

  const load = () => {
    setLoading(true)
    const params = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== ''))
    listUsers(params)
      .then((r) => setUsers(r.data.data || []))
      .catch((e) => setError(e?.response?.data?.error || 'Failed to load'))
      .finally(() => setLoading(false))
  }

  useEffect(load, [filters])

  const update = (key, value) => setFilters((f) => ({ ...f, [key]: value }))

  const changeRole = async (id, role) => {
    if (!confirm(`Change role to "${role}"?`)) return
    setBusyId(id)
    try { await setUserRole(id, role); load() }
    catch (e) { alert(e?.response?.data?.error || 'Failed') }
    finally { setBusyId(null) }
  }

  const changeStatus = async (id, status) => {
    if (!confirm(`Set status to "${status}"?`)) return
    setBusyId(id)
    try { await setUserStatus(id, status); load() }
    catch (e) { alert(e?.response?.data?.error || 'Failed') }
    finally { setBusyId(null) }
  }

  const isSuperAdmin = user?.roles?.includes('super_admin')
  if (!isSuperAdmin) {
    return <div className="page"><div className="card"><h2>Access denied</h2><p>Only super admins can manage users.</p></div></div>
  }

  return (
    <div className="page">
      <h1>Users</h1>

      <div className="filters">
        <input type="text" placeholder="Search by email or name…" value={filters.q}
          onChange={(e) => update('q', e.target.value)} />
        <select value={filters.role} onChange={(e) => update('role', e.target.value)}>
          <option value="">All roles</option>
          {ROLES.map((r) => <option key={r} value={r}>{r.replace('_', ' ')}</option>)}
        </select>
        <select value={filters.status} onChange={(e) => update('status', e.target.value)}>
          <option value="">All statuses</option>
          {STATUSES.map((s) => <option key={s} value={s}>{s}</option>)}
        </select>
      </div>

      {error && <p className="error">{error}</p>}
      {loading && <p>Loading…</p>}

      {!loading && users.length === 0 && <div className="card"><p>No users found.</p></div>}

      {users.length > 0 && (
        <div className="card">
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <thead>
              <tr style={{ textAlign: 'left', borderBottom: '1px solid #e5e7eb' }}>
                <th style={{ padding: 10 }}>User</th>
                <th style={{ padding: 10 }}>Status</th>
                <th style={{ padding: 10 }}>Role</th>
                <th style={{ padding: 10 }}>Actions</th>
              </tr>
            </thead>
            <tbody>
              {users.map((u) => {
                const currentRole = (u.roles && u.roles[0]) || ''
                return (
                  <tr key={u.id} style={{ borderBottom: '1px solid #f3f4f6', opacity: busyId === u.id ? 0.5 : 1 }}>
                    <td style={{ padding: 10 }}>
                      <strong>{u.first_name} {u.last_name}</strong>
                      <div className="muted" style={{ fontSize: 13 }}>{u.email}</div>
                    </td>
                    <td style={{ padding: 10 }}>
                      <span className={`status-pill status-${u.account_status}`}>{u.account_status}</span>
                    </td>
                    <td style={{ padding: 10 }}>
                      <select value={currentRole} onChange={(e) => changeRole(u.id, e.target.value)} disabled={busyId === u.id}>
                        {ROLES.map((r) => <option key={r} value={r}>{r.replace('_', ' ')}</option>)}
                      </select>
                    </td>
                    <td style={{ padding: 10, display: 'flex', gap: 6, flexWrap: 'wrap' }}>
                      {u.account_status === 'active' ? (
                        <button className="btn btn-danger" onClick={() => changeStatus(u.id, 'suspended')} disabled={busyId === u.id}>Suspend</button>
                      ) : (
                        <button className="btn btn-primary" onClick={() => changeStatus(u.id, 'active')} disabled={busyId === u.id}>Activate</button>
                      )}
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}