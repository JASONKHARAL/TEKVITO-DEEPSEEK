import { useEffect, useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { getJobVersions, getJobVersionDetail } from '../api/jobs.js'

export default function JobVersions() {
  const { id } = useParams()
  const [versions, setVersions] = useState([])
  const [selected, setSelected] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    getJobVersions(id)
      .then((r) => setVersions(r.data.versions || []))
      .catch((e) => setError(e?.response?.data?.error || 'Failed to load'))
      .finally(() => setLoading(false))
  }, [id])

  const openVersion = (vnum) => {
    getJobVersionDetail(id, vnum)
      .then((r) => setSelected(r.data.version))
      .catch(() => setSelected(null))
  }

  if (loading) return <div className="page"><p>Loading…</p></div>

  return (
    <div className="page">
      <Link to={`/my-jobs`} className="muted">← Back to my jobs</Link>
      <h1>Version History</h1>
      <p className="muted">Snapshots are created automatically when a published job is edited.</p>

      {error && <p className="error">{error}</p>}

      {versions.length === 0 && (
        <div className="card"><p>No versions yet. Edit the published job to create one.</p></div>
      )}

      {versions.length > 0 && (
        <div className="card">
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <thead>
              <tr style={{ textAlign: 'left', borderBottom: '1px solid #e5e7eb' }}>
                <th style={{ padding: 10 }}>Version</th>
                <th style={{ padding: 10 }}>Reason</th>
                <th style={{ padding: 10 }}>Changed by</th>
                <th style={{ padding: 10 }}>Date</th>
                <th style={{ padding: 10 }}>Action</th>
              </tr>
            </thead>
            <tbody>
              {versions.map((v) => (
                <tr key={v.id} style={{ borderBottom: '1px solid #f3f4f6' }}>
                  <td style={{ padding: 10 }}><strong>v{v.version_number}</strong></td>
                  <td style={{ padding: 10, color: '#6b7280' }}>{v.change_reason || '—'}</td>
                  <td style={{ padding: 10 }}>{v.first_name} {v.last_name}</td>
                  <td style={{ padding: 10, fontSize: 13, color: '#6b7280' }}>
                    {new Date(v.created_at.replace(' ', 'T')).toLocaleString()}
                  </td>
                  <td style={{ padding: 10 }}>
                    <button className="btn btn-secondary" onClick={() => openVersion(v.version_number)}>View snapshot</button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {selected && (
        <div className="card" style={{ marginTop: 24 }}>
          <h2>Snapshot: version {selected.version_number}</h2>
          <p className="muted">
            Changed by {selected.first_name} {selected.last_name} on{' '}
            {new Date(selected.created_at.replace(' ', 'T')).toLocaleString()}
          </p>
          <pre style={{ background: '#f9fafb', padding: 16, borderRadius: 6, overflow: 'auto', fontSize: 13 }}>
            {JSON.stringify(selected.snapshot, null, 2)}
          </pre>
        </div>
      )}
    </div>
  )
}