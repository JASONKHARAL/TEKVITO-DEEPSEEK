import { useState } from 'react'
import { Link } from 'react-router-dom'
import { forgotPassword } from '../api/auth.js'

export default function ForgotPassword() {
  const [email, setEmail] = useState('')
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const submit = async (e) => {
    e.preventDefault()
    setError(''); setMessage(''); setLoading(true)
    try {
      const res = await forgotPassword(email)
      setMessage(res.data.message)
    } catch (err) {
      setError(err?.response?.data?.error || 'Failed to send reset link')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="page" style={{ maxWidth: 400, margin: '40px auto' }}>
      <h1>Forgot Password</h1>
      <p className="muted">Enter your email to receive a reset link.</p>
      
      <form className="card" onSubmit={submit} style={{ marginTop: 20 }}>
        <label>Email Address
          <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
        </label>
        
        {error && <div className="error">{error}</div>}
        {message && <div className="success">{message}</div>}
        
        <button type="submit" className="btn btn-primary" disabled={loading} style={{ marginTop: 16 }}>
          {loading ? 'Sending...' : 'Send Reset Link'}
        </button>
      </form>
      
      <p className="muted" style={{ textAlign: 'center', marginTop: 16 }}>
        <Link to="/login">Back to login</Link>
      </p>
    </div>
  )
}