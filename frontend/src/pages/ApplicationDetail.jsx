import { useEffect, useState } from 'react'
import { useParams, Link, useNavigate } from 'react-router-dom'
import { getApplication, withdrawApplication } from '../api/applications.js'
import { downloadResume } from '../api/resumes.js'

export default function ApplicationDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const [app, setApp] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  const load = () => {
    setLoading(true)
    getApplication(id)
      .then((r) => setApp(r.data.application))
      .catch((e) => setError(e?.response?.data?.error || 'Not found'))
      .finally(() => setLoading(false))
  }

  useEffect(load, [id])

  const withdraw = async () => {
    if (!confirm('Withdraw this application? This cannot be undone.')) return
    setBusy(true)
    try {
      await withdrawApplication(id)
      load()
    } catch (e) {
      alert(e?.response?.data?.error || 'Failed')
    } finally {
      setBusy(false)
    }
  }

  if (loading) return <div className="page"><p>Loading…</p></div>
  if (error) return <div className="page"><p className="error">{error}</p><Link to="/applications">Back</Link></div>
  if (!app) return null

  const canWithdraw = ['submitted','under_review','shortlisted'].includes(app.status)

  return (
    <div className="page">
      <Link to="/applications" className="muted">← Back to applications</Link>
      <div className="card" style={{ marginTop: 16 }}>
        <div className="job-card-header">
          <h1>{app.job_title}</h1>
          <span className={`status-pill status-${app.status}`}>{app.status.replace('_', ' ')}</span>
        </div>
        <div className="job-card-meta" style={{ marginTop: 12 }}>
          {app.category_name && <span>{app.category_name}</span>}
          {app.location && <span>{app.location}{app.country ? `, ${app.country}` : ''}</span>}
        </div>
        <p className="muted" style={{ marginTop: 16 }}>
          Applied {new Date(app.applied_at.replace(' ', 'T')).toLocaleString()}
        </p>
        {app.resume_filename && (
          <p style={{ marginTop: 10 }}>
  <strong>Resume sent:</strong>{' '}
  <button className="btn btn-secondary" onClick={() => downloadResume(app.resume_id, app.resume_filename)}>
    {app.resume_filename}
  </button>
</p>
        )}
        {app.cover_letter && (<><h2 style={{ marginTop: 24 }}>Your cover letter</h2><p style={{ whiteSpace: 'pre-line' }}>{app.cover_letter}</p></>)}
        <h2 style={{ marginTop: 24 }}>Job description</h2>
        <p style={{ whiteSpace: 'pre-line' }}>{app.description}</p>

        <div style={{ marginTop: 20, display: 'flex', gap: 10 }}>
          <Link to={`/jobs/${app.job_id}`} className="btn btn-secondary">View job posting</Link>
          {canWithdraw && (
            <button className="btn btn-danger" onClick={withdraw} disabled={busy}>
              {busy ? '…' : 'Withdraw application'}
            </button>
          )}
        </div>
      </div>
    </div>
  )
}