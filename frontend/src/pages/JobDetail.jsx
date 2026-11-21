import { useEffect, useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { getJob, updateJobStatus } from '../api/jobs.js'
import { applyToJob, hasApplied as checkApplied } from '../api/applications.js'
import { listResumes } from '../api/resumes.js'
import { useAuth } from '../context/AuthContext.jsx'
import SaveJobButton from '../components/SaveJobButton.jsx'

export default function JobDetail() {
  const { id } = useParams()
  const { user } = useAuth()
  const [job, setJob] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  const [showApply, setShowApply] = useState(false)
  const [coverLetter, setCoverLetter] = useState('')
  const [applied, setApplied] = useState(false)
  const [applying, setApplying] = useState(false)
  const [applyError, setApplyError] = useState('')
  const [primaryResume, setPrimaryResume] = useState(null)
  const [resumesLoading, setResumesLoading] = useState(false)

  const load = () => {
    setLoading(true)
    getJob(id)
      .then((res) => setJob(res.data.job))
      .catch((err) => setError(err?.response?.data?.error || 'Job not found'))
      .finally(() => setLoading(false))
  }

  useEffect(load, [id])

  useEffect(() => {
    if (!user || !user.roles?.includes('candidate')) return
    checkApplied(id)
      .then((r) => setApplied(!!r.data.has_applied))
      .catch(() => {})
  }, [id, user])

  const openApply = async () => {
    setShowApply(true)
    setApplyError('')
    setResumesLoading(true)
    try {
      const r = await listResumes()
      const list = r.data.data || []
      const primary = list.find((x) => x.is_primary) || list[0] || null
      setPrimaryResume(primary)
    } catch {
      setApplyError('Could not load your resumes')
    } finally {
      setResumesLoading(false)
    }
  }

  const submitApply = async (e) => {
    e.preventDefault()
    setApplying(true)
    setApplyError('')
    try {
      await applyToJob(id, coverLetter || null)
      setApplied(true)
      setShowApply(false)
      setCoverLetter('')
    } catch (err) {
      const data = err?.response?.data
      setApplyError(data?.error || 'Failed to apply')
    } finally {
      setApplying(false)
    }
  }

  const canApprove = user?.roles?.some((r) => ['super_admin', 'recruiter'].includes(r))
  const isOwner = user && job && user.id === job.created_by
  const isCandidate = user?.roles?.includes('candidate')

  const togglePublish = async () => {
    if (!job) return
    setBusy(true)
    try {
      const newStatus = job.status === 'published' ? 'closed' : 'published'
      await updateJobStatus(job.id, newStatus)
      await new Promise((r) => setTimeout(r, 200))
      load()
    } catch (e) { alert(e?.response?.data?.error || 'Failed') }
    finally { setBusy(false) }
  }

  if (loading) return <div className="page"><p>Loading…</p></div>
  if (error) return <div className="page"><p className="error">{error}</p><Link to="/jobs">Back</Link></div>
  if (!job) return null

  return (
    <div className="page">
      <Link to="/jobs" className="muted">← Back to jobs</Link>

      <div className="card" style={{ marginTop: 16 }}>
        <div className="job-card-header">
          <h1>{job.title}</h1>
          <span className={`badge badge-${job.workplace_type}`}>{job.workplace_type}</span>
        </div>
        <div className="job-card-meta" style={{ marginTop: 12 }}>
          {job.category_name && <span>{job.category_name}</span>}
          {job.location && <span>{job.location}{job.country ? `, ${job.country}` : ''}</span>}
          <span>{job.employment_type.replace('_', ' ')}</span>
          <span className={`status-pill status-${job.status}`}>{job.status.replace('_', ' ')}</span>
        </div>
        {(job.salary_min || job.salary_max) && (
          <p className="job-card-salary" style={{ fontSize: 18, marginTop: 16 }}>
            {job.salary_currency || 'USD'} {job.salary_min || '?'} – {job.salary_max || '?'}
          </p>
        )}

        {isCandidate && job.status === 'published' && (
          <div style={{ marginTop: 20, display: 'flex', gap: 10, alignItems: 'center' }}>
            {applied ? (
              <div className="success">✓ You have applied to this job</div>
            ) : (
              <button className="btn btn-primary" onClick={openApply}>Apply to this job</button>
            )}
            <SaveJobButton jobId={job.id} />
          </div>
        )}

        {(canApprove || isOwner) && (
          <div style={{ marginTop: 20, padding: 12, background: '#f9fafb', borderRadius: 6 }}>
            <strong>Admin actions:</strong>{' '}
            <Link to={`/my-jobs/${job.id}/edit`} className="btn btn-secondary" style={{ marginRight: 8 }}>Edit job</Link>
            <button className="btn btn-primary" onClick={togglePublish} disabled={busy}>
              {busy ? '…' : job.status === 'published' ? 'Unpublish / Close' : 'Publish'}
            </button>
          </div>
        )}

        <hr style={{ margin: '24px 0', border: 'none', borderTop: '1px solid #e5e7eb' }} />
        <h2>Description</h2>
        <p style={{ whiteSpace: 'pre-line' }}>{job.description}</p>
        {job.responsibilities && (<><h2>Responsibilities</h2><p style={{ whiteSpace: 'pre-line' }}>{job.responsibilities}</p></>)}
        {job.requirements && (<><h2>Requirements</h2><p style={{ whiteSpace: 'pre-line' }}>{job.requirements}</p></>)}
      </div>

      {showApply && (
        <div className="modal-backdrop" onClick={() => setShowApply(false)}>
          <div className="modal" onClick={(e) => e.stopPropagation()}>
            <h2>Apply to {job.title}</h2>
            {resumesLoading ? (
              <p className="muted">Checking your resumes…</p>
            ) : !primaryResume ? (
              <div>
                <div className="error" style={{ marginBottom: 14 }}>
                  You must upload a resume before applying.
                </div>
                <Link to="/profile" className="btn btn-primary" onClick={() => setShowApply(false)}>
                  Go to Profile
                </Link>{' '}
                <button type="button" className="btn btn-secondary" onClick={() => setShowApply(false)}>Cancel</button>
              </div>
            ) : (
              <form onSubmit={submitApply}>
                <p><strong>Resume to attach:</strong> {primaryResume.original_filename}</p>
                <p className="muted" style={{ marginTop: -6, marginBottom: 14 }}>
                  <Link to="/profile">Change primary resume</Link>
                </p>
                <label>Cover letter (optional)
                  <textarea rows={6} value={coverLetter} onChange={(e) => setCoverLetter(e.target.value)}
                    placeholder="Tell the employer why you're a great fit…" />
                </label>
                {applyError && <div className="error">{applyError}</div>}
                <div style={{ display: 'flex', gap: 10, marginTop: 12 }}>
                  <button type="submit" className="btn btn-primary" disabled={applying}>
                    {applying ? 'Submitting…' : 'Submit Application'}
                  </button>
                  <button type="button" className="btn btn-secondary" onClick={() => setShowApply(false)}>Cancel</button>
                </div>
              </form>
            )}
          </div>
        </div>
      )}
    </div>
  )
}