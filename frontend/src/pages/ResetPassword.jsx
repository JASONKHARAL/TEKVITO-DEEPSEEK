import { useState } from 'react'
import { useSearchParams, useNavigate, Link } from 'react-router-dom'
import { resetPassword } from '../api/auth.js'

export default function ResetPassword() {
  const [searchParams] = useSearchParams()
  const token = searchParams.get('token')
  const navigate = useNavigate()
  
  const [password, setPassword] = useState('')
  const [confirm, setConfirm] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const submit = async (e) => {
    e.preventDefault()
    setError('')
    if (password !== confirm) return setError('Passwords do not match')
    if (password.length < 8) return setError('Password must be at least 8 characters')
    
    setLoading(true)
    try {
      await resetPassword(token, password)
      alert('Password reset successfully! You can now log in.')
      navigate('/login')
    } catch (err) {
      setError(err?.response?.data?.error || 'Failed to reset password')
    } finally {
      setLoading(false)
    }
  }

  if (!token) return (
    <div className="page"><p className="error">Invalid reset link.</p><Link to="/login">Back to login</Link></div>
  )

  return (
    <div className="page" style={{ maxWidth: 400, margin: '40px auto' }}>
      <h1>Reset Password</h1>
      <form className="card" onSubmit={submit} style={{ marginTop: 20 }}>
        <label>New Password
          <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} required minLength={8} />
        </label>
        <label>Confirm Password
          <input type="password" value={confirm} onChange={(e) => setConfirm(e.target.value)} required minLength={8} />
        </label>
        {error && <div className="error">{error}</div>}
        <button type="submit" className="btn btn-primary" disabled={loading} style={{ marginTop: 16 }}>
          {loading ? 'Resetting...' : 'Reset Password'}
        </button>
      </form>
    </div>
  )
}