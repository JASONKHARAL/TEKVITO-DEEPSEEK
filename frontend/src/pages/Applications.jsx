import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { myApplications, withdrawApplication } from '../api/applications.js'

export default function Applications() {
  const [apps, setApps] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(null)

  const load = () => {
    setLoading(true)
    myApplications()
      .then((r) => setApps(r.data.data || []))
      .catch((e) => setError(e?.response?.data?.error || 'Failed to load'))
      .finally(() => setLoading(false))
  }

  useEffect(load, [])

  const withdraw = async (id) => {
    if (!confirm('Withdraw this application? This cannot be undone.')) return
    setBusy(id)
    try {
      await withdrawApplication(id)
      load()
    } catch (e) {
      alert(e?.response?.data?.error || 'Failed to withdraw')
    } finally {
      setBusy(null)
    }
  }

  const canWithdraw = (status) => ['submitted','under_review','shortlisted'].includes(status)

  if (loading) return <div className="page"><p>Loading…</p></div>

  return (
    <div className="page">
      <h1>My Applications</h1>
      {error && <p className="error">{error}</p>}

      {apps.length === 0 && (
        <div className="card"><p>No applications yet. <Link to="/jobs">Browse jobs</Link></p></div>
      )}

      <div className="job-grid">
        {apps.map((a) => (
          <div key={a.id} className="job-card" style={{ cursor: 'default' }}>
            <div className="job-card-header">
              <Link to={`/applications/${a.id}`} style={{ flex: 1 }}><h3>{a.job_title}</h3></Link>
              <span className={`status-pill status-${a.status}`}>{a.status.replace('_', ' ')}</span>
            </div>
            <div className="job-card-meta">
              {a.category_name && <span>{a.category_name}</span>}
              {a.location && <span>{a.location}{a.country ? `, ${a.country}` : ''}</span>}
            </div>
            <div className="job-card-footer" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginTop: 12 }}>
              <span className="muted">Applied {new Date(a.applied_at.replace(' ', 'T')).toLocaleDateString()}</span>
              {canWithdraw(a.status) && (
                <button className="btn btn-danger" onClick={() => withdraw(a.id)} disabled={busy === a.id}>
                  {busy === a.id ? '…' : 'Withdraw'}
                </button>
              )}
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}