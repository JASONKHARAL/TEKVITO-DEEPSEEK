import { useEffect, useState } from 'react'
import { useAuth } from '../context/AuthContext.jsx'
import { saveJob, unsaveJob, isJobSaved } from '../api/savedJobs.js'
import toast from 'react-hot-toast'

export default function SaveJobButton({ jobId }) {
  const { user } = useAuth()
  const [saved, setSaved] = useState(false)
  const [busy, setBusy] = useState(false)
  const isCandidate = user?.roles?.includes('candidate')

  useEffect(() => {
    if (!isCandidate) return
    isJobSaved(jobId)
      .then((r) => setSaved(!!r.data.saved))
      .catch(() => {})
  }, [jobId, isCandidate])

  if (!isCandidate) return null

  const toggle = async () => {
    setBusy(true)
    try {
      if (saved) {
        await unsaveJob(jobId)
        setSaved(false)
        toast.success('Job removed from saved')
      } else {
        await saveJob(jobId)
        setSaved(true)
        toast.success('Job saved!')
      }
    } catch (e) {
      toast.error(e?.response?.data?.error || 'Failed to save job')
    } finally {
      setBusy(false)
    }
  }

  return (
    <button
      onClick={toggle}
      className={saved ? 'btn btn-secondary' : 'btn btn-primary'}
      disabled={busy}
    >
      {busy ? '…' : saved ? '★ Saved' : '☆ Save Job'}
    </button>
  )
}