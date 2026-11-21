import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { getDashboardStats } from '../api/dashboard.js'
import { useAuth } from '../context/AuthContext.jsx'

export default function Dashboard() {
  const { user } = useAuth()
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    getDashboardStats()
      .then((r) => setData(r.data))
      .catch((e) => setError(e?.response?.data?.error || 'Failed to load dashboard'))
      .finally(() => setLoading(false))
  }, [])

  if (loading) return <div className="page"><p>Loading dashboard…</p></div>
  if (error) return <div className="page"><p className="error">{error}</p></div>
  if (!data) return null

  if (data.view === 'candidate') return <CandidateDashboard data={data} user={user} />
  if (data.view === 'admin') return <AdminDashboard data={data} user={user} />

  return <div className="page"><p>No dashboard available.</p></div>
}

function StatCard({ label, value, color = '#111827' }) {
  return (
    <div className="stat-card">
      <div className="stat-value" style={{ color }}>{value}</div>
      <div className="stat-label">{label}</div>
    </div>
  )
}

function CandidateDashboard({ data, user }) {
  const s = data.stats
  return (
    <div className="page">
      <h1>Dashboard</h1>
      <p className="muted">Welcome back, {user.first_name}.</p>

      <div className="stat-grid">
        <StatCard label="Applications" value={s.applications} />
        <StatCard label="Resumes" value={s.resumes} />
        <StatCard label="Skills" value={s.skills} />
      </div>

      <div className="card" style={{ marginTop: 24 }}>
        <h2>Applications by status</h2>
        {Object.keys(s.by_status || {}).length === 0 ? (
          <p className="muted">No applications yet. <Link to="/jobs">Browse jobs</Link></p>
        ) : (
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <tbody>
              {Object.entries(s.by_status).map(([status, count]) => (
                <tr key={status} style={{ borderBottom: '1px solid #f3f4f6' }}>
                  <td style={{ padding: 10 }}>
                    <span className={`status-pill status-${status}`}>{status.replace('_', ' ')}</span>
                  </td>
                  <td style={{ padding: 10, textAlign: 'right', fontWeight: 600 }}>{count}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      <div style={{ marginTop: 24, display: 'flex', gap: 10 }}>
        <Link to="/applications" className="btn btn-primary">My Applications</Link>
        <Link to="/jobs" className="btn btn-secondary">Browse Jobs</Link>
      </div>
    </div>
  )
}

function AdminDashboard({ data, user }) {
  const s = data.stats
  return (
    <div className="page">
      <h1>Admin Dashboard</h1>
      <p className="muted">Welcome back, {user.first_name}.</p>

      <div className="stat-grid">
        <StatCard label="Total Users" value={s.users} color="#2563eb" />
        <StatCard label="Jobs" value={s.jobs} color="#7c3aed" />
        <StatCard label="Applications" value={s.applications} color="#0891b2" />
        <StatCard label="Hires" value={s.hires} color="#16a34a" />
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20, marginTop: 24 }}>
        <div className="card">
          <h2>Jobs by status</h2>
          {Object.keys(s.jobs_by_status || {}).length === 0 ? (
            <p className="muted">No jobs yet.</p>
          ) : (
            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
              <tbody>
                {Object.entries(s.jobs_by_status).map(([status, count]) => (
                  <tr key={status} style={{ borderBottom: '1px solid #f3f4f6' }}>
                    <td style={{ padding: 8 }}>
                      <span className={`status-pill status-${status}`}>{status.replace('_', ' ')}</span>
                    </td>
                    <td style={{ padding: 8, textAlign: 'right', fontWeight: 600 }}>{count}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>

        <div className="card">
          <h2>Applications by status</h2>
          {Object.keys(s.apps_by_status || {}).length === 0 ? (
            <p className="muted">No applications yet.</p>
          ) : (
            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
              <tbody>
                {Object.entries(s.apps_by_status).map(([status, count]) => (
                  <tr key={status} style={{ borderBottom: '1px solid #f3f4f6' }}>
                    <td style={{ padding: 8 }}>
                      <span className={`status-pill status-${status}`}>{status.replace('_', ' ')}</span>
                    </td>
                    <td style={{ padding: 8, textAlign: 'right', fontWeight: 600 }}>{count}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20, marginTop: 20 }}>
        <div className="card">
          <h2>Recent applications</h2>
          {data.recent_applications.length === 0 ? (
            <p className="muted">No applications yet.</p>
          ) : (
            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
              <tbody>
                {data.recent_applications.map((a) => (
                  <tr key={a.id} style={{ borderBottom: '1px solid #f3f4f6' }}>
                    <td style={{ padding: 8 }}>
                      <Link to={`/received-applications/${a.id}`}>
                        <strong>{a.first_name} {a.last_name}</strong>
                      </Link>
                      <div className="muted" style={{ fontSize: 13 }}>{a.job_title}</div>
                    </td>
                    <td style={{ padding: 8, textAlign: 'right' }}>
                      <span className={`status-pill status-${a.status}`}>{a.status.replace('_', ' ')}</span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>

        <div className="card">
          <h2>Top jobs by applications</h2>
          {data.top_jobs.length === 0 ? (
            <p className="muted">No applications yet.</p>
          ) : (
            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
              <tbody>
                {data.top_jobs.map((j) => (
                  <tr key={j.id} style={{ borderBottom: '1px solid #f3f4f6' }}>
                    <td style={{ padding: 8 }}>
                      <Link to={`/jobs/${j.id}`}><strong>{j.title}</strong></Link>
                    </td>
                    <td style={{ padding: 8, textAlign: 'right', fontWeight: 600 }}>{j.app_count}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>

      <div style={{ marginTop: 24, display: 'flex', gap: 10 }}>
        <Link to="/my-jobs" className="btn btn-primary">My Jobs</Link>
        <Link to="/jobs/new" className="btn btn-secondary">Post New Job</Link>
        <Link to="/received-applications" className="btn btn-secondary">All Applicants</Link>
      </div>
    </div>
  )
}