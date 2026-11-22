import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { listJobs, getCategories } from '../api/jobs.js'
import { listAllSkills } from '../api/skills.js'
import JobCard from '../components/JobCard.jsx'

export default function Jobs() {
  const [searchParams] = useSearchParams()
  const [jobs, setJobs] = useState([])
  const [categories, setCategories] = useState([])
  const [skills, setSkills] = useState([])
  const [meta, setMeta] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [filters, setFilters] = useState({
    q: searchParams.get('q') || '',
    category_id: searchParams.get('category_id') || '',
    country: searchParams.get('country') || '',
    skill_id: searchParams.get('skill_id') || '',
    page: 1,
  })

  useEffect(() => {
    Promise.all([getCategories(), listAllSkills()])
      .then(([cRes, sRes]) => {
        setCategories(cRes.data.categories || [])
        setSkills(sRes.data.skills || [])
      })
      .catch(() => {})
  }, [])

  useEffect(() => {
    setLoading(true)
    setError('')
    const params = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== ''))
    listJobs(params)
      .then((res) => {
        setJobs(res.data.data || [])
        setMeta(res.data.meta)
      })
      .catch((err) => setError(err?.response?.data?.error || 'Failed to load jobs'))
      .finally(() => setLoading(false))
  }, [filters])

  const update = (key, value) => setFilters((f) => ({ ...f, [key]: value, page: 1 }))

  return (
    <div className="page">
      <h1>Browse Jobs</h1>

      <div className="filters">
        <input
          type="text"
          placeholder="Search jobs…"
          value={filters.q}
          onChange={(e) => update('q', e.target.value)}
        />
        <select
          value={filters.category_id}
          onChange={(e) => update('category_id', e.target.value)}
        >
          <option value="">All categories</option>
          {categories.map((c) => (
            <option key={c.id} value={c.id}>{c.name}</option>
          ))}
        </select>
        <select
          value={filters.skill_id}
          onChange={(e) => update('skill_id', e.target.value)}
        >
          <option value="">Any skill</option>
          {skills.map((s) => (
            <option key={s.id} value={s.id}>{s.name}</option>
          ))}
        </select>
        <input
          type="text"
          placeholder="Country (e.g. NP)"
          value={filters.country}
          onChange={(e) => update('country', e.target.value)}
        />
      </div>

            {loading && (
        <div className="job-grid">
          {[1, 2, 3, 4, 5, 6].map((n) => (
            <div key={n} className="skeleton-card">
              <div className="skeleton skeleton-title"></div>
              <div className="skeleton skeleton-text short"></div>
              <div className="skeleton skeleton-badge" style={{ marginTop: '10px' }}></div>
              <div className="skeleton skeleton-text" style={{ marginTop: '10px' }}></div>
            </div>
          ))}
        </div>
      )}
      {error && <p className="error">{error}</p>}

      {!loading && !error && jobs.length === 0 && (
        <div className="card">
          <p>No jobs found. Try clearing filters.</p>
        </div>
      )}

      <div className="job-grid">
        {jobs.map((job) => (
          <JobCard key={job.id} job={job} />
        ))}
      </div>

      {meta && meta.total_pages > 1 && (
        <div className="pagination">
          <button
            disabled={meta.page <= 1}
            onClick={() => setFilters((f) => ({ ...f, page: f.page - 1 }))}
          >
            Previous
          </button>
          <span>Page {meta.page} of {meta.total_pages}</span>
          <button
            disabled={meta.page >= meta.total_pages}
            onClick={() => setFilters((f) => ({ ...f, page: f.page + 1 }))}
          >
            Next
          </button>
        </div>
      )}
    </div>
  )
}