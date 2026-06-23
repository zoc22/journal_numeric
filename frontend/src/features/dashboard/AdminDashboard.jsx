import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { Building, Users, FileText, Shield, AlertTriangle, CheckCircle, XCircle } from 'lucide-react'
import Navbar from '../../components/shared/Navbar'
import Sidebar from '../../components/shared/Sidebar'
import { Card, CardBody } from '../../components/ui/Card'
import Button from '../../components/ui/Button'
import Spinner from '../../components/ui/Spinner'
import maisonService from '../../services/maisonService'
import userService from '../../services/userService'
import toast from 'react-hot-toast'

const sidebarItems = [
  { to: '/dashboard',           label: 'Vue globale',    icon: Shield, end: true },
  { type: 'divider', key: 'd1' },
  { type: 'label', key: 'l1', label: 'Administration' },
  { to: '/dashboard/maisons',   label: 'Maisons d\'édition', icon: Building },
  { to: '/dashboard/users',     label: 'Utilisateurs',   icon: Users },
  { to: '/dashboard/articles',  label: 'Tous les articles', icon: FileText },
  { to: '/dashboard/logs',      label: 'Journaux d\'audit', icon: AlertTriangle },
]

export default function AdminDashboard() {
  const [maisons,  setMaisons]  = useState([])
  const [users,    setUsers]    = useState([])
  const [loading,  setLoading]  = useState(true)

  useEffect(() => {
    Promise.all([
      maisonService.getAll({ statut: 'en_attente', per_page: 5 }),
      userService.getAll({ per_page: 5 }),
    ]).then(([mRes, uRes]) => {
      setMaisons(mRes.data?.data || [])
      setUsers(uRes.data?.data   || [])
    }).catch(() => {}).finally(() => setLoading(false))
  }, [])

  const handleValidateMaison = async (id) => {
    try {
      await maisonService.validate(id)
      setMaisons(m => m.filter(x => x.id !== id))
      toast.success('Maison d\'édition validée !')
    } catch {
      toast.error('Erreur lors de la validation')
    }
  }

  return (
    <div className="min-h-screen bg-ink-50">
      <Navbar />
      <Sidebar items={sidebarItems} title="Administration" />
      <main className="pt-14 lg:pl-60">
        <div className="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto">

          <div className="mb-8">
            <p className="font-mono text-xs text-accent-rose uppercase tracking-wider mb-1">Super Admin</p>
            <h1 className="font-display text-3xl font-bold text-ink-900">Administration de la plateforme</h1>
          </div>

          <div className="grid lg:grid-cols-2 gap-6">
            {/* Pending maisons */}
            <Card>
              <div className="px-6 py-4 border-b border-ink-100 flex items-center justify-between">
                <h2 className="font-display font-bold text-ink-900">Maisons en attente de validation</h2>
                <span className="bg-accent-rose text-white text-xs font-bold px-2 py-0.5 rounded-full">
                  {maisons.length}
                </span>
              </div>
              {loading ? (
                <CardBody className="flex justify-center py-8"><Spinner /></CardBody>
              ) : maisons.length === 0 ? (
                <CardBody className="text-center py-8">
                  <CheckCircle className="w-10 h-10 text-emerald-200 mx-auto mb-2" />
                  <p className="text-ink-500 text-sm">Aucune maison en attente</p>
                </CardBody>
              ) : (
                <div className="divide-y divide-ink-100">
                  {maisons.map(m => (
                    <div key={m.id} className="px-6 py-4">
                      <div className="flex items-start justify-between gap-3">
                        <div>
                          <h3 className="font-semibold text-ink-900">{m.nom}</h3>
                          <p className="text-xs text-ink-400">{m.email_contact}</p>
                        </div>
                        <div className="flex gap-2 shrink-0">
                          <button
                            onClick={() => handleValidateMaison(m.id)}
                            className="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100"
                            title="Valider"
                          >
                            <CheckCircle className="w-4 h-4" />
                          </button>
                          <button
                            className="p-1.5 rounded-lg bg-red-50 text-accent-rose hover:bg-red-100"
                            title="Rejeter"
                          >
                            <XCircle className="w-4 h-4" />
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </Card>

            {/* Recent users */}
            <Card>
              <div className="px-6 py-4 border-b border-ink-100 flex items-center justify-between">
                <h2 className="font-display font-bold text-ink-900">Derniers utilisateurs</h2>
                <Link to="/dashboard/users" className="text-sm text-brand hover:underline">Gérer</Link>
              </div>
              {loading ? (
                <CardBody className="flex justify-center py-8"><Spinner /></CardBody>
              ) : (
                <div className="divide-y divide-ink-100">
                  {users.map(u => (
                    <div key={u.id} className="px-6 py-3.5 flex items-center gap-3">
                      <div className="w-8 h-8 rounded-full bg-gradient-to-br from-brand to-accent-purple grid place-items-center text-white text-xs font-bold shrink-0">
                        {u.name?.[0]?.toUpperCase()}
                      </div>
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium text-ink-900 truncate">{u.name}</p>
                        <p className="text-xs text-ink-400 truncate">{u.email}</p>
                      </div>
                      <span className="text-xs font-medium text-ink-500 bg-ink-100 px-2 py-0.5 rounded-full capitalize">
                        {u.role}
                      </span>
                    </div>
                  ))}
                </div>
              )}
            </Card>
          </div>

        </div>
      </main>
    </div>
  )
}