import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { listJobs, getCategories } from '../api/jobs.js'
import { listAllSkills } from '../api/skills.js'
import JobCard from '../components/JobCard.jsx'

export default function Home() {
  const navigate = useNavigate()
  const [jobs, setJobs] = useState([])
  const [categories, setCategories] = useState([])
  const [skills, setSkills] = useState([])
  const [stats, setStats] = useState({ jobs: 0, categories: 0, skills: 0 })
  const [q, setQ] = useState('')
  const [categoryId, setCategoryId] = useState('')
  const [skillId, setSkillId] = useState('')

  useEffect(() => {
    Promise.all([
      listJobs({ per_page: 6 }),
      getCategories(),
      listAllSkills(),
    ])
      .then(([jRes, cRes, sRes]) => {
        setJobs(jRes.data.data || [])
        setCategories(cRes.data.categories || [])
        setSkills(sRes.data.skills || [])
        setStats({
          jobs: jRes.data.meta?.total || 0,
          categories: (cRes.data.categories || []).length,
          skills: (sRes.data.skills || []).length,
        })
      })
      .catch(() => {})
  }, [])

  const handleSearch = (e) => {
    e.preventDefault()
    const params = new URLSearchParams()
    if (q) params.set('q', q)
    if (categoryId) params.set('category_id', categoryId)
    if (skillId) params.set('skill_id', skillId)
    navigate('/jobs?' + params.toString())
  }

  return (
    <div className="home-page">
      {/* ============ HERO ============ */}
      <section className="hero-section">
        <div className="hero-blob hero-blob-1" />
        <div className="hero-blob hero-blob-2" />
        <div className="hero-blob hero-blob-3" />

        <div className="hero-inner">
          <div className="hero-badge">✨ The hiring platform built for you</div>

          <h1 className="hero-title">
            Find your <span className="hero-gradient">dream job</span>
            <br />
            or hire top talent
          </h1>

          <p className="hero-subtitle">
            Thousands of opportunities. One beautiful place. Search, apply, and get hired — all in TEKVITO.
          </p>

          {/* Search bar */}
          <form className="hero-search" onSubmit={handleSearch}>
            <div className="hero-search-row">
              <input
                type="text"
                placeholder="Search jobs, skills, companies…"
                value={q}
                onChange={(e) => setQ(e.target.value)}
                className="hero-search-input"
              />
              <select
                value={categoryId}
                onChange={(e) => setCategoryId(e.target.value)}
                className="hero-search-select"
              >
                <option value="">All categories</option>
                {categories.map((c) => (
                  <option key={c.id} value={c.id}>{c.name}</option>
                ))}
              </select>
              <select
                value={skillId}
                onChange={(e) => setSkillId(e.target.value)}
                className="hero-search-select"
              >
                <option value="">Any skill</option>
                {skills.map((s) => (
                  <option key={s.id} value={s.id}>{s.name}</option>
                ))}
              </select>
              <button type="submit" className="hero-search-btn">
                Search Jobs
              </button>
            </div>
          </form>

          {/* Stats */}
          <div className="hero-stats">
            <div className="hero-stat">
              <div className="hero-stat-value">{stats.jobs}+</div>
              <div className="hero-stat-label">Live Jobs</div>
            </div>
            <div className="hero-stat">
              <div className="hero-stat-value">{stats.categories}</div>
              <div className="hero-stat-label">Categories</div>
            </div>
            <div className="hero-stat">
              <div className="hero-stat-value">{stats.skills}</div>
              <div className="hero-stat-label">Skills tracked</div>
            </div>
          </div>
        </div>
      </section>

      {/* ============ TRENDING CATEGORIES ============ */}
      {categories.length > 0 && (
        <section className="home-section">
          <div className="home-section-header">
            <h2>Browse by category</h2>
            <Link to="/jobs" className="muted">View all →</Link>
          </div>
          <div className="category-chips">
            {categories.slice(0, 8).map((c) => (
              <Link
                key={c.id}
                to={`/jobs?category_id=${c.id}`}
                className="category-chip"
              >
                {c.name}
              </Link>
            ))}
          </div>
        </section>
      )}

      {/* ============ LATEST JOBS ============ */}
      {jobs.length > 0 && (
        <section className="home-section">
          <div className="home-section-header">
            <h2>Latest jobs</h2>
            <Link to="/jobs" className="muted">View all →</Link>
          </div>
          <div className="job-grid">
            {jobs.map((job) => (
              <JobCard key={job.id} job={job} />
            ))}
          </div>
          <div style={{ textAlign: 'center', marginTop: 32 }}>
            <Link to="/jobs" className="btn btn-secondary">Browse all jobs</Link>
          </div>
        </section>
      )}

      {/* ============ CTA FOR EMPLOYERS ============ */}
      <section className="home-cta">
        <h2>Hiring? Post a job in minutes.</h2>
        <p>Reach thousands of qualified candidates. Free to start.</p>
        <Link to="/register" className="btn btn-primary">Get started</Link>
      </section>
    </div>
  )
}