import { useEffect, useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { receivedApplication, updateApplicationStatus } from '../api/receivedApplications.js'
import { listResumes, uploadResume, deleteResume, makePrimary, downloadResume } from '../api/resumes.js'

const LABELS = {
  submitted: 'Submitted', under_review: 'Under Review', shortlisted: 'Shortlisted',
  interview: 'Interview', offered: 'Offer', hired: 'Hired', rejected: 'Rejected', withdrawn: 'Withdrawn',
}
const ACTION_LABELS = {
  under_review: 'Move to Under Review', shortlisted: 'Shortlist',
  interview: 'Schedule Interview', offered: 'Make Offer',
  hired: 'Mark as Hired', rejected: 'Reject',
}

export default function ReceivedApplicationDetail() {
  const { id } = useParams()
  const [app, setApp] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(null)

  const load = () => {
    setLoading(true)
    receivedApplication(id)
      .then((r) => setApp(r.data.application))
      .catch((e) => setError(e?.response?.data?.error || 'Not found'))
      .finally(() => setLoading(false))
  }

  useEffect(load, [id])

  const transition = async (newStatus) => {
    setBusy(newStatus)
    setError('')
    try {
      const res = await updateApplicationStatus(id, newStatus)
      setApp(res.data.application)
    } catch (e) {
      setError(e?.response?.data?.error || 'Failed to update')
    } finally { setBusy(null) }
  }

  if (loading) return <div className="page"><p>Loading…</p></div>
  if (!app) return <div className="page"><p className="error">{error}</p><Link to="/received-applications">Back</Link></div>

  const allowed = app.allowed_transitions || []

  return (
    <div className="page">
      <Link to="/received-applications" className="muted">← Back to applications</Link>
      <div className="card" style={{ marginTop: 16 }}>
        <div className="job-card-header">
          <h1>{app.first_name} {app.last_name}</h1>
          <span className={`status-pill status-${app.status}`}>{LABELS[app.status] || app.status}</span>
        </div>
        <div className="job-card-meta" style={{ marginTop: 10 }}>
          <span>{app.candidate_email}</span>
          {app.headline && <span>{app.headline}</span>}
          {app.candidate_location && <span>{app.candidate_location}{app.candidate_country ? `, ${app.candidate_country}` : ''}</span>}
        </div>
        {app.skills && app.skills.length > 0 && (
  <div style={{ marginTop: 16 }}>
    <strong>Skills:</strong>
    <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6, marginTop: 8 }}>
      {app.skills.map((s) => (
        <span
          key={s.id}
          className="badge"
          style={{
            background: '#dbeafe',
            color: '#1e40af',
            padding: '4px 10px',
            borderRadius: 12,
            fontSize: 13,
          }}
        >
          {s.name}
          {s.proficiency ? ` · ${s.proficiency}` : ''}
          {s.years_experience ? ` · ${s.years_experience}y` : ''}
        </span>
      ))}
    </div>
  </div>
)}

        {app.resume_id && app.resume_filename && (
          <p style={{ marginTop: 14 }}>
            <strong>Resume:</strong>{' '}
            <button className="btn btn-secondary" onClick={() => downloadResume(app.resume_id, app.resume_filename)}>
              {app.resume_filename}
            </button>
          </p>
        )}

        <p className="muted" style={{ marginTop: 14 }}>
          Applied {new Date(app.applied_at.replace(' ', 'T')).toLocaleString()} to{' '}
          <Link to={`/jobs/${app.job_id}`}><strong>{app.job_title}</strong></Link>
        </p>

        {allowed.length > 0 && (
          <div style={{ marginTop: 24, padding: 16, background: '#f9fafb', borderRadius: 8 }}>
            <strong>Update status</strong>
            <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap', marginTop: 12 }}>
              {allowed.map((s) => (
                <button key={s} className={s === 'rejected' ? 'btn btn-danger' : 'btn btn-primary'}
                  disabled={busy === s} onClick={() => transition(s)}>
                  {busy === s ? '…' : ACTION_LABELS[s] || s}
                </button>
              ))}
            </div>
          </div>
        )}
        {allowed.length === 0 && (
          <p className="muted" style={{ marginTop: 16 }}>
            This application is in a final state ({LABELS[app.status] || app.status}).
          </p>
        )}
        {error && <div className="error" style={{ marginTop: 12 }}>{error}</div>}

        <hr style={{ margin: '24px 0', border: 'none', borderTop: '1px solid #e5e7eb' }} />
        <h2>Cover letter</h2>
        {app.cover_letter ? <p style={{ whiteSpace: 'pre-line' }}>{app.cover_letter}</p> : <p className="muted">No cover letter provided.</p>}
      </div>
    </div>
  )
}