import { useState, useEffect } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { Users, FileText, ClipboardList, TrendingUp, UserPlus, Settings } from 'lucide-react'
import Navbar from '../../components/shared/Navbar'
import Sidebar from '../../components/shared/Sidebar'
import { Card, CardBody } from '../../components/ui/Card'
import { StatusBadge } from '../../components/ui/Badge'
import Button from '../../components/ui/Button'
import Spinner from '../../components/ui/Spinner'
import workflowService from '../../services/workflowService'
import useAuthStore from '../../stores/authStore'
import { timeAgo } from '../../lib/utils'

const sidebarItems = [
  { to: '/dashboard',           label: 'Vue d\'ensemble', icon: TrendingUp, end: true },
  { type: 'divider', key: 'd1' },
  { type: 'label', key: 'l1', label: 'Workflow' },
  { to: '/dashboard/pipeline',  label: 'Pipeline éditorial', icon: ClipboardList },
  { to: '/workflow',            label: 'Assigner reviewers', icon: Users },
  { type: 'divider', key: 'd2' },
  { type: 'label', key: 'l2', label: 'Équipe' },
  { to: '/dashboard/team',      label: 'Gérer l\'équipe', icon: UserPlus },
  { to: '/dashboard/settings',  label: 'Paramètres maison', icon: Settings },
  { to: '/profile',             label: 'Mon profil', icon: FileText },
]

export default function EditeurAssocieDashboard() {
  const { user }   = useAuthStore()
  const navigate   = useNavigate()
  const [pipeline, setPipeline] = useState([])
  const [loading,  setLoading]  = useState(true)
  const [stats,    setStats]    = useState({})

  useEffect(() => {
    Promise.all([
      workflowService.getPipeline(),
      workflowService.getWorkflowStats(),
    ]).then(([pRes, sRes]) => {
      setPipeline(pRes.data?.data || [])
      setStats(sRes.data?.data || {})
    }).catch(() => {}).finally(() => setLoading(false))
  }, [])

  return (
    <div className="min-h-screen bg-ink-50">
      <Navbar />
      <Sidebar items={sidebarItems} title="Éditeur associé" />
      <main className="pt-14 lg:pl-60">
        <div className="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto">

          <div className="mb-8">
            <p className="font-mono text-xs text-violet-600 uppercase tracking-wider mb-1">Espace éditeur associé</p>
            <h1 className="font-display text-3xl font-bold text-ink-900">Pipeline éditorial</h1>
          </div>

          {/* Stats row */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
            {[
              { label: 'En attente',   value: stats.en_attente   || 0, color: 'bg-amber-50 text-amber-700' },
              { label: 'En relecture', value: stats.en_review    || 0, color: 'bg-blue-50 text-blue-700' },
              { label: 'À valider',    value: stats.a_valider    || 0, color: 'bg-violet-50 text-violet-700' },
              { label: 'Publiés ce mois', value: stats.publie_mois || 0, color: 'bg-emerald-50 text-emerald-700' },
            ].map(({ label, value, color }) => (
              <Card key={label}>
                <CardBody>
                  <p className="text-sm text-ink-500 mb-1">{label}</p>
                  <p className={`text-3xl font-display font-bold ${color.split(' ')[1]}`}>{value}</p>
                </CardBody>
              </Card>
            ))}
          </div>

          {/* Pipeline */}
          <Card>
            <div className="px-6 py-4 border-b border-ink-100 flex items-center justify-between">
              <h2 className="font-display font-bold text-ink-900">Articles en cours de traitement</h2>
            </div>
            {loading ? (
              <CardBody className="flex justify-center py-12"><Spinner /></CardBody>
            ) : pipeline.length === 0 ? (
              <CardBody className="text-center py-16">
                <ClipboardList className="w-12 h-12 text-ink-200 mx-auto mb-3" />
                <h3 className="font-semibold text-ink-700 mb-1">Pipeline vide</h3>
                <p className="text-sm text-ink-400">Aucun article à traiter pour le moment.</p>
              </CardBody>
            ) : (
              <div className="divide-y divide-ink-100">
                {pipeline.map(article => (
                  <div key={article.id} className="px-6 py-4 flex items-center gap-4 hover:bg-ink-50">
                    <div className="flex-1 min-w-0">
                      <h3 className="font-medium text-ink-900 truncate">{article.titre}</h3>
                      <p className="text-xs text-ink-400 mt-0.5">
                        {article.auteur?.name} · {timeAgo(article.updated_at)}
                      </p>
                    </div>
                    <StatusBadge status={article.statut} />
                    <Button size="sm" variant="outline" onClick={() => navigate(`/workflow?article=${article.id}`)}>
                      Traiter
                    </Button>
                  </div>
                ))}
              </div>
            )}
          </Card>

        </div>
      </main>
    </div>
  )
}