import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext.jsx'
import NotificationBell from './NotificationBell.jsx'

export default function Navbar() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const isCandidate = user?.roles?.includes('candidate')
  const isAdmin = user?.roles?.some((r) => ['super_admin', 'recruiter'].includes(r))
  const isSuperAdmin = user?.roles?.includes('super_admin')

  const handleLogout = () => { logout(); navigate('/') }

  return (
    <nav className="navbar">
      <Link to="/" className="brand">TEKVITO</Link>
      <div className="nav-links">
        <Link to="/">Home</Link>
        <Link to="/jobs">Jobs</Link>
        {user && <Link to="/dashboard">Dashboard</Link>}
        {user && isCandidate && <Link to="/applications">My Applications</Link>}
        {user && isCandidate && <Link to="/saved-jobs">Saved</Link>}
        {user && isAdmin && <Link to="/my-jobs">My Jobs</Link>}
        {user && isAdmin && <Link to="/received-applications">Applicants</Link>}
        {user && isSuperAdmin && <Link to="/admin/users">Users</Link>}
        {user ? (
          <>
            <Link to="/profile">Profile</Link>
            <NotificationBell />
            <span className="nav-user">{user.first_name} ({user.roles?.[0]})</span>
            <button onClick={handleLogout} className="btn-link">Logout</button>
          </>
        ) : (
          <>
            <Link to="/login">Login</Link>
            <Link to="/register">Register</Link>
          </>
        )}
      </div>
    </nav>
  )
}