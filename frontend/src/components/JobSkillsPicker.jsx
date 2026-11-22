import { useEffect, useState } from 'react'
import { listAllSkills } from '../api/skills.js'
import { getJobSkills, setJobSkills } from '../api/jobSkills.js'

export default function JobSkillsPicker({ jobId }) {
  const [all, setAll] = useState([])
  const [selected, setSelected] = useState([])
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [saved, setSaved] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    Promise.all([listAllSkills(), getJobSkills(jobId)])
      .then(([aRes, jRes]) => {
        setAll(aRes.data.skills || [])
        setSelected((jRes.data.skills || []).map((s) => s.id))
      })
      .catch((e) => setError(e?.response?.data?.error || 'Failed to load'))
      .finally(() => setLoading(false))
  }, [jobId])

  const toggle = (id) => {
    setSelected((s) => s.includes(id) ? s.filter((x) => x !== id) : [...s, id])
    setSaved(false)
  }

  const save = async () => {
    setSaving(true)
    setError('')
    try {
      await setJobSkills(jobId, selected)
      setSaved(true)
    } catch (e) {
      setError(e?.response?.data?.error || 'Failed to save')
    } finally {
      setSaving(false)
    }
  }

  if (loading) return <div className="card"><p>Loading skills…</p></div>

  return (
    <div className="card">
      <h2>Required Skills</h2>
      <p className="muted">Pick the skills a candidate should have for this job.</p>

      <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8, marginTop: 16 }}>
        {all.map((s) => {
          const on = selected.includes(s.id)
          return (
            <button
              key={s.id}
              type="button"
              onClick={() => toggle(s.id)}
              className={`skill-chip ${on ? 'on' : ''}`}
            >
              {on ? '✓ ' : ''}{s.name}
            </button>
          )
        })}
      </div>

      {error && <div className="error" style={{ marginTop: 10 }}>{error}</div>}
      {saved && <div className="success" style={{ marginTop: 10 }}>Saved ✓</div>}

      <button className="btn btn-primary" onClick={save} disabled={saving} style={{ marginTop: 16 }}>
        {saving ? 'Saving…' : 'Save Skills'}
      </button>
    </div>
  )
}