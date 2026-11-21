import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { myJobs } from '../api/myJobs.js'
import { updateJobStatus } from '../api/jobs.js'

export default function MyJobs() {
  const [jobs, setJobs] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [busyId, setBusyId] = useState(null)

  const load = () => {
    setLoading(true)
    myJobs()
      .then((r) => setJobs(r.data.data || []))
      .catch((e) => setError(e?.response?.data?.error || 'Failed to load'))
      .finally(() => setLoading(false))
  }

  useEffect(load, [])

  const changeStatus = async (id, status) => {
    setBusyId(id)
    try {
      await updateJobStatus(id, status)
      await new Promise((r) => setTimeout(r, 200))
      load()
    } catch (e) {
      alert(e?.response?.data?.error || 'Failed to update status')
    } finally { setBusyId(null) }
  }

  if (loading) return <div className="page"><p>Loading…</p></div>

  return (
    <div className="page">
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <h1>My Jobs</h1>
        <Link to="/jobs/new" className="btn btn-primary">+ Post New Job</Link>
      </div>

      {error && <p className="error">{error}</p>}

      {jobs.length === 0 && (
        <div className="card"><p>No jobs yet. <Link to="/jobs/new">Create one.</Link></p></div>
      )}

      {jobs.length > 0 && (
        <div className="card">
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <thead>
              <tr style={{ textAlign: 'left', borderBottom: '1px solid #e5e7eb' }}>
                <th style={{ padding: 10 }}>Title</th>
                <th style={{ padding: 10 }}>Category</th>
                <th style={{ padding: 10 }}>Status</th>
                <th style={{ padding: 10 }}>Actions</th>
              </tr>
            </thead>
            <tbody>
              {jobs.map((j) => (
                <tr key={j.id} style={{ borderBottom: '1px solid #f3f4f6' }}>
                  <td style={{ padding: 10 }}><Link to={`/jobs/${j.id}`}>{j.title}</Link></td>
                  <td style={{ padding: 10, color: '#6b7280' }}>{j.category_name || '—'}</td>
                  <td style={{ padding: 10 }}>
                    <span className={`status-pill status-${j.status}`}>{j.status.replace('_', ' ')}</span>
                  </td>
                  <td style={{ padding: 10, display: 'flex', gap: 6, flexWrap: 'wrap' }}>
                    <Link to={`/my-jobs/${j.id}/edit`} className="btn btn-secondary">Edit</Link>
                    <Link to={`/my-jobs/${j.id}/versions`} className="btn btn-secondary">History</Link>
                    {j.status === 'published' ? (
                      <button className="btn btn-secondary" disabled={busyId === j.id}
                        onClick={() => changeStatus(j.id, 'closed')}>
                        {busyId === j.id ? '…' : 'Close'}
                      </button>
                    ) : (
                      <button className="btn btn-primary" disabled={busyId === j.id}
                        onClick={() => changeStatus(j.id, 'published')}>
                        {busyId === j.id ? '…' : 'Publish'}
                      </button>
                    )}
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