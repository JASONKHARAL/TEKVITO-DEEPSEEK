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