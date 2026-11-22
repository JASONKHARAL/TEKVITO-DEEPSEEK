import { useEffect, useState } from 'react'
import { useNavigate, useParams, Link } from 'react-router-dom'
import { getJob, getCategories, updateJob, deleteJob } from '../api/jobs.js'
import JobSkillsPicker from '../components/JobSkillsPicker.jsx'

export default function EditJob() {
  const { id } = useParams()
  const navigate = useNavigate()
  const [categories, setCategories] = useState([])
  const [form, setForm] = useState(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    Promise.all([getCategories(), getJob(id)])
      .then(([cRes, jRes]) => {
        setCategories(cRes.data.categories || [])
        const j = jRes.data.job
        setForm({
          title: j.title || '', description: j.description || '',
          responsibilities: j.responsibilities || '', requirements: j.requirements || '',
          category_id: j.category_id || '', employment_type: j.employment_type || 'full_time',
          workplace_type: j.workplace_type || 'onsite', location: j.location || '',
          country: j.country || '', salary_min: j.salary_min || '',
          salary_max: j.salary_max || '', salary_currency: j.salary_currency || 'USD',
        })
      })
      .catch((e) => setError(e?.response?.data?.error || 'Failed to load'))
      .finally(() => setLoading(false))
  }, [id])

  const set = (k) => (e) => setForm({ ...form, [k]: e.target.value })

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setSaving(true)
    try {
      const payload = { ...form }
      if (!payload.category_id) delete payload.category_id
      if (!payload.salary_min) delete payload.salary_min
      if (!payload.salary_max) delete payload.salary_max
      await updateJob(id, payload)
      navigate('/my-jobs')
    } catch (err) {
      setError(err?.response?.data?.error || 'Failed to save')
    } finally {
      setSaving(false)
    }
  }

  const handleDelete = async () => {
    if (!window.confirm("Are you sure you want to delete this job? This action cannot be undone.")) return;
    
    try {
      await deleteJob(id);
      alert('Job deleted successfully.');
      navigate('/my-jobs');
    } catch (err) {
      alert(err?.response?.data?.error || 'Failed to delete job');
    }
  }

  if (loading) return <div className="page"><p>Loading…</p></div>
  if (!form) return <div className="page"><p className="error">{error}</p><Link to="/my-jobs">Back</Link></div>

  return (
    <div className="page">
      <Link to="/my-jobs" className="muted">← Back to my jobs</Link>
      <h1>Edit Job</h1>
      <p className="muted"><Link to={`/my-jobs/${id}/versions`}>View version history →</Link></p>

      <form className="card" onSubmit={handleSubmit}>
        <label>Title*<input value={form.title} onChange={set('title')} required /></label>
        <label>Description*<textarea rows={4} value={form.description} onChange={set('description')} required /></label>
        <label>Responsibilities<textarea rows={3} value={form.responsibilities} onChange={set('responsibilities')} /></label>
        <label>Requirements<textarea rows={3} value={form.requirements} onChange={set('requirements')} /></label>

        <div className="form-row">
          <label>Category<select value={form.category_id} onChange={set('category_id')}>
            <option value="">Select…</option>
            {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
          </select></label>
          <label>Employment type<select value={form.employment_type} onChange={set('employment_type')}>
            <option value="full_time">Full-time</option><option value="part_time">Part-time</option>
            <option value="contract">Contract</option><option value="contract_to_hire">Contract to hire</option>
            <option value="permanent">Permanent</option>
          </select></label>
        </div>

        <div className="form-row">
          <label>Workplace<select value={form.workplace_type} onChange={set('workplace_type')}>
            <option value="onsite">Onsite</option><option value="remote">Remote</option><option value="hybrid">Hybrid</option>
          </select></label>
          <label>Location<input value={form.location} onChange={set('location')} /></label>
          <label>Country<input value={form.country} onChange={set('country')} maxLength={2} /></label>
        </div>

        <div className="form-row">
          <label>Salary min<input type="number" value={form.salary_min} onChange={set('salary_min')} /></label>
          <label>Salary max<input type="number" value={form.salary_max} onChange={set('salary_max')} /></label>
          <label>Currency<input value={form.salary_currency} onChange={set('salary_currency')} maxLength={3} /></label>
        </div>

        {error && <div className="error">{error}</div>}
        
        <div style={{ display: 'flex', gap: 10, marginTop: 16 }}>
          <button type="submit" className="btn btn-primary" disabled={saving}>
            {saving ? 'Saving…' : 'Save Changes'}
          </button>
        </div>
      </form>

      <JobSkillsPicker jobId={id} />

      {/* Danger Zone */}
      <div className="card" style={{ marginTop: 24, border: '1px solid #fecaca', background: '#fef2f2' }}>
        <h3 style={{ color: '#b91c1c', marginTop: 0 }}>Danger Zone</h3>
        <p className="muted" style={{ color: '#991b1b', marginBottom: 16 }}>
          Once you delete a job, there is no going back. All applications associated with this job will also be removed. Please be certain.
        </p>
        <button type="button" onClick={handleDelete} className="btn btn-danger">
          Delete this job
        </button>
      </div>
    </div>
  )
}