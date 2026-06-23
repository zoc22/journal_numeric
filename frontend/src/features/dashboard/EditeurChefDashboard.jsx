import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { Building, Users, FileText, TrendingUp, Settings, Shield, Globe } from 'lucide-react'
import Navbar from '../../components/shared/Navbar'
import Sidebar from '../../components/shared/Sidebar'
import { Card, CardBody } from '../../components/ui/Card'
import { StatusBadge } from '../../components/ui/Badge'
import Button from '../../components/ui/Button'
import Spinner from '../../components/ui/Spinner'
import workflowService from '../../services/workflowService'
import { timeAgo } from '../../lib/utils'

const sidebarItems = [
  { to: '/dashboard',            label: 'Tableau de bord',  icon: TrendingUp, end: true },
  { type: 'divider', key: 'd1' },
  { type: 'label', key: 'l1', label: 'Éditorial' },
  { to: '/dashboard/validate',   label: 'Validations finales', icon: Shield },
  { to: '/workflow',             label: 'Workflow',         icon: FileText },
  { type: 'divider', key: 'd2' },
  { type: 'label', key: 'l2', label: 'Maison' },
  { to: '/dashboard/team',       label: 'Équipe',           icon: Users },
  { to: '/dashboard/partenariats',label: 'Partenariats',    icon: Globe },
  { to: '/dashboard/settings',   label: 'Configuration',    icon: Settings },
  { to: '/profile', label: 'Mon profil', icon: Building },
]

export default function EditeurChefDashboard() {
  const navigate  = useNavigate()
  const [pipeline, setPipeline] = useState([])
  const [stats,    setStats]    = useState({})
  const [loading,  setLoading]  = useState(true)

  useEffect(() => {
    Promise.all([
      workflowService.getPipeline({ statut: 'valide_directeur' }),
      workflowService.getWorkflowStats(),
    ]).then(([pRes, sRes]) => {
      setPipeline(pRes.data?.data || [])
      setStats(sRes.data?.data || {})
    }).catch(() => {}).finally(() => setLoading(false))
  }, [])

  return (
    <div className="min-h-screen bg-ink-50">
      <Navbar />
      <Sidebar items={sidebarItems} title="Éditeur en Chef" />
      <main className="pt-14 lg:pl-60">
        <div className="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto">

          <div className="mb-8">
            <p className="font-mono text-xs text-purple-600 uppercase tracking-wider mb-1">Éditeur en Chef</p>
            <h1 className="font-display text-3xl font-bold text-ink-900">Tableau de bord central</h1>
          </div>

          {/* Global KPIs */}
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            {[
              { label: 'Articles ce mois', value: stats.articles_mois  || 0, color: 'text-brand' },
              { label: 'À valider finale', value: stats.a_valider_chef || 0, color: 'text-accent-rose' },
              { label: 'Membres actifs',   value: stats.membres        || 0, color: 'text-emerald-600' },
              { label: 'Publications',     value: stats.publie_total   || 0, color: 'text-purple-600' },
            ].map(({ label, value, color }) => (
              <Card key={label}><CardBody>
                <p className="text-sm text-ink-500 mb-1">{label}</p>
                <p className={`text-3xl font-display font-bold ${color}`}>{value}</p>
              </CardBody></Card>
            ))}
          </div>

          {/* Articles pending final validation */}
          <Card>
            <div className="px-6 py-4 border-b border-ink-100">
              <h2 className="font-display font-bold text-ink-900">Validations finales en attente</h2>
              <p className="text-sm text-ink-500 mt-0.5">Articles validés par le directeur de collection, en attente de votre approbation finale.</p>
            </div>
            {loading ? (
              <CardBody className="flex justify-center py-12"><Spinner /></CardBody>
            ) : pipeline.length === 0 ? (
              <CardBody className="text-center py-12">
                <Shield className="w-12 h-12 text-ink-200 mx-auto mb-3" />
                <p className="text-ink-500">Aucune validation finale en attente.</p>
              </CardBody>
            ) : (
              <div className="divide-y divide-ink-100">
                {pipeline.map(article => (
                  <div key={article.id} className="px-6 py-4 flex items-center gap-4 hover:bg-ink-50">
                    <div className="flex-1 min-w-0">
                      <h3 className="font-medium text-ink-900 truncate">{article.titre}</h3>
                      <p className="text-xs text-ink-400 mt-0.5">{article.auteur?.name} · {timeAgo(article.updated_at)}</p>
                    </div>
                    <StatusBadge status={article.statut} />
                    <Button size="sm" onClick={() => navigate(`/workflow?article=${article.id}`)}>
                      Valider
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