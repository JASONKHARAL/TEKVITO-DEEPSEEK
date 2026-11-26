import { useEffect, useState } from 'react'
import { Link, useSearchParams, useNavigate } from 'react-router-dom'
import api from '../api/client.js'

export default function VerifyEmail() {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const [status, setStatus] = useState('verifying') // verifying | success | error
  const [message, setMessage] = useState('')
  const [email, setEmail] = useState('')

  useEffect(() => {
    const token = searchParams.get('token')
    if (!token) {
      setStatus('error')
      setMessage('Missing verification token')
      return
    }

    api.get('/auth/verify-email', { params: { token } })
      .then((res) => {
        setStatus('success')
        setMessage(res.data.message || 'Email verified!')
        setEmail(res.data.email || '')
      })
      .catch((err) => {
        setStatus('error')
        setMessage(err?.response?.data?.error || 'Verification failed')
      })
  }, [searchParams])

  return (
    <div className="page">
      <div className="card auth-card" style={{ textAlign: 'center' }}>
        {status === 'verifying' && (
          <>
            <div style={{ fontSize: 56, marginBottom: 16 }}>⏳</div>
            <h2>Verifying your email…</h2>
            <p className="muted">This will only take a moment.</p>
          </>
        )}

        {status === 'success' && (
          <>
            <div style={{ fontSize: 56, marginBottom: 16 }}>✅</div>
            <h2>Email verified!</h2>
            <p className="muted">{message}</p>
            {email && <p className="muted" style={{ fontSize: 13 }}>{email}</p>}
            <button
              className="btn btn-primary"
              style={{ marginTop: 20 }}
              onClick={() => navigate('/login')}
            >
              Go to Login
            </button>
          </>
        )}

        {status === 'error' && (
          <>
            <div style={{ fontSize: 56, marginBottom: 16 }}>❌</div>
            <h2>Verification failed</h2>
            <p className="error">{message}</p>
            <p className="muted" style={{ marginTop: 20 }}>
              The link may have expired. Request a new one from the login page.
            </p>
            <Link to="/login" className="btn btn-secondary" style={{ marginTop: 16 }}>
              Back to Login
            </Link>
          </>
        )}
      </div>
    </div>
  )
}