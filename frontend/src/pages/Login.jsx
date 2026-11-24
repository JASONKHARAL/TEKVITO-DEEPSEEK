import { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext.jsx'

export default function Login() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('admin@tekvito.local')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      await login(email, password)
      navigate('/dashboard')
    } catch (err) {
      const msg = err?.response?.data?.error || err.message || 'Login failed'
      setError(msg)
    } finally {
      setLoading(false)
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