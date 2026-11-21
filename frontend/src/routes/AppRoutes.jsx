import { Routes, Route } from 'react-router-dom'
import Home from '../pages/Home.jsx'
import Jobs from '../pages/Jobs.jsx'
import JobDetail from '../pages/JobDetail.jsx'
import NewJob from '../pages/NewJob.jsx'
import MyJobs from '../pages/MyJobs.jsx'
import EditJob from '../pages/EditJob.jsx'
import JobVersions from '../pages/JobVersions.jsx'
import Profile from '../pages/Profile.jsx'
import Applications from '../pages/Applications.jsx'
import ApplicationDetail from '../pages/ApplicationDetail.jsx'
import SavedJobs from '../pages/SavedJobs.jsx'
import ReceivedApplications from '../pages/ReceivedApplications.jsx'
import ReceivedApplicationDetail from '../pages/ReceivedApplicationDetail.jsx'
import AdminUsers from '../pages/AdminUsers.jsx'
import Notifications from '../pages/Notifications.jsx'
import Login from '../pages/Login.jsx'
import Register from '../pages/Register.jsx'
import Dashboard from '../pages/Dashboard.jsx'
import ProtectedRoute from '../components/ProtectedRoute.jsx'

export default function AppRoutes() {
  return (
    <Routes>
      <Route path="/" element={<Home />} />
      <Route path="/jobs" element={<Jobs />} />
      <Route path="/jobs/:id" element={<JobDetail />} />
      <Route path="/login" element={<Login />} />
      <Route path="/register" element={<Register />} />
      <Route path="/dashboard" element={<ProtectedRoute><Dashboard /></ProtectedRoute>} />
      <Route path="/notifications" element={<ProtectedRoute><Notifications /></ProtectedRoute>} />
      <Route path="/profile" element={<ProtectedRoute roles={['candidate']}><Profile /></ProtectedRoute>} />
      <Route path="/applications" element={<ProtectedRoute roles={['candidate']}><Applications /></ProtectedRoute>} />
      <Route path="/applications/:id" element={<ProtectedRoute roles={['candidate']}><ApplicationDetail /></ProtectedRoute>} />
      <Route path="/saved-jobs" element={<ProtectedRoute roles={['candidate']}><SavedJobs /></ProtectedRoute>} />
      <Route path="/my-jobs" element={<ProtectedRoute roles={['super_admin', 'recruiter']}><MyJobs /></ProtectedRoute>} />
      <Route path="/my-jobs/:id/edit" element={<ProtectedRoute roles={['super_admin', 'recruiter']}><EditJob /></ProtectedRoute>} />
      <Route path="/my-jobs/:id/versions" element={<ProtectedRoute roles={['super_admin', 'recruiter']}><JobVersions /></ProtectedRoute>} />
      <Route path="/jobs/new" element={<ProtectedRoute roles={['super_admin', 'recruiter']}><NewJob /></ProtectedRoute>} />
      <Route path="/received-applications" element={<ProtectedRoute roles={['super_admin', 'recruiter']}><ReceivedApplications /></ProtectedRoute>} />
      <Route path="/received-applications/:id" element={<ProtectedRoute roles={['super_admin', 'recruiter']}><ReceivedApplicationDetail /></ProtectedRoute>} />
      <Route path="/admin/users" element={<ProtectedRoute roles={['super_admin']}><AdminUsers /></ProtectedRoute>} />
    </Routes>
  )
}