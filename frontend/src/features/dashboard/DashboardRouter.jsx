import { Routes, Route } from 'react-router-dom'
import useAuthStore from '../../stores/authStore'
import { ROLES } from '../../config/constants'
import LecteurDashboard          from './LecteurDashboard'
import JournalisteDashboard      from './JournalisteDashboard'
import ReviewerDashboard         from './ReviewerDashboard'
import EditeurAssocieDashboard   from './EditeurAssocieDashboard'
import DirecteurDashboard        from './DirecteurDashboard'
import EditeurChefDashboard      from './EditeurChefDashboard'
import AdminDashboard            from './AdminDashboard'
import Spinner from '../../components/ui/Spinner'

const dashboardMap = {
  [ROLES.LECTEUR]:              LecteurDashboard,
  [ROLES.JOURNALISTE]:          JournalisteDashboard,
  [ROLES.REVIEWER]:             ReviewerDashboard,
  [ROLES.EDITEUR_ASSOCIE]:      EditeurAssocieDashboard,
  [ROLES.DIRECTEUR_COLLECTION]: DirecteurDashboard,
  [ROLES.EDITEUR_CHEF]:         EditeurChefDashboard,
  [ROLES.ADMIN]:                AdminDashboard,
  [ROLES.SUPER_ADMIN]:          AdminDashboard,
}

export default function DashboardRouter() {
  const { user } = useAuthStore()
  if (!user) return <div className="min-h-screen flex items-center justify-center"><Spinner size="lg" /></div>

  const Dashboard = dashboardMap[user.role] || LecteurDashboard

  return (
    <Routes>
      <Route path="/*" element={<Dashboard />} />
    </Routes>
  )
}