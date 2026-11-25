
import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { submitHireRequest } from '../api/talentRequests.js'
import { listAllSkills } from '../api/skills.js'
import { Turnstile } from '@marsidev/react-turnstile'

const SITE_KEY = '0x4AAAAAAGS1av2O0W5KzD8q'

export default function Hire() {
  const [skills, setSkills] = useState([])
  const [form, setForm] = useState({
    contact_name: '',
    contact_email: '',
    contact_phone: '',
    company_name: '',
    job_title: '',
    job_description: '',
    required_skills: '',
    location: '',
    country: '',
    budget: '',
    timeline: 'flexible',
    preferred_contact: 'email',
  })

  const [submitting, setSubmitting] = useState(false)
  const [success, setSuccess] = useState(false)
  const [error, setError] = useState('')
  const [captchaToken, setCaptchaToken] = useState('')
  const [captchaKey, setCaptchaKey] = useState(0)

  useEffect(() => {
    listAllSkills()
      .then((r) => setSkills(r.data.skills || []))
      .catch(() => {})
  }, [])

  const set = (k) => (e) =>
    setForm({ ...form, [k]: e.target.value })

  const submit = async (e) => {
    e.preventDefault()
    setError('')

    if (!captchaToken) {
      setError('Please complete the security verification.')
      return
    }

    setSubmitting(true)

    try {
      await submitHireRequest({
        ...form,
        turnstile_token: captchaToken,
      })

      setSuccess(true)
      window.scrollTo({ top: 0, behavior: 'smooth' })
    } catch (err) {
      setError(err?.response?.data?.error || 'Failed to submit')

      // Reset CAPTCHA so the user can try again
      setCaptchaToken('')
      setCaptchaKey((key) => key + 1)
    } finally {
      setSubmitting(false)
    }
  }

  if (success) {
    return (
      <div className="page">
        <div
          className="hire-page-card"
          style={{
            maxWidth: 600,
            margin: '40px auto',
            textAlign: 'center',
            padding: 40,
          }}
        >
          <div
            style={{
              fontSize: 56,
              marginBottom: 16,
              color: '#16a34a',
            }}
          >
            OK
          </div>

          <h1>Request received!</h1>

          <p>
            Thanks - we've got your request. Our team will review it and
            get back to you within 1 business day.
          </p>

          <p className="muted">
            Want to explore TEKVITO in the meantime?
          </p>

          <Link
            to="/jobs"
            className="btn btn-primary"
            style={{ marginTop: 16 }}
          >
            Browse Jobs
          </Link>
        </div>
      </div>
    )
  }

  return (
    <div className="page" style={{ maxWidth: 760 }}>
      <Link to="/" className="muted">
        &lt; Back to home
      </Link>

      <div className="hire-hero">
        <div className="hero-badge">For employers</div>

        <h1
          style={{
            fontSize: '2.5rem',
            fontWeight: 800,
            letterSpacing: '-0.02em',
            marginBottom: '16px',
          }}
        >
          Let TEKVITO find your next great hire
        </h1>

        <p
          style={{
            fontSize: '1.1rem',
            color: '#4b5563',
            lineHeight: 1.6,
          }}
        >
          Not ready to post a job yourself? Tell us what you need - we'll
          review your request and match you with qualified candidates
          from our network.
        </p>
      </div>

      <form
        className="hire-page-card"
        onSubmit={submit}
        style={{ marginTop: 32, padding: '40px' }}
      >
        <h2>About you</h2>

        <div className="form-row">
          <label>
            Your name*
            <input
              value={form.contact_name}
              onChange={set('contact_name')}
              required
            />
          </label>

          <label>
            Email*
            <input
              type="email"
              value={form.contact_email}
              onChange={set('contact_email')}
              required
            />
          </label>
        </div>

        <div className="form-row">
          <label>
            Phone (optional)
            <input
              value={form.contact_phone}
              onChange={set('contact_phone')}
            />
          </label>

          <label>
            Company*
            <input
              value={form.company_name}
              onChange={set('company_name')}
              required
            />
          </label>
        </div>

        <h2>Hiring requirements</h2>

        <label>
          Job title*
          <input
            value={form.job_title}
            onChange={set('job_title')}
            required
            placeholder="e.g. Senior React Developer"
          />
        </label>

        <label>
          Job description*
          <textarea
            rows={5}
            value={form.job_description}
            onChange={set('job_description')}
            required
            placeholder="What will this person work on? What are the main responsibilities?"
          />
        </label>

        <label>
          Required skills (comma-separated)
          <input
            value={form.required_skills}
            onChange={set('required_skills')}
            placeholder="e.g. React, TypeScript, Node.js"
          />
        </label>

        <div className="form-row">
          <label>
            Location
            <input
              value={form.location}
              onChange={set('location')}
            />
          </label>

          <label>
            Country
            <input
              value={form.country}
              onChange={set('country')}
              maxLength={2}
              placeholder="e.g. NP"
            />
          </label>
        </div>

        <div className="form-row">
          <label>
            Budget / salary range
            <input
              value={form.budget}
              onChange={set('budget')}
              placeholder="e.g. USD 60k-90k / year"
            />
          </label>

          <label>
            Timeline
            <select
              value={form.timeline}
              onChange={set('timeline')}
            >
              <option value="asap">ASAP</option>
              <option value="1_2_weeks">1-2 weeks</option>
              <option value="1_month">Within a month</option>
              <option value="flexible">Flexible</option>
            </select>
          </label>
        </div>

        <div className="form-row">
          <label>
            Preferred contact method
            <select
              value={form.preferred_contact}
              onChange={set('preferred_contact')}
            >
              <option value="email">Email</option>
              <option value="phone">Phone</option>
              <option value="either">Either</option>
            </select>
          </label>

          <div></div>
        </div>

        {/* Cloudflare Turnstile CAPTCHA */}
        <div style={{ marginTop: 20, marginBottom: 16 }}>
          <Turnstile
            key={captchaKey}
            siteKey={SITE_KEY}
            onSuccess={(token) => {
              setCaptchaToken(token)
              setError('')
            }}
            onExpire={() => setCaptchaToken('')}
            onError={() => {
              setCaptchaToken('')
              setError('Security verification failed. Please try again.')
            }}
          />
        </div>

        {error && <div className="error">{error}</div>}

        <button
          type="submit"
          className="btn btn-primary"
          disabled={submitting || !captchaToken}
          style={{ marginTop: 8 }}
        >
          {submitting ? 'Sending...' : 'Send Request'}
        </button>

        <p
          className="muted"
          style={{ marginTop: 12, fontSize: 13 }}
        >
          We'll never share your details. No account required.
        </p>
      </form>
    </div>
  )
}
