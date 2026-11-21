import { Link } from 'react-router-dom'

export default function JobCard({ job }) {
  const salary = formatSalary(job)

  return (
    <Link to={`/jobs/${job.id}`} className="job-card">
      <div className="job-card-header">
        <h3>{job.title}</h3>
        <span className={`badge badge-${job.workplace_type}`}>{job.workplace_type}</span>
      </div>
      <div className="job-card-meta">
        {job.category_name && <span>{job.category_name}</span>}
        {job.location && <span>{job.location}{job.country ? `, ${job.country}` : ''}</span>}
        <span>{formatEmploymentType(job.employment_type)}</span>
      </div>
      {salary && <div className="job-card-salary">{salary}</div>}
      <div className="job-card-footer">
        <span className="muted">Posted {formatDate(job.published_at || job.created_at)}</span>
      </div>
    </Link>
  )
}

function formatSalary(job) {
  if (!job.salary_min && !job.salary_max) return null
  const currency = job.salary_currency || 'USD'
  const fmt = (n) => new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(n)
  if (job.salary_min && job.salary_max) return `${currency} ${fmt(job.salary_min)} – ${fmt(job.salary_max)}`
  if (job.salary_min) return `${currency} ${fmt(job.salary_min)}+`
  return `Up to ${currency} ${fmt(job.salary_max)}`
}

function formatEmploymentType(t) {
  const map = {
    full_time: 'Full-time',
    part_time: 'Part-time',
    contract: 'Contract',
    contract_to_hire: 'Contract to hire',
    permanent: 'Permanent',
  }
  return map[t] || t
}

function formatDate(d) {
  if (!d) return 'recently'
  const date = new Date(d.replace(' ', 'T'))
  const days = Math.floor((Date.now() - date.getTime()) / 86400000)
  if (days <= 0) return 'today'
  if (days === 1) return 'yesterday'
  if (days < 7) return `${days} days ago`
  return date.toLocaleDateString()
}