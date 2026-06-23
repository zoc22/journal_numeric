import { useState, useEffect } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { PenSquare, FileText, Clock, CheckCircle, XCircle, TrendingUp, Plus, Eye } from 'lucide-react'
import Navbar from '../../components/shared/Navbar'
import Sidebar from '../../components/shared/Sidebar'
import { Card, CardBody } from '../../components/ui/Card'
import { StatusBadge } from '../../components/ui/Badge'
import Button from '../../components/ui/Button'
import Spinner from '../../components/ui/Spinner'
import articleService from '../../services/articleService'
import useAuthStore from '../../stores/authStore'
import { formatDate, timeAgo, truncate } from '../../lib/utils'
import { ARTICLE_STATUS } from '../../config/constants'

const sidebarItems = [
  { to: '/dashboard',        label: 'Vue d\'ensemble', icon: TrendingUp, end: true },
  { type: 'divider', key: 'd1' },
  { type: 'label', key: 'l1', label: 'Articles' },
  { to: '/editor',           label: 'Nouvel article',  icon: Plus },
  { to: '/dashboard/articles',label: 'Mes articles',   icon: FileText },
  { type: 'divider', key: 'd2' },
  { to: '/profile',          label: 'Mon profil',      icon: Eye },
]

function StatCard({ label, value, icon: Icon, color }) {
  return (
    <Card>
      <CardBody className="flex items-center gap-4">
        <div className={`w-12 h-12 rounded-xl grid place-items-center ${color}`}>
          <Icon className="w-5 h-5" />
        </div>
        <div>
          <p className="text-2xl font-display font-bold text-ink-900">{value}</p>
          <p className="text-sm text-ink-500">{label}</p>
        </div>
      </CardBody>
    </Card>
  )
}

export default function JournalisteDashboard() {
  const { user } = useAuthStore()
  const navigate  = useNavigate()
  const [articles, setArticles] = useState([])
  const [loading,  setLoading]  = useState(true)

  useEffect(() => {
    articleService.getMyArticles({ per_page: 10 })
      .then(r => setArticles(r.data?.data || []))
      .catch(() => {})
      .finally(() => setLoading(false))
  }, [])

  const stats = {
    total:     articles.length,
    publie:    articles.filter(a => a.statut === ARTICLE_STATUS.PUBLIE).length,
    en_review: articles.filter(a => a.statut === ARTICLE_STATUS.EN_REVIEW).length,
    brouillon: articles.filter(a => a.statut === ARTICLE_STATUS.BROUILLON).length,
  }

  const recent = articles.slice(0, 5)

  return (
    <div className="min-h-screen bg-ink-50">
      <Navbar />
      <Sidebar items={sidebarItems} title="Journaliste" />
      <main className="pt-14 lg:pl-60">
        <div className="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto">

          {/* Header */}
          <div className="mb-8 flex flex-col sm:flex-row sm:items-center gap-4 justify-between">
            <div>
              <p className="font-mono text-xs text-brand uppercase tracking-wider mb-1">Espace journaliste</p>
              <h1 className="font-display text-3xl font-bold text-ink-900">Bonjour, {user?.name?.split(' ')[0]} 👋</h1>
              <p className="text-ink-500 text-sm mt-1">{formatDate(new Date(), 'EEEE dd MMMM yyyy')}</p>
            </div>
            <Button onClick={() => navigate('/editor')} leftIcon={<PenSquare className="w-4 h-4" />} size="lg">
              Nouvel article
            </Button>
          </div>

          {/* Stats */}
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <StatCard label="Total articles"   value={stats.total}     icon={FileText}    color="bg-brand-50 text-brand" />
            <StatCard label="Publiés"           value={stats.publie}    icon={CheckCircle} color="bg-emerald-50 text-emerald-600" />
            <StatCard label="En relecture"      value={stats.en_review} icon={Clock}       color="bg-amber-50 text-amber-600" />
            <StatCard label="Brouillons"        value={stats.brouillon} icon={PenSquare}   color="bg-ink-100 text-ink-600" />
          </div>

          {/* Recent articles */}
          <Card>
            <div className="px-6 py-4 border-b border-ink-100 flex items-center justify-between">
              <h2 className="font-display font-bold text-ink-900">Mes articles récents</h2>
              <Link to="/dashboard/articles" className="text-sm text-brand hover:underline">Tout voir</Link>
            </div>
            {loading ? (
              <CardBody className="flex justify-center py-12"><Spinner /></CardBody>
            ) : recent.length === 0 ? (
              <CardBody className="text-center py-16">
                <FileText className="w-12 h-12 text-ink-200 mx-auto mb-3" />
                <h3 className="font-semibold text-ink-700 mb-1">Aucun article</h3>
                <p className="text-sm text-ink-400 mb-4">Commencez à rédiger votre premier article.</p>
                <Button onClick={() => navigate('/editor')} leftIcon={<Plus className="w-4 h-4" />}>
                  Créer un article
                </Button>
              </CardBody>
            ) : (
              <div className="divide-y divide-ink-100">
                {recent.map(article => (
                  <div key={article.id} className="px-6 py-4 flex items-center gap-4 hover:bg-ink-50">
                    <div className="flex-1 min-w-0">
                      <h3 className="font-medium text-ink-900 truncate">{article.titre}</h3>
                      <p className="text-xs text-ink-400 mt-0.5">{timeAgo(article.updated_at)}</p>
                    </div>
                    <StatusBadge status={article.statut} />
                    <Link
                      to={`/editor/${article.id}`}
                      className="text-xs font-medium text-brand hover:underline shrink-0"
                    >
                      Modifier
                    </Link>
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