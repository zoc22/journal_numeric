import { Suspense, lazy } from 'react'
import { Routes, Route, Navigate } from 'react-router-dom'
import { AuthProvider } from './context/AuthContext'
import ProtectedRoute from './components/shared/ProtectedRoute'
import Spinner from './components/ui/Spinner'
import { BrowserRouter } from 'react-router-dom' 

// Lazy load features
const HomePage        = lazy(() => import('./features/public-site/HomePage'))
const ArticlesPage    = lazy(() => import('./features/public-site/ArticlesPage'))
const ArticleDetail   = lazy(() => import('./features/public-site/ArticleDetailPage'))
const MaisonsPage     = lazy(() => import('./features/public-site/MaisonsPage'))
const LoginPage       = lazy(() => import('./features/auth/LoginPage'))
const RegisterPage    = lazy(() => import('./features/auth/RegisterPage'))
const ForgotPassword  = lazy(() => import('./features/auth/ForgotPasswordPage'))
const DashboardRouter = lazy(() => import('./features/dashboard/DashboardRouter'))
const ArticleEditor   = lazy(() => import('./features/editor/ArticleEditor'))
const ProfilePage     = lazy(() => import('./features/profile/ProfilePage'))
const WorkflowBoard   = lazy(() => import('./features/workflow/WorkflowBoard'))

const PageLoader = () => (
  <div className="min-h-screen flex items-center justify-center bg-ink-50">
    <Spinner size="lg" />
  </div>
)

export default function App() {
  return (
    <BrowserRouter>
    <AuthProvider>
      <Suspense fallback={<PageLoader />}>
        <Routes>
          {/* Public routes */}
          <Route path="/"          element={<HomePage />} />
          <Route path="/articles"  element={<ArticlesPage />} />
          <Route path="/articles/:slug" element={<ArticleDetail />} />
          <Route path="/maisons"   element={<MaisonsPage />} />

          {/* Auth routes */}
          <Route path="/login"           element={<LoginPage />} />
          <Route path="/register"        element={<RegisterPage />} />
          <Route path="/forgot-password" element={<ForgotPassword />} />

          {/* Protected routes */}
          <Route element={<ProtectedRoute />}>
            <Route path="/dashboard/*" element={<DashboardRouter />} />
            <Route path="/editor"        element={<ArticleEditor />} />
            <Route path="/editor/:id"    element={<ArticleEditor />} />
            <Route path="/workflow"      element={<WorkflowBoard />} />
            <Route path="/profile"       element={<ProfilePage />} />
          </Route>

          {/* Fallback */}
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </Suspense>
    </AuthProvider>
    </BrowserRouter>
  )
}