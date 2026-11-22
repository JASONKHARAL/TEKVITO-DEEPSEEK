import Navbar from './components/Navbar.jsx'
import AppRoutes from './routes/AppRoutes.jsx'
import { Toaster } from 'react-hot-toast'

export default function App() {
  return (
    <>
      <Navbar />
      <main>
        <AppRoutes />
      </main>
      <Toaster position="top-right" />   {/* ← ADD THIS */}
    </>
  )
}