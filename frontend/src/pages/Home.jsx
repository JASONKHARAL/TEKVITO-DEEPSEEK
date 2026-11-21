import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { listJobs } from '../api/jobs.js'
import JobCard from '../components/JobCard.jsx'

export default function Home() {
  const [jobs, setJobs] = useState([])

  useEffect(() => {
    listJobs({ per_page: 6 })
      .then((res) => setJobs(res.data.data || []))
      .catch(() => {})
  }, [])

  return (
    <div className="page">
      <div className="hero">
        <h1>Welcome to TEKVITO</h1>
        <p>Find your next opportunity or hire top talent.</p>
        <div className="hero-actions">
          <Link to="/jobs" className="btn btn-primary">Browse Jobs</Link>
          <Link to="/register" className="btn btn-secondary">Create Account</Link>
        </div>
      </div>

      {jobs.length > 0 && (
        <div style={{ marginTop: 60 }}>
          <h2>Latest Jobs</h2>
          <div className="job-grid">
            {jobs.map((job) => (
              <JobCard key={job.id} job={job} />
            ))}
          </div>
          <div style={{ textAlign: 'center', marginTop: 24 }}>
            <Link to="/jobs" className="btn btn-secondary">View all jobs</Link>
          </div>
        </div>
      )}
    </div>
  )
}