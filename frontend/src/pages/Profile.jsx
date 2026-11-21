import { useEffect, useState } from 'react'
import { getProfile, updateProfile } from '../api/candidate.js'
import ResumeSection from '../components/ResumeSection.jsx'
import SkillsSection from '../components/SkillsSection.jsx'
import { useAuth } from '../context/AuthContext.jsx'

export default function Profile() {
  const { user } = useAuth()
  const isCandidate = user?.roles?.includes('candidate')
  const [form, setForm] = useState(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [saved, setSaved] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    if (!isCandidate) {
      setLoading(false)
      return
    }

    getProfile()
      .then((r) => setForm(r.data.profile))
      .catch((e) => setError(e?.response?.data?.error || 'Failed to load profile'))
      .finally(() => setLoading(false))
  }, [isCandidate])

  const set = (k) => (e) => setForm({ ...form, [k]: e.target.value })

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSaving(true)
    setSaved(false)
    setError('')
    try {
      const res = await updateProfile(form)
      setForm(res.data.profile)
      setSaved(true)
    } catch (err) {
      setError(err?.response?.data?.error || 'Failed to save')
    } finally {
      setSaving(false)
    }
  }

  if (loading) return <div className="page"><p>Loading…</p></div>

  // Non-candidates (admin / recruiter) don't have candidate profiles
  if (!isCandidate) {
    return (
      <div className="page">
        <h1>My Profile</h1>
        <div className="card">
          <h2>{user.first_name} {user.last_name}</h2>
          <p><strong>Email:</strong> {user.email}</p>
          <p><strong>Roles:</strong> {user.roles?.join(', ')}</p>
          <p className="muted">
            Candidate profiles with resumes and skills are only available to candidate accounts.
          </p>
        </div>
      </div>
    )
  }

  if (!form) return <div className="page"><p className="error">{error}</p></div>

  return (
    <div className="page">
      <h1>My Profile</h1>

      <form className="card" onSubmit={handleSubmit}>
        <label>Headline
          <input value={form.headline || ''} onChange={set('headline')} placeholder="e.g. Senior PHP Developer" />
        </label>

        <label>Professional summary
          <textarea rows={5} value={form.professional_summary || ''} onChange={set('professional_summary')} />
        </label>

        <div className="form-row">
          <label>Location
            <input value={form.location || ''} onChange={set('location')} placeholder="e.g. Kathmandu" />
          </label>
          <label>Country
            <input value={form.country || ''} onChange={set('country')} maxLength={2} placeholder="e.g. NP" />
          </label>
          <label>Years of experience
            <input type="number" step="0.5" value={form.years_experience || ''} onChange={set('years_experience')} />
          </label>
        </div>

        <label>Employment status
          <select value={form.employment_status || ''} onChange={set('employment_status')}>
            <option value="">Not specified</option>
            <option value="employed">Employed</option>
            <option value="unemployed">Unemployed</option>
            <option value="open_to_work">Open to work</option>
            <option value="not_looking">Not looking</option>
          </select>
        </label>

        <div className="form-row">
          <label>LinkedIn URL
            <input value={form.linkedin_url || ''} onChange={set('linkedin_url')} placeholder="https://linkedin.com/in/…" />
          </label>
          <label>Portfolio URL
            <input value={form.portfolio_url || ''} onChange={set('portfolio_url')} placeholder="https://…" />
          </label>
        </div>

        {error && <div className="error">{error}</div>}
        {saved && <div className="success">Saved ✓</div>}

        <button type="submit" className="btn btn-primary" disabled={saving}>
          {saving ? 'Saving…' : 'Save Profile'}
        </button>
      </form>

      {isCandidate && <ResumeSection />}
      {isCandidate && <SkillsSection />}
    </div>
  )
}