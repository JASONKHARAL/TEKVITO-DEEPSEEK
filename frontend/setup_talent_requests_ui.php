<?php
// frontend/setup_talent_requests_ui.php
// /hire page + admin /admin/talent-requests pages.

declare(strict_types=1);

$base = __DIR__;

$files = [

'src/api/talentRequests.js' => <<<'JS'
import api from './client.js'

export const submitHireRequest = (payload) => api.post('/hire-request', payload)
export const listTalentRequests = (params = {}) => api.get('/admin/talent-requests', { params })
export const getTalentRequest = (id) => api.get(`/admin/talent-requests/${id}`)
export const updateTalentRequestStatus = (id, status, adminNotes = null) =>
  api.patch(`/admin/talent-requests/${id}/status`, { status, admin_notes: adminNotes })
export const linkJobToRequest = (id, jobId) =>
  api.post(`/admin/talent-requests/${id}/link-job`, { job_id: jobId })
JS,

'src/pages/Hire.jsx' => <<<'JSX'
import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { submitHireRequest } from '../api/talentRequests.js'
import { listAllSkills } from '../api/skills.js'

export default function Hire() {
  const [skills, setSkills] = useState([])
  const [form, setForm] = useState({
    contact_name: '', contact_email: '', contact_phone: '',
    company_name: '', job_title: '', job_description: '',
    required_skills: '', location: '', country: '', budget: '',
    timeline: 'flexible', preferred_contact: 'email',
  })
  const [submitting, setSubmitting] = useState(false)
  const [success, setSuccess] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    listAllSkills().then((r) => setSkills(r.data.skills || [])).catch(() => {})
  }, [])

  const set = (k) => (e) => setForm({ ...form, [k]: e.target.value })

  const submit = async (e) => {
    e.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      await submitHireRequest(form)
      setSuccess(true)
      window.scrollTo({ top: 0, behavior: 'smooth' })
    } catch (err) {
      setError(err?.response?.data?.error || 'Failed to submit')
    } finally {
      setSubmitting(false)
    }
  }

  if (success) {
    return (
      <div className="page">
        <div className="card" style={{ maxWidth: 600, margin: '40px auto', textAlign: 'center', padding: 40 }}>
          <div style={{ fontSize: 56, marginBottom: 16 }}>ÃƒÆ’Ã‚Â¢Ãƒâ€¦Ã¢â‚¬Å“ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦</div>
          <h1>Request received!</h1>
          <p>Thanks ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â we've got your request. Our team will review it and get back to you within 1 business day.</p>
          <p className="muted">Want to explore TEKVITO in the meantime?</p>
          <Link to="/jobs" className="btn btn-primary">Browse Jobs</Link>
        </div>
      </div>
    )
  }

  return (
    <div className="page" style={{ maxWidth: 760 }}>
      <Link to="/" className="muted">ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â Ãƒâ€šÃ‚Â Back to home</Link>

      <div className="hire-hero">
        <div className="hero-badge">For employers</div>
        <h1>Let TEKVITO find your next great hire</h1>
        <p>
          Not ready to post a job yourself? Tell us what you need ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â we'll review your request
          and match you with qualified candidates from our network.
        </p>
      </div>

      <form className="card" onSubmit={submit} style={{ marginTop: 32 }}>
        <h2 style={{ marginTop: 0 }}>About you</h2>
        <div className="form-row">
          <label>Your name*<input value={form.contact_name} onChange={set('contact_name')} required /></label>
          <label>Email*<input type="email" value={form.contact_email} onChange={set('contact_email')} required /></label>
        </div>
        <div className="form-row">
          <label>Phone (optional)<input value={form.contact_phone} onChange={set('contact_phone')} /></label>
          <label>Company*<input value={form.company_name} onChange={set('company_name')} required /></label>
        </div>

        <h2 style={{ marginTop: 24 }}>The role</h2>
        <label>Job title*<input value={form.job_title} onChange={set('job_title')} required placeholder="e.g. Senior React Developer" /></label>
        <label>Job description*<textarea rows={5} value={form.job_description} onChange={set('job_description')} required placeholder="What will this person work on? What are the main responsibilities?" /></label>
        <label>
          Required skills (comma-separated)
          <input value={form.required_skills} onChange={set('required_skills')} placeholder="e.g. React, TypeScript, Node.js" />
        </label>

        <div className="form-row">
          <label>Location<input value={form.location} onChange={set('location')} /></label>
          <label>Country<input value={form.country} onChange={set('country')} maxLength={2} placeholder="e.g. NP" /></label>
        </div>

        <div className="form-row">
          <label>Budget / salary range<input value={form.budget} onChange={set('budget')} placeholder="e.g. USD 60kÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ90k / year" /></label>
          <label>Timeline
            <select value={form.timeline} onChange={set('timeline')}>
              <option value="asap">ASAP</option>
              <option value="1_2_weeks">1ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ2 weeks</option>
              <option value="1_month">Within a month</option>
              <option value="flexible">Flexible</option>
            </select>
          </label>
        </div>

        <label>Preferred contact method
          <select value={form.preferred_contact} onChange={set('preferred_contact')}>
            <option value="email">Email</option>
            <option value="phone">Phone</option>
            <option value="either">Either</option>
          </select>
        </label>

        {error && <div className="error">{error}</div>}

        <button type="submit" className="btn btn-primary" disabled={submitting} style={{ marginTop: 8 }}>
          {submitting ? 'SendingÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¦' : 'Send Request'}
        </button>
        <p className="muted" style={{ marginTop: 12, fontSize: 13 }}>
          We'll never share your details. No account required.
        </p>
      </form>
    </div>
  )
}
JSX,

'src/pages/AdminTalentRequests.jsx' => <<<'JSX'
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
JSX,

'src/pages/AdminTalentRequestDetail.jsx' => <<<'JSX'
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
JSX,

'src/routes/AppRoutes.jsx' => <<<'JSX'
import { Routes, Route } from 'react-router-dom'
import Home from '../pages/Home.jsx'
import About from '../pages/About.jsx'
import Hire from '../pages/Hire.jsx'
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
import AdminTalentRequests from '../pages/AdminTalentRequests.jsx'
import AdminTalentRequestDetail from '../pages/AdminTalentRequestDetail.jsx'
import Notifications from '../pages/Notifications.jsx'
import Login from '../pages/Login.jsx'
import Register from '../pages/Register.jsx'
import Dashboard from '../pages/Dashboard.jsx'
import ProtectedRoute from '../components/ProtectedRoute.jsx'

export default function AppRoutes() {
  return (
    <Routes>
      <Route path="/" element={<Home />} />
      <Route path="/about" element={<About />} />
      <Route path="/hire" element={<Hire />} />
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
      <Route path="/my-jobs" element={<ProtectedRoute roles={['employer','recruiter','admin']}><MyJobs /></ProtectedRoute>} />
      <Route path="/my-jobs/new" element={<ProtectedRoute roles={['employer','recruiter','admin']}><NewJob /></ProtectedRoute>} />
      <Route path="/my-jobs/:id/edit" element={<ProtectedRoute roles={['employer','recruiter','admin']}><EditJob /></ProtectedRoute>} />
      <Route path="/my-jobs/:id/versions" element={<ProtectedRoute roles={['employer','recruiter','admin']}><JobVersions /></ProtectedRoute>} />
      <Route path="/received-applications" element={<ProtectedRoute roles={['employer','recruiter','admin']}><ReceivedApplications /></ProtectedRoute>} />
      <Route path="/received-applications/:id" element={<ProtectedRoute roles={['employer','recruiter','admin']}><ReceivedApplicationDetail /></ProtectedRoute>} />
      <Route path="/admin/users" element={<ProtectedRoute roles={['admin']}><AdminUsers /></ProtectedRoute>} />
      <Route path="/admin/talent-requests" element={<ProtectedRoute roles={['admin']}><AdminTalentRequests /></ProtectedRoute>} />
      <Route path="/admin/talent-requests/:id" element={<ProtectedRoute roles={['admin']}><AdminTalentRequestDetail /></ProtectedRoute>} />
    </Routes>
  )
}
JSX,
];

// Write each file
foreach ($files as $rel => $contents) {
    $path = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $dir  = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    file_put_contents($path, $contents);
    echo "Wrote: $rel\n";
}
echo "Done.\n";