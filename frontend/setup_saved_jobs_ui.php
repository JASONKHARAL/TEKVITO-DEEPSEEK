<?php
// frontend/setup_saved_jobs_ui.php
// Saved jobs UI.

declare(strict_types=1);

$base = __DIR__;

$files = [

'src/api/savedJobs.js' => <<<'JS'
import api from './client.js'

export const listSavedJobs = () => api.get('/saved-jobs')
export const saveJob = (id) => api.post(`/jobs/${id}/save`)
export const unsaveJob = (id) => api.delete(`/jobs/${id}/save`)
export const isJobSaved = (id) => api.get(`/jobs/${id}/is-saved`)
JS,

'src/components/SaveJobButton.jsx' => <<<'JSX'
import { useEffect, useState } from 'react'
import { useAuth } from '../context/AuthContext.jsx'
import { saveJob, unsaveJob, isJobSaved } from '../api/savedJobs.js'

export default function SaveJobButton({ jobId }) {
  const { user } = useAuth()
  const [saved, setSaved] = useState(false)
  const [busy, setBusy] = useState(false)
  const isCandidate = user?.roles?.includes('candidate')

  useEffect(() => {
    if (!isCandidate) return
    isJobSaved(jobId)
      .then((r) => setSaved(!!r.data.saved))
      .catch(() => {})
  }, [jobId, isCandidate])

  if (!isCandidate) return null

  const toggle = async () => {
    setBusy(true)
    try {
      if (saved) {
        await unsaveJob(jobId)
        setSaved(false)
      } else {
        await saveJob(jobId)
        setSaved(true)
      }
    } catch (e) {
      alert(e?.response?.data?.error || 'Failed')
    } finally {
      setBusy(false)
    }
  }

  return (
    <button
      onClick={toggle}
      className={saved ? 'btn btn-secondary' : 'btn btn-primary'}
      disabled={busy}
    >
      {busy ? '…' : saved ? '★ Saved' : '☆ Save Job'}
    </button>
  )
}
JSX,

'src/pages/SavedJobs.jsx' => <<<'JSX'
import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { listSavedJobs } from '../api/savedJobs.js'
import JobCard from '../components/JobCard.jsx'

export default function SavedJobs() {
  const [jobs, setJobs] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    listSavedJobs()
      .then((r) => setJobs(r.data.data || []))
      .catch((e) => setError(e?.response?.data?.error || 'Failed to load'))
      .finally(() => setLoading(false))
  }, [])

  if (loading) return <div className="page"><p>Loading…</p></div>

  return (
    <div className="page">
      <h1>Saved Jobs</h1>
      <p className="muted">Jobs you've bookmarked for later.</p>

      {error && <p className="error">{error}</p>}

      {jobs.length === 0 && (
        <div className="card">
          <p>No saved jobs yet. <Link to="/jobs">Browse jobs</Link> and click "Save Job".</p>
        </div>
      )}

      {jobs.length > 0 && (
        <div className="job-grid">
          {jobs.map((job) => (
            <JobCard key={job.id} job={job} />
          ))}
        </div>
      )}
    </div>
  )
}
JSX,

'src/pages/JobDetail.jsx' => <<<'JSX'
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
import SavedJobs from '../pages/SavedJobs.jsx'
import ReceivedApplications from '../pages/ReceivedApplications.jsx'
import ReceivedApplicationDetail from '../pages/ReceivedApplicationDetail.jsx'
import AdminUsers from '../pages/AdminUsers.jsx'
import Notifications from '../pages/Notifications.jsx'
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
      <Route path="/notifications" element={<ProtectedRoute><Notifications /></ProtectedRoute>} />
      <Route path="/profile" element={<ProtectedRoute roles={['candidate']}><Profile /></ProtectedRoute>} />
      <Route path="/applications" element={<ProtectedRoute roles={['candidate']}><Applications /></ProtectedRoute>} />
      <Route path="/applications/:id" element={<ProtectedRoute roles={['candidate']}><ApplicationDetail /></ProtectedRoute>} />
      <Route path="/saved-jobs" element={<ProtectedRoute roles={['candidate']}><SavedJobs /></ProtectedRoute>} />
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
import NotificationBell from './NotificationBell.jsx'

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
        {user && isCandidate && <Link to="/saved-jobs">Saved</Link>}
        {user && isAdmin && <Link to="/my-jobs">My Jobs</Link>}
        {user && isAdmin && <Link to="/received-applications">Applicants</Link>}
        {user && isSuperAdmin && <Link to="/admin/users">Users</Link>}
        {user ? (
          <>
            <Link to="/profile">Profile</Link>
            <NotificationBell />
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

echo "\nDone. Vite will hot-reload.\n";