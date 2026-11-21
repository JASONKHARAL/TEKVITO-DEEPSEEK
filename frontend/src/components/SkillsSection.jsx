import { useEffect, useState } from 'react'
import { listAllSkills, mySkills, addSkill, removeSkill } from '../api/skills.js'

export default function SkillsSection() {
  const [allSkills, setAllSkills] = useState([])
  const [mine, setMine] = useState([])
  const [selectedId, setSelectedId] = useState('')
  const [proficiency, setProficiency] = useState('intermediate')
  const [years, setYears] = useState('')
  const [loading, setLoading] = useState(true)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')

  const load = () => {
    setLoading(true)
    Promise.all([listAllSkills(), mySkills()])
      .then(([aRes, mRes]) => {
        setAllSkills(aRes.data.skills || [])
        setMine(mRes.data.skills || [])
      })
      .catch((e) => setError(e?.response?.data?.error || 'Failed to load skills'))
      .finally(() => setLoading(false))
  }

  useEffect(load, [])

  const mineIds = new Set(mine.map((s) => s.id))
  const available = allSkills.filter((s) => !mineIds.has(s.id))

  const onAdd = async (e) => {
    e.preventDefault()
    if (!selectedId) return
    setBusy(true)
    setError('')
    try {
      const payload = {
        skill_id: Number(selectedId),
        proficiency,
      }
      if (years !== '') payload.years_experience = Number(years)
      const res = await addSkill(payload)
      setMine(res.data.skills || [])
      setSelectedId('')
      setYears('')
      setProficiency('intermediate')
    } catch (err) {
      setError(err?.response?.data?.error || 'Failed to add skill')
    } finally {
      setBusy(false)
    }
  }

  const onRemove = async (skillId) => {
    if (!confirm('Remove this skill?')) return
    setError('')
    try {
      const res = await removeSkill(skillId)
      setMine(res.data.skills || [])
    } catch (err) {
      setError(err?.response?.data?.error || 'Failed to remove')
    }
  }

  if (loading) return <div className="card" style={{ marginTop: 24 }}><p>Loading skills…</p></div>

  return (
    <div className="card" style={{ marginTop: 24 }}>
      <h2>Skills</h2>
      <p className="muted">Add your skills so employers can see your strengths.</p>

      <form onSubmit={onAdd} style={{ marginTop: 16, display: 'flex', gap: 10, flexWrap: 'wrap', alignItems: 'flex-end' }}>
        <label style={{ flex: '1 1 200px' }}>
          Skill
          <select value={selectedId} onChange={(e) => setSelectedId(e.target.value)} required>
            <option value="">Choose a skill…</option>
            {available.map((s) => (
              <option key={s.id} value={s.id}>{s.name}</option>
            ))}
          </select>
        </label>
        <label>
          Proficiency
          <select value={proficiency} onChange={(e) => setProficiency(e.target.value)}>
            <option value="beginner">Beginner</option>
            <option value="intermediate">Intermediate</option>
            <option value="advanced">Advanced</option>
            <option value="expert">Expert</option>
          </select>
        </label>
        <label>
          Years
          <input type="number" step="0.5" min="0" value={years} onChange={(e) => setYears(e.target.value)} style={{ width: 80 }} />
        </label>
        <button type="submit" className="btn btn-primary" disabled={busy || !selectedId}>
          {busy ? 'Adding…' : 'Add Skill'}
        </button>
      </form>

      {error && <div className="error" style={{ marginTop: 10 }}>{error}</div>}

      {mine.length === 0 && <p className="muted" style={{ marginTop: 16 }}>No skills added yet.</p>}

      {mine.length > 0 && (
        <table style={{ width: '100%', borderCollapse: 'collapse', marginTop: 16 }}>
          <thead>
            <tr style={{ textAlign: 'left', borderBottom: '1px solid #e5e7eb' }}>
              <th style={{ padding: 8 }}>Skill</th>
              <th style={{ padding: 8 }}>Proficiency</th>
              <th style={{ padding: 8 }}>Years</th>
              <th style={{ padding: 8 }}></th>
            </tr>
          </thead>
          <tbody>
            {mine.map((s) => (
              <tr key={s.id} style={{ borderBottom: '1px solid #f3f4f6' }}>
                <td style={{ padding: 8 }}><strong>{s.name}</strong></td>
                <td style={{ padding: 8, textTransform: 'capitalize' }}>{s.proficiency || '—'}</td>
                <td style={{ padding: 8 }}>{s.years_experience || '—'}</td>
                <td style={{ padding: 8 }}>
                  <button className="btn btn-danger" onClick={() => onRemove(s.id)}>Remove</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}