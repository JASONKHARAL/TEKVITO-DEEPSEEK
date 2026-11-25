
import { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { Turnstile } from '@marsidev/react-turnstile'
import { useAuth } from '../context/AuthContext.jsx'

const SITE_KEY = '0x4AAAAAAGS1av2O0W5KzD8q'

export default function Register() {
  const { register } = useAuth()
  const navigate = useNavigate()

  const [form, setForm] = useState({
    email: '',
    password: '',
    first_name: '',
    last_name: ''
  })

  const [captchaToken, setCaptchaToken] = useState('')
  const [captchaKey, setCaptchaKey] = useState(0)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value })
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')

    if (!captchaToken) {
      setError('Please complete the CAPTCHA verification.')
      return
    }

    setLoading(true)

    try {
      await register({
        ...form,
        turnstile_token: captchaToken
      })

      navigate('/dashboard')
    } catch (err) {
      const msg =
        err?.response?.data?.error ||
        err.message ||
        'Registration failed'

      setError(msg)
      setCaptchaToken('')
      setCaptchaKey((key) => key + 1)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="page">
      <div className="card auth-card">
        <h2>Create Account</h2>

        <form onSubmit={handleSubmit}>
          <label>
            First Name
            <input
              name="first_name"
              value={form.first_name}
              onChange={handleChange}
              required
            />
          </label>

          <label>
            Last Name
            <input
              name="last_name"
              value={form.last_name}
              onChange={handleChange}
              required
            />
          </label>

          <label>
            Email
            <input
              name="email"
              type="email"
              value={form.email}
              onChange={handleChange}
              required
            />
          </label>

          <label>
            Password
            <input
              name="password"
              type="password"
              value={form.password}
              onChange={handleChange}
              minLength={8}
              required
            />
          </label>

          <div style={{ margin: '16px 0' }}>
            <Turnstile
              key={captchaKey}
              siteKey={SITE_KEY}
              onSuccess={setCaptchaToken}
              onExpire={() => setCaptchaToken('')}
              onError={() => setCaptchaToken('')}
              options={{ theme: 'auto' }}
            />
          </div>

          {error && <div className="error">{error}</div>}

          <button
            type="submit"
            className="btn btn-primary"
            disabled={loading || !captchaToken}
          >
            {loading ? 'Creating…' : 'Create Account'}
          </button>
        </form>

        <p className="muted">
          Already registered? <Link to="/login">Login</Link>
        </p>
      </div>
    </div>
  )
}
