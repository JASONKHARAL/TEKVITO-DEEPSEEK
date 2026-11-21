<?php
// frontend/setup_admin_users_ui.php
// Adds /admin/users page.

declare(strict_types=1);

$base = __DIR__;

$files = [

'src/api/adminUsers.js' => <<<'JS'
import api from './client.js'

export const listUsers = (params = {}) => api.get('/admin/users', { params })
export const setUserRole = (id, role) => api.patch(`/admin/users/${id}/role`, { role })
export const setUserStatus = (id, status) => api.patch(`/admin/users/${id}/status`, { status })
JS,

'src/pages/AdminUsers.jsx' => <<<'JSX'
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
JSX,

'src/routes/AppRoutes.jsx' => <<<'JSX'
import { Routes, Route } from 'react-router-dom'
import Home from '../pages/Home.jsx'
import Jobs from '../pages/Jobs.jsx'
import JobDetail from '../pages/JobDetail.jsx'
import NewJob from '../pages/NewJob.jsx'
import MyJobs from '../pages/MyJobs.jsx'
import EditJob from '../pages/EditJob.jsx'
import JobVersions from '../pages/JobVersions.jsx'
import Profile from '../pages/Profile.jsx'
import Applications from '../pages/Applications.jsx'
import ApplicationDetail from '../pages/ApplicationDetail.jsx'
import ReceivedApplications from '../pages/ReceivedApplications.jsx'
import ReceivedApplicationDetail from '../pages/ReceivedApplicationDetail.jsx'
import AdminUsers from '../pages/AdminUsers.jsx'
import Login from '../pages/Login.jsx'
import Register from '../pages/Register.jsx'
import Dashboard from '../pages/Dashboard.jsx'
import ProtectedRoute from '../components/ProtectedRoute.jsx'

export default function AppRoutes() {
  return (
    <Routes>
      <Route path="/" element={<Home />} />
      <Route path="/jobs" element={<Jobs />} />
      <Route path="/jobs/:id" element={<JobDetail />} />
      <Route path="/login" element={<Login />} />
      <Route path="/register" element={<Register />} />
      <Route path="/dashboard" element={<ProtectedRoute><Dashboard /></ProtectedRoute>} />
      <Route path="/profile" element={<ProtectedRoute roles={['candidate']}><Profile /></ProtectedRoute>} />
      <Route path="/applications" element={<ProtectedRoute roles={['candidate']}><Applications /></ProtectedRoute>} />
      <Route path="/applications/:id" element={<ProtectedRoute roles={['candidate']}><ApplicationDetail /></ProtectedRoute>} />
      <Route path="/my-jobs" element={<ProtectedRoute roles={['super_admin', 'recruiter']}><MyJobs /></ProtectedRoute>} />
      <Route path="/my-jobs/:id/edit" element={<ProtectedRoute roles={['super_admin', 'recruiter']}><EditJob /></ProtectedRoute>} />
      <Route path="/my-jobs/:id/versions" element={<ProtectedRoute roles={['super_admin', 'recruiter']}><JobVersions /></ProtectedRoute>} />
      <Route path="/jobs/new" element={<ProtectedRoute roles={['super_admin', 'recruiter']}><NewJob /></ProtectedRoute>} />
      <Route path="/received-applications" element={<ProtectedRoute roles={['super_admin', 'recruiter']}><ReceivedApplications /></ProtectedRoute>} />
      <Route path="/received-applications/:id" element={<ProtectedRoute roles={['super_admin', 'recruiter']}><ReceivedApplicationDetail /></ProtectedRoute>} />
      <Route path="/admin/users" element={<ProtectedRoute roles={['super_admin']}><AdminUsers /></ProtectedRoute>} />
    </Routes>
  )
}
JSX,

'src/components/Navbar.jsx' => <<<'JSX'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext.jsx'

export default function Navbar() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const isCandidate = user?.roles?.includes('candidate')
  const isAdmin = user?.roles?.some((r) => ['super_admin', 'recruiter'].includes(r))
  const isSuperAdmin = user?.roles?.includes('super_admin')

  const handleLogout = () => { logout(); navigate('/') }

  return (
    <nav className="navbar">
      <Link to="/" className="brand">TEKVITO</Link>
      <div className="nav-links">
        <Link to="/">Home</Link>
        <Link to="/jobs">Jobs</Link>
        {user && <Link to="/dashboard">Dashboard</Link>}
        {user && isCandidate && <Link to="/applications">My Applications</Link>}
        {user && isAdmin && <Link to="/my-jobs">My Jobs</Link>}
        {user && isAdmin && <Link to="/received-applications">Applicants</Link>}
        {user && isSuperAdmin && <Link to="/admin/users">Users</Link>}
        {user ? (
          <>
            <Link to="/profile">Profile</Link>
            <span className="nav-user">{user.first_name} ({user.roles?.[0]})</span>
            <button onClick={handleLogout} className="btn-link">Logout</button>
          </>
        ) : (
          <>
            <Link to="/login">Login</Link>
            <Link to="/register">Register</Link>
          </>
        )}
      </div>
    </nav>
  )
}
JSX,

];

foreach ($files as $rel => $content) {
    $fullPath = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $dir = dirname($fullPath);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents($fullPath, $content);
    echo "Wrote: $fullPath\n";
}

// Append CSS for status pills (pending etc.)
$extraCss = <<<'CSS'

.status-pill.status-active { background: #d1fae5; color: #065f46; }
.status-pill.status-pending { background: #fef3c7; color: #92400e; }
.status-pill.status-suspended { background: #fee2e2; color: #991b1b; }
.status-pill.status-deactivated { background: #f3f4f6; color: #6b7280; }
CSS;

$cssPath = $base . '/src/index.css';
$existing = is_file($cssPath) ? file_get_contents($cssPath) : '';
if (strpos($existing, '.status-pill.status-active') === false) {
    file_put_contents($cssPath, $existing . "\n" . $extraCss);
    echo "Appended CSS\n";
}

echo "\nDone. Vite will hot-reload.\n";