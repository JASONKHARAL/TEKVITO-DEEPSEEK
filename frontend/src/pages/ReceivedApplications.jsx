import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { receivedApplications } from '../api/receivedApplications.js'

export default function ReceivedApplications() {
  const [apps, setApps] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    receivedApplications()
      .then((r) => setApps(r.data.data || []))
      .catch((e) => setError(e?.response?.data?.error || 'Failed to load'))
      .finally(() => setLoading(false))
  }, [])

  if (loading) return <div className="page"><p>Loading…</p></div>

  return (
    <div className="page">
      <h1>Applications Received</h1>
      <p className="muted">Applications submitted to jobs you posted.</p>

      {error && <p className="error">{error}</p>}

      {apps.length === 0 && !error && (
        <div className="card">
          <p>No applications yet.</p>
        </div>
      )}

      {apps.length > 0 && (
        <div className="card">
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <thead>
              <tr style={{ textAlign: 'left', borderBottom: '1px solid #e5e7eb' }}>
                <th style={{ padding: 10 }}>Candidate</th>
                <th style={{ padding: 10 }}>Job</th>
                <th style={{ padding: 10 }}>Status</th>
                <th style={{ padding: 10 }}>Applied</th>
                <th style={{ padding: 10 }}>Action</th>
              </tr>
            </thead>
            <tbody>
              {apps.map((a) => (
                <tr key={a.id} style={{ borderBottom: '1px solid #f3f4f6' }}>
                  <td style={{ padding: 10 }}>
                    <strong>{a.first_name} {a.last_name}</strong>
                    <div className="muted" style={{ fontSize: 13 }}>{a.email}</div>
                  </td>
                  <td style={{ padding: 10 }}>{a.job_title}</td>
                  <td style={{ padding: 10 }}>
                    <span className={`status-pill status-${a.status}`}>{a.status.replace('_', ' ')}</span>
                  </td>
                  <td style={{ padding: 10, color: '#6b7280', fontSize: 13 }}>
                    {new Date(a.applied_at.replace(' ', 'T')).toLocaleDateString()}
                  </td>
                  <td style={{ padding: 10 }}>
                    <Link to={`/received-applications/${a.id}`} className="btn btn-secondary">View</Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}