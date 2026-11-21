import { useEffect, useRef, useState } from 'react'
import { listResumes, uploadResume, deleteResume, makePrimary, downloadResume } from '../api/resumes.js'

export default function ResumeSection() {
  const [resumes, setResumes] = useState([])
  const [loading, setLoading] = useState(true)
  const [uploading, setUploading] = useState(false)
  const [error, setError] = useState('')
  const fileRef = useRef(null)

  const load = () => {
    setLoading(true)
    listResumes()
      .then((r) => setResumes(r.data.data || []))
      .catch((e) => setError(e?.response?.data?.error || 'Failed to load'))
      .finally(() => setLoading(false))
  }

  useEffect(load, [])

  const onUpload = async (e) => {
    e.preventDefault()
    if (!fileRef.current?.files?.[0]) return
    setUploading(true)
    setError('')
    try {
      await uploadResume(fileRef.current.files[0])
      fileRef.current.value = ''
      load()
    } catch (err) {
      setError(err?.response?.data?.error || 'Upload failed')
    } finally {
      setUploading(false)
    }
  }

  const onDelete = async (id) => {
    if (!confirm('Delete this resume?')) return
    try {
      await deleteResume(id)
      load()
    } catch (e) {
      setError(e?.response?.data?.error || 'Delete failed')
    }
  }

  const onPrimary = async (id) => {
    try {
      await makePrimary(id)
      load()
    } catch (e) {
      setError(e?.response?.data?.error || 'Failed')
    }
  }

  const onDownload = async (id, filename) => {
    try {
      await downloadResume(id, filename)
    } catch (e) {
      setError(e?.response?.data?.error || 'Download failed')
    }
  }

  return (
    <div className="card" style={{ marginTop: 24 }}>
      <h2>Resumes</h2>
      <p className="muted">Upload PDF or DOCX (max 5MB). Your primary resume is shown to employers.</p>

      <form onSubmit={onUpload} style={{ marginTop: 16, display: 'flex', gap: 10, alignItems: 'center', flexWrap: 'wrap' }}>
        <input
          ref={fileRef}
          type="file"
          accept=".pdf,.doc,.docx,application/pdf"
          disabled={uploading}
        />
        <button type="submit" className="btn btn-primary" disabled={uploading}>
          {uploading ? 'Uploading…' : 'Upload Resume'}
        </button>
      </form>

      {error && <div className="error" style={{ marginTop: 10 }}>{error}</div>}

      {loading && <p className="muted">Loading…</p>}

      {!loading && resumes.length === 0 && (
        <p className="muted" style={{ marginTop: 16 }}>No resumes yet.</p>
      )}

      {resumes.length > 0 && (
        <table style={{ width: '100%', borderCollapse: 'collapse', marginTop: 16 }}>
          <thead>
            <tr style={{ textAlign: 'left', borderBottom: '1px solid #e5e7eb' }}>
              <th style={{ padding: 8 }}>File</th>
              <th style={{ padding: 8 }}>Size</th>
              <th style={{ padding: 8 }}>Uploaded</th>
              <th style={{ padding: 8 }}>Actions</th>
            </tr>
          </thead>
          <tbody>
            {resumes.map((r) => (
              <tr key={r.id} style={{ borderBottom: '1px solid #f3f4f6' }}>
                <td style={{ padding: 8 }}>
                  {r.is_primary && <span className="badge" style={{ background: '#d1fae5', color: '#065f46', marginRight: 8 }}>primary</span>}
                  {r.original_filename}
                </td>
                <td style={{ padding: 8, color: '#6b7280', fontSize: 13 }}>
                  {(r.file_size_bytes / 1024).toFixed(0)} KB
                </td>
                <td style={{ padding: 8, color: '#6b7280', fontSize: 13 }}>
                  {new Date(r.uploaded_at.replace(' ', 'T')).toLocaleDateString()}
                </td>
                <td style={{ padding: 8, display: 'flex', gap: 6 }}>
                  <button className="btn btn-secondary" onClick={() => onDownload(r.id, r.original_filename)}>Download</button>
                  {!r.is_primary && (
                    <button className="btn btn-secondary" onClick={() => onPrimary(r.id)}>Make Primary</button>
                  )}
                  <button className="btn btn-danger" onClick={() => onDelete(r.id)}>Delete</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}