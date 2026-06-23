import { Navigate, Outlet, useLocation } from 'react-router-dom'
import useAuthStore from '../../stores/authStore'

export default function ProtectedRoute({ requiredRole, requiredPermission }) {
  const { isAuthenticated, user } = useAuthStore()
  const location = useLocation()

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />
  }

  if (requiredRole && user?.role !== requiredRole) {
    return <Navigate to="/dashboard" replace />
  }

  if (requiredPermission && !user?.permissions?.includes(requiredPermission)) {
    return <Navigate to="/dashboard" replace />
  }

  return <Outlet />
}