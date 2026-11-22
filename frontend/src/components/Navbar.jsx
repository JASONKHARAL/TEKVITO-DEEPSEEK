import { useState } from 'react'
import { Link, NavLink, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext.jsx'
import NotificationBell from './NotificationBell.jsx'

export default function Navbar() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const [menuOpen, setMenuOpen] = useState(false)

  const isCandidate = user?.roles?.includes('candidate')
  const isAdmin = user?.roles?.some((r) => ['super_admin', 'recruiter'].includes(r))
  const isSuperAdmin = user?.roles?.includes('super_admin')

  const handleLogout = () => { 
    logout(); 
    setMenuOpen(false); 
    navigate('/') 
  }

  const closeMenu = () => setMenuOpen(false)
  const initial = user?.first_name?.[0]?.toUpperCase() || '?'

  return (
    <nav className="navbar">
      <div className="navbar-inner">
        {/* Brand */}
        <Link to="/" className="brand" onClick={closeMenu}>
          <span className="brand-mark">T</span>
          <span className="brand-text">TEKVITO</span>
        </Link>

        {/* Mobile Burger Icon */}
        <button className="mobile-menu-btn" onClick={() => setMenuOpen(!menuOpen)}>
          {menuOpen ? '✕' : '☰'}
        </button>

        {/* Nav Links (Collapses into dropdown on mobile) */}
        <div className={`nav-links ${menuOpen ? 'open' : ''}`}>
          <NavLink to="/" end className={({ isActive }) => isActive ? 'nav-link active' : 'nav-link'} onClick={closeMenu}>
            Home
          </NavLink>
          <NavLink to="/jobs" className={({ isActive }) => isActive ? 'nav-link active' : 'nav-link'} onClick={closeMenu}>
            Jobs
          </NavLink>
          <NavLink to="/about" className={({ isActive }) => isActive ? 'nav-link active' : 'nav-link'} onClick={closeMenu}>
           About
          </NavLink>

          {user && (
            <NavLink to="/dashboard" className={({ isActive }) => isActive ? 'nav-link active' : 'nav-link'} onClick={closeMenu}>
              Dashboard
            </NavLink>
          )}
          {user && isCandidate && (
            <NavLink to="/applications" className={({ isActive }) => isActive ? 'nav-link active' : 'nav-link'} onClick={closeMenu}>
              My Applications
            </NavLink>
          )}
          {user && isCandidate && (
            <NavLink to="/saved-jobs" className={({ isActive }) => isActive ? 'nav-link active' : 'nav-link'} onClick={closeMenu}>
              Saved
            </NavLink>
          )}
          {user && isAdmin && (
            <NavLink to="/my-jobs" className={({ isActive }) => isActive ? 'nav-link active' : 'nav-link'} onClick={closeMenu}>
              My Jobs
            </NavLink>
          )}
          {user && isAdmin && (
            <NavLink to="/received-applications" className={({ isActive }) => isActive ? 'nav-link active' : 'nav-link'} onClick={closeMenu}>
              Applicants
            </NavLink>
          )}
          {user && isSuperAdmin && (
            <NavLink to="/admin/users" className={({ isActive }) => isActive ? 'nav-link active' : 'nav-link'} onClick={closeMenu}>
              Users
            </NavLink>
          )}
          {user && isSuperAdmin && (
            <NavLink to="/admin/talent-requests" className={({ isActive }) => isActive ? 'nav-link active' : 'nav-link'} onClick={closeMenu}>
              Talent Requests
            </NavLink>
          )}

          {/* Mobile Auth Actions (moved inside menu) */}
          <div className="mobile-auth-actions">
            {!user ? (
              <>
                <NavLink to="/hire" className="nav-link" onClick={closeMenu}>For Employers</NavLink>
                <Link to="/login" className="btn btn-secondary" onClick={closeMenu}>Login</Link>
                <Link to="/register" className="btn btn-primary" onClick={closeMenu}>Sign up</Link>
              </>
            ) : (
              <button onClick={handleLogout} className="btn btn-secondary" style={{ width: '100%' }}>Logout</button>
            )}
          </div>
        </div>

        {/* Desktop Right Side Actions */}
        <div className="nav-actions desktop-actions">
          {user ? (
            <>
              <NotificationBell />
              <Link to="/profile" className="user-chip" title={`${user.first_name} ${user.last_name}`}>
                <span className="user-avatar">{initial}</span>
                <span className="user-name">{user.first_name}</span>
              </Link>
              <button onClick={handleLogout} className="btn-logout">Logout</button>
            </>
          ) : (
            <>
              <NavLink to="/hire" className="nav-link" style={{ marginRight: '8px' }}>For Employers</NavLink>
              <Link to="/login" className="btn btn-secondary">Login</Link>
              <Link to="/register" className="btn btn-primary">Sign up</Link>
            </>
          )}
        </div>
      </div>
    </nav>
  )
}