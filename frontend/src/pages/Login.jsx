import { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import api from '../api/client.js'
import { useAuth } from '../context/AuthContext.jsx'

export default function Login() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('admin@tekvito.local')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)
  const [needsVerification, setNeedsVerification] = useState(false)
  const [resending, setResending] = useState(false)
  const [resendMessage, setResendMessage] = useState('')

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setNeedsVerification(false)
    setResendMessage('')
    setLoading(true)
    try {
      await login(email, password)
      navigate('/dashboard')
    } catch (err) {
      const data = err?.response?.data
      if (data?.code === 'EMAIL_NOT_VERIFIED') {
        setNeedsVerification(true)
        setError(data.error || 'Please verify your email before logging in')
      } else {
        setError(data?.error || err.message || 'Login failed')
      }
    } finally {
      setLoading(false)
    }
  }

  const resendVerification = async () => {
    setResending(true)
    setResendMessage('')
    setError('')
    try {
      const res = await api.post('/auth/resend-verification', { email })
      setResendMessage(res.data.message || 'Verification email sent')
    } catch (err) {
      setError(err?.response?.data?.error || 'Failed to resend verification email')
    } finally {
      setResending(false)
    }
  }

  return (
    <div className="page">
      <div className="card auth-card">
        <h2>Login</h2>
        <form onSubmit={handleSubmit}>
          <label>
            Email
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
            />
          </label>
          <label>
            Password
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </label>

          {error && <div className="error">{error}</div>}

          {needsVerification && (
            <div className="card" style={{ marginTop: 12, padding: 16, background: '#fef3c7', borderRadius: 8 }}>
              <p style={{ margin: '0 0 12px', fontSize: 14 }}>
                Your email isn't verified yet. Check your inbox for the verification link, or request a new one:
              </p>
              <button
                type="button"
                className="btn btn-secondary"
                onClick={resendVerification}
                disabled={resending}
              >
                {resending ? 'Sending…' : 'Resend verification email'}
              </button>
              {resendMessage && (
                <p style={{ marginTop: 10, fontSize: 13, color: '#065f46' }}>{resendMessage}</p>
              )}
            </div>
          )}

          <button type="submit" className="btn btn-primary" disabled={loading}>
            {loading ? 'Logging in…' : 'Login'}
          </button>
        </form>

        <div style={{ marginTop: '16px', textAlign: 'center', fontSize: '14px' }}>
          <Link to="/forgot-password" style={{ color: '#2563eb' }}>
            Forgot your password?
          </Link>
        </div>

        <p className="muted">
          No account? <Link to="/register">Register</Link>
        </p>
      </div>
    </div>
  )
}