import { useEffect, useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { getTalentRequest, updateTalentRequestStatus } from '../api/talentRequests.js'
import { createJob, getCategories } from '../api/jobs.js'
import { useNavigate } from 'react-router-dom'

const STATUSES = ['new', 'in_progress', 'posted', 'closed']

export default function AdminTalentRequestDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const [req, setReq] = useState(null)
  const [loading, setLoading] = useState(true)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const [notes, setNotes] = useState('')
  const [creating, setCreating] = useState(false)

  const load = () => {
    setLoading(true)
    getTalentRequest(id)
      .then((r) => { setReq(r.data.request); setNotes(r.data.request.admin_notes || '') })
      .catch((e) => setError(e?.response?.data?.error || 'Not found'))
      .finally(() => setLoading(false))
  }

  useEffect(load, [id])

  const changeStatus = async (status) => {
    if (!confirm(`Set status to "${status}"?`)) return
    setBusy(true)
    try {
      await updateTalentRequestStatus(id, status, notes || null)
      load()
    } catch (e) {
      alert(e?.response?.data?.error || 'Failed')
    } finally { setBusy(false) }
  }

  const saveNotes = async () => {
    setBusy(true)
    try {
      await updateTalentRequestStatus(id, req.status, notes)
      alert('Notes saved')
    } catch (e) {
      alert(e?.response?.data?.error || 'Failed')
    } finally { setBusy(false) }
  }

  const createDraftJob = async () => {
    if (!confirm('Create a draft job from this request? You can review it before publishing.')) return
    setCreating(true)
    try {
      const catRes = await getCategories()
      const cats = catRes.data.categories || []
      // Try to match category by name similarity to job_title
      const match = cats.find((c) => req.job_title.toLowerCase().includes(c.name.toLowerCase().split(' ')[0]))
      const payload = {
        title: req.job_title,
        description: req.job_description + (req.required_skills ? `\n\nRequired skills: ${req.required_skills}` : ''),
        location: req.location || undefined,
        country: req.country || undefined,
        category_id: match?.id || undefined,
        employment_type: 'full_time',
        workplace_type: 'onsite',
      }
      const res = await createJob(payload)
      // Link it
      const { linkJobToRequest } = await import('../api/talentRequests.js')
      await linkJobToRequest(id, res.data.job.id)
      navigate(`/my-jobs/${res.data.job.id}/edit`)
    } catch (e) {
      alert(e?.response?.data?.error || 'Failed to create job')
    } finally {
      setCreating(false)
    }
  }

  if (loading) return <div className="page"><p>LoadingÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¦</p></div>
  if (!req) return <div className="page"><p className="error">{error}</p><Link to="/admin/talent-requests">Back</Link></div>

  return (
    <div className="page">
      <Link to="/admin/talent-requests" className="muted">ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â Ãƒâ€šÃ‚Â Back to requests</Link>

      <div className="card" style={{ marginTop: 16 }}>
        <div className="job-card-header">
          <h1>{req.job_title}</h1>
          <span className={`status-pill status-${req.status}`}>{req.status.replace('_', ' ')}</span>
        </div>

        <div className="job-card-meta" style={{ marginTop: 10 }}>
          <span>{req.company_name}</span>
          <span>{req.contact_name}</span>
          <span>{req.contact_email}</span>
          {req.contact_phone && <span>{req.contact_phone}</span>}
        </div>

        <p className="muted" style={{ marginTop: 12 }}>
          Submitted {new Date(req.created_at.replace(' ', 'T')).toLocaleString()}
        </p>

        <hr style={{ margin: '20px 0', border: 'none', borderTop: '1px solid #e5e7eb' }} />

        <h2>Job description</h2>
        <p style={{ whiteSpace: 'pre-line' }}>{req.job_description}</p>

        {req.required_skills && (<><h2>Required skills</h2><p>{req.required_skills}</p></>)}

        <div className="form-row">
          {req.location && <div><strong>Location:</strong> {req.location}{req.country ? `, ${req.country}` : ''}</div>}
          {req.budget && <div><strong>Budget:</strong> {req.budget}</div>}
          <div><strong>Timeline:</strong> {req.timeline.replace('_', ' ')}</div>
          <div><strong>Preferred contact:</strong> {req.preferred_contact}</div>
        </div>
      </div>

      <div className="card">
        <h2>Admin actions</h2>

        <div style={{ marginBottom: 16 }}>
          <strong>Status:</strong>{' '}
          {STATUSES.map((s) => (
            <button
              key={s}
              className={req.status === s ? 'btn btn-primary' : 'btn btn-secondary'}
              onClick={() => changeStatus(s)}
              disabled={busy || req.status === s}
              style={{ marginRight: 8 }}
            >
              {s.replace('_', ' ')}
            </button>
          ))}
        </div>

        <label>
          <strong>Internal notes</strong>
          <textarea rows={3} value={notes} onChange={(e) => setNotes(e.target.value)}
            placeholder="Notes only you can seeÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¦" />
        </label>
        <button className="btn btn-secondary" onClick={saveNotes} disabled={busy} style={{ marginTop: 8 }}>
          Save notes
        </button>

        <hr style={{ margin: '20px 0', border: 'none', borderTop: '1px solid #e5e7eb' }} />

        <h3>Create job from this request</h3>
        <p className="muted">
          Creates a draft job pre-filled with the request data. You can review and publish it.
        </p>
        {req.created_job_id ? (
          <p>
            <strong>Linked job:</strong>{' '}
            <Link to={`/my-jobs/${req.created_job_id}/edit`}>{req.created_job_title}</Link>
          </p>
        ) : (
          <button className="btn btn-primary" onClick={createDraftJob} disabled={creating}>
            {creating ? 'CreatingÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¦' : 'Create draft job'}
          </button>
        )}
      </div>
    </div>
  )
}