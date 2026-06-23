import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { BookOpen, CheckCircle, Clock, BarChart2, Settings } from 'lucide-react'
import Navbar from '../../components/shared/Navbar'
import Sidebar from '../../components/shared/Sidebar'
import { Card, CardBody } from '../../components/ui/Card'
import { StatusBadge } from '../../components/ui/Badge'
import Button from '../../components/ui/Button'
import Spinner from '../../components/ui/Spinner'
import workflowService from '../../services/workflowService'
import { timeAgo } from '../../lib/utils'

const sidebarItems = [
  { to: '/dashboard',          label: 'Vue d\'ensemble', icon: BarChart2, end: true },
  { type: 'divider', key: 'd' },
  { to: '/dashboard/pipeline', label: 'Pipeline',       icon: Clock },
  { to: '/dashboard/stats',    label: 'Statistiques',   icon: BarChart2 },
  { to: '/profile',            label: 'Mon profil',     icon: Settings },
]

export default function DirecteurDashboard() {
  const navigate  = useNavigate()
  const [pipeline, setPipeline] = useState([])
  const [loading,  setLoading]  = useState(true)
  const [stats,    setStats]    = useState({})

  useEffect(() => {
    Promise.all([
      workflowService.getPipeline({ statut: 'valide_editeur' }),
      workflowService.getWorkflowStats(),
    ]).then(([pRes, sRes]) => {
      setPipeline(pRes.data?.data || [])
      setStats(sRes.data?.data   || {})
    }).catch(() => {}).finally(() => setLoading(false))
  }, [])

  return (
    <div className="min-h-screen bg-ink-50">
      <Navbar />
      <Sidebar items={sidebarItems} title="Directeur Collection" />
      <main className="pt-14 lg:pl-60">
        <div className="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto">

          <div className="mb-8">
            <p className="font-mono text-xs text-teal-600 uppercase tracking-wider mb-1">Directeur de Collection</p>
            <h1 className="font-display text-3xl font-bold text-ink-900">Validation thématique</h1>
            <p className="text-ink-500 text-sm mt-1">
              {pipeline.length} article{pipeline.length !== 1 ? 's' : ''} validé{pipeline.length !== 1 ? 's' : ''} par l'éditeur, en attente de votre validation thématique.
            </p>
          </div>

          <Card>
            {loading ? (
              <CardBody className="flex justify-center py-16"><Spinner /></CardBody>
            ) : pipeline.length === 0 ? (
              <CardBody className="text-center py-16">
                <CheckCircle className="w-12 h-12 text-emerald-200 mx-auto mb-3" />
                <p className="text-ink-500">Aucun article en attente de validation thématique.</p>
              </CardBody>
            ) : (
              <div className="divide-y divide-ink-100">
                {pipeline.map(article => (
                  <div key={article.id} className="px-6 py-5 flex items-center gap-4 hover:bg-ink-50">
                    <div className="flex-1 min-w-0">
                      <h3 className="font-semibold text-ink-900 truncate">{article.titre}</h3>
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