import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { Menu, X, Search, Bell, ChevronDown, LogOut, User, LayoutDashboard } from 'lucide-react'
import useAuthStore from '../../stores/authStore'
import authService from '../../services/authService'
import { getInitials } from '../../lib/utils'
import toast from 'react-hot-toast'

export default function Navbar() {
  const { isAuthenticated, user, logout } = useAuthStore()
  const [mobileOpen, setMobileOpen] = useState(false)
  const [userMenuOpen, setUserMenuOpen] = useState(false)
  const navigate = useNavigate()

  const handleLogout = async () => {
    try {
        await authService.logout()
    } catch (error) {
        // On logue l'erreur en console pour le dev, sans bloquer l'utilisateur
        console.error("Erreur lors de la déconnexion backend :", error)
    }
    
    logout() // Déconnexion du contexte frontend
    navigate('/')
    toast.success('Déconnecté avec succès')
    }

  return (
    <nav className="fixed inset-x-0 top-0 z-50 h-14 bg-white/90 backdrop-blur-xl border-b border-ink-200/70 flex items-center px-4 sm:px-6">
      {/* Mobile toggle */}
      <button
        onClick={() => setMobileOpen(!mobileOpen)}
        className="lg:hidden mr-2 p-2 rounded-lg hover:bg-ink-100 text-ink-600"
      >
        {mobileOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
      </button>

      {/* Logo */}
      <Link to="/" className="flex items-center gap-2 mr-6">
        <div className="w-8 h-8 rounded-lg bg-gradient-to-br from-brand to-accent-purple grid place-items-center text-white font-display font-bold text-sm">
          N
        </div>
        <span className="font-display text-lg font-bold tracking-tight hidden sm:block">
          Nexus<span className="text-brand">Press</span>
        </span>
      </Link>

      {/* Desktop nav */}
      <div className="hidden lg:flex items-center gap-1 flex-1">
        {[
          { to: '/',        label: 'Accueil' },
          { to: '/maisons', label: "Maisons d'édition" },
          { to: '/articles',label: 'Articles' },
        ].map(({ to, label }) => (
          <Link
            key={to}
            to={to}
            className="px-3 py-2 text-sm font-medium text-ink-600 hover:text-ink-900 rounded-md transition-colors"
          >
            {label}
          </Link>
        ))}
        {isAuthenticated && (
          <>
            <div className="w-px h-6 bg-ink-200 mx-2" />
            <Link
              to="/dashboard"
              className="px-3 py-2 text-sm font-medium text-ink-600 hover:text-ink-900 rounded-md flex items-center gap-1.5"
            >
              <LayoutDashboard className="w-3.5 h-3.5" />
              Espace Pro
            </Link>
          </>
        )}
      </div>

      {/* Right actions */}
      <div className="flex items-center gap-2 ml-auto">
        <button className="p-2 rounded-lg hover:bg-ink-100 text-ink-600">
          <Search className="w-4 h-4" />
        </button>

        {isAuthenticated ? (
          <>
            <button className="relative p-2 rounded-lg hover:bg-ink-100 text-ink-600">
              <Bell className="w-4 h-4" />
              <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-accent-rose rounded-full" />
            </button>
            <div className="relative">
              <button
                onClick={() => setUserMenuOpen(!userMenuOpen)}
                className="flex items-center gap-2 pl-2 pr-3 py-1.5 rounded-xl hover:bg-ink-100 transition-colors"
              >
                <div className="w-7 h-7 rounded-full bg-gradient-to-br from-brand to-accent-purple grid place-items-center text-white text-xs font-bold">
                  {getInitials(user?.name)}
                </div>
                <span className="hidden sm:block text-sm font-medium text-ink-800 max-w-[120px] truncate">
                  {user?.name}
                </span>
                <ChevronDown className="w-3.5 h-3.5 text-ink-400" />
              </button>

              {userMenuOpen && (
                <div className="absolute right-0 top-full mt-1 w-52 bg-white rounded-xl shadow-card border border-ink-100 py-1 z-50">
                  <Link
                    to="/profile"
                    onClick={() => setUserMenuOpen(false)}
                    className="flex items-center gap-2.5 px-4 py-2.5 text-sm text-ink-700 hover:bg-ink-50"
                  >
                    <User className="w-4 h-4" />
                    Mon profil
                  </Link>
                  <Link
                    to="/dashboard"
                    onClick={() => setUserMenuOpen(false)}
                    className="flex items-center gap-2.5 px-4 py-2.5 text-sm text-ink-700 hover:bg-ink-50"
                  >
                    <LayoutDashboard className="w-4 h-4" />
                    Tableau de bord
                  </Link>
                  <div className="border-t border-ink-100 my-1" />
                  <button
                    onClick={() => { setUserMenuOpen(false); handleLogout() }}
                    className="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-accent-rose hover:bg-red-50"
                  >
                    <LogOut className="w-4 h-4" />
                    Se déconnecter
                  </button>
                </div>
              )}
            </div>
          </>
        ) : (
          <>
            <Link
              to="/login"
              className="hidden sm:inline-flex px-3 py-1.5 text-sm font-medium text-ink-700 hover:text-ink-900"
            >
              Se connecter
            </Link>
            <Link
              to="/register"
              className="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-sm font-semibold text-white bg-ink-900 hover:bg-brand rounded-lg transition-colors"
            >
              S'inscrire
            </Link>
          </>
        )}
      </div>

      {/* Mobile menu */}
      {mobileOpen && (
        <div className="lg:hidden fixed inset-0 top-14 z-40 bg-white">
          <div className="p-4 space-y-1">
            {[
              { to: '/',         label: 'Accueil' },
              { to: '/maisons',  label: "Maisons d'édition" },
              { to: '/articles', label: 'Articles' },
            ].map(({ to, label }) => (
              <Link
                key={to}
                to={to}
                onClick={() => setMobileOpen(false)}
                className="block w-full text-left px-4 py-3 rounded-lg hover:bg-ink-50 font-medium text-ink-800"
              >
                {label}
              </Link>
            ))}
            {isAuthenticated ? (
              <>
                <Link
                  to="/dashboard"
                  onClick={() => setMobileOpen(false)}
                  className="block w-full text-left px-4 py-3 rounded-lg bg-brand text-white font-semibold mt-4"
                >
                  Espace Pro
                </Link>
                <button
                  onClick={() => { setMobileOpen(false); handleLogout() }}
                  className="block w-full text-left px-4 py-3 rounded-lg text-accent-rose font-medium"
                >
                  Se déconnecter
                </button>
              </>
            ) : (
              <div className="flex gap-2 pt-4">
                <Link to="/login"    onClick={() => setMobileOpen(false)} className="flex-1 text-center py-3 rounded-lg border border-ink-200 font-medium">Connexion</Link>
                <Link to="/register" onClick={() => setMobileOpen(false)} className="flex-1 text-center py-3 rounded-lg bg-ink-900 text-white font-semibold">S'inscrire</Link>
              </div>
            )}
          </div>
        </div>
      )}
    </nav>
  )
}