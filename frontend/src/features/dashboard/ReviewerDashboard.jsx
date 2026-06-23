import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { ClipboardCheck, Clock, CheckCircle, AlertCircle, FileText } from 'lucide-react'
import Navbar from '../../components/shared/Navbar'
import Sidebar from '../../components/shared/Sidebar'
import { Card, CardBody } from '../../components/ui/Card'
import { StatusBadge } from '../../components/ui/Badge'
import Button from '../../components/ui/Button'
import Spinner from '../../components/ui/Spinner'
import workflowService from '../../services/workflowService'
import useAuthStore from '../../stores/authStore'
import { formatDate, timeAgo } from '../../lib/utils'

const sidebarItems = [
  { to: '/dashboard',          label: 'Tableau de bord', icon: ClipboardCheck, end: true },
  { type: 'divider', key: 'd' },
  { to: '/dashboard/pending',  label: 'Articles à relire', icon: Clock },
  { to: '/dashboard/reviewed', label: 'Revues effectuées', icon: CheckCircle },
  { to: '/profile',            label: 'Mon profil', icon: FileText },
]

export default function ReviewerDashboard() {
  const { user }  = useAuthStore()
  const [pending,  setPending]  = useState([])
  const [loading,  setLoading]  = useState(true)

  useEffect(() => {
    workflowService.getPendingReviews()
      .then(r => setPending(r.data?.data || []))
      .catch(() => {})
      .finally(() => setLoading(false))
  }, [])

  return (
    <div className="min-h-screen bg-ink-50">
      <Navbar />
      <Sidebar items={sidebarItems} title="Reviewer" />
      <main className="pt-14 lg:pl-60">
        <div className="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto">

          <div className="mb-8">
            <p className="font-mono text-xs text-amber-600 uppercase tracking-wider mb-1">Espace reviewer</p>
            <h1 className="font-display text-3xl font-bold text-ink-900">
              Articles en attente de relecture
            </h1>
            <p className="text-ink-500 text-sm mt-1">
              {pending.length} article{pending.length !== 1 ? 's' : ''} nécessitent votre attention.
            </p>
          </div>

          {/* Pending reviews */}
          <Card>
            {loading ? (
              <CardBody className="flex justify-center py-16"><Spinner /></CardBody>
            ) : pending.length === 0 ? (
              <CardBody className="text-center py-16">
                <CheckCircle className="w-12 h-12 text-emerald-200 mx-auto mb-3" />
                <h3 className="font-semibold text-ink-700 mb-1">Aucun article en attente</h3>
                <p className="text-sm text-ink-400">Vous avez terminé toutes vos relectures.</p>
              </CardBody>
            ) : (
              <div className="divide-y divide-ink-100">
                {pending.map(article => (
                  <div key={article.id} className="px-6 py-5 hover:bg-ink-50">
                    <div className="flex items-start justify-between gap-4">
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 mb-1">
                          <StatusBadge status={article.statut} />
                          <span className="text-xs text-ink-400">· Soumis {timeAgo(article.created_at)}</span>
                        </div>
                        <h3 className="font-semibold text-ink-900 mb-1">{article.titre}</h3>
                        <p className="text-sm text-ink-500">
                          Par {article.auteur?.name} — {article.maison?.nom}
                        </p>
                      </div>
                      <Link to={`/workflow?article=${article.id}`}>
                        <Button size="sm" variant="outline">
                          Examiner
                        </Button>
                      </Link>
                    </div>
                    <div className="flex gap-4 mt-3 text-xs text-ink-400">
                      <span className="flex items-center gap-1"><Clock className="w-3 h-3" />{article.temps_lecture || 5} min</span>
                      <span>{formatDate(article.updated_at)}</span>
                    </div>
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