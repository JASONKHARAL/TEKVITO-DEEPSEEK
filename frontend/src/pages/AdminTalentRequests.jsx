import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { listTalentRequests } from '../api/talentRequests.js'

export default function AdminTalentRequests() {
  const [requests, setRequests] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [statusFilter, setStatusFilter] = useState('')

  const load = () => {
    setLoading(true)
    listTalentRequests(statusFilter ? { status: statusFilter } : {})
      .then((r) => setRequests(r.data.data || []))
      .catch((e) => setError(e?.response?.data?.error || 'Failed to load'))
      .finally(() => setLoading(false))
  }

  useEffect(load, [statusFilter])

  return (
    <div className="page">
      <h1>Talent Requests</h1>
      <p className="muted">Employers who asked TEKVITO for help hiring.</p>

      <div className="filters">
        <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
          <option value="">All statuses</option>
          <option value="new">New</option>
          <option value="in_progress">In progress</option>
          <option value="posted">Posted</option>
          <option value="closed">Closed</option>
        </select>
      </div>

      {error && <p className="error">{error}</p>}
      {loading && <p>LoadingÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¦</p>}

      {!loading && requests.length === 0 && (
        <div className="card"><p>No talent requests yet.</p></div>
      )}

      {requests.length > 0 && (
        <div className="card">
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <thead>
              <tr style={{ textAlign: 'left', borderBottom: '1px solid #e5e7eb' }}>
                <th style={{ padding: 10 }}>Company</th>
                <th style={{ padding: 10 }}>Job title</th>
                <th style={{ padding: 10 }}>Contact</th>
                <th style={{ padding: 10 }}>Status</th>
                <th style={{ padding: 10 }}>Date</th>
                <th style={{ padding: 10 }}>Action</th>
              </tr>
            </thead>
            <tbody>
              {requests.map((r) => (
                <tr key={r.id} style={{ borderBottom: '1px solid #f3f4f6' }}>
                  <td data-label="Company" style={{ padding: 10 }}>
                    <strong>{r.company_name}</strong>
                  </td>
                  <td data-label="Job title" style={{ padding: 10 }}>{r.job_title}</td>
                  <td data-label="Contact" style={{ padding: 10, fontSize: 13, color: '#6b7280' }}>
                    {r.contact_name}<br/>{r.contact_email}
                  </td>
                  <td data-label="Status" style={{ padding: 10 }}>
                    <span className={`status-pill status-${r.status}`}>{r.status.replace('_', ' ')}</span>
                  </td>
                  <td data-label="Date" style={{ padding: 10, fontSize: 13, color: '#6b7280' }}>
                    {new Date(r.created_at.replace(' ', 'T')).toLocaleDateString()}
                  </td>
                  <td data-label="Action" style={{ padding: 10 }}>
                    <Link to={`/admin/talent-requests/${r.id}`} className="btn btn-secondary">View</Link>
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