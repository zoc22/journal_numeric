import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { BookOpen, Heart, Bell, TrendingUp, Search } from 'lucide-react'
import Navbar from '../../components/shared/Navbar'
import Sidebar from '../../components/shared/Sidebar'
import { Card, CardBody } from '../../components/ui/Card'
import Spinner from '../../components/ui/Spinner'
import articleService from '../../services/articleService'
import useAuthStore from '../../stores/authStore'
import { formatDate, truncate } from '../../lib/utils'

const sidebarItems = [
  { to: '/dashboard',           label: 'Accueil',      icon: TrendingUp, end: true },
  { type: 'divider', key: 'd1' },
  { to: '/articles',            label: 'Explorer',     icon: Search },
  { to: '/dashboard/favoris',   label: 'Mes favoris',  icon: Heart },
  { to: '/dashboard/abonnements',label: 'Abonnements', icon: Bell },
  { to: '/profile',             label: 'Mon profil',   icon: BookOpen },
]

export default function LecteurDashboard() {
  const { user } = useAuthStore()
  const [articles, setArticles] = useState([])
  const [loading,  setLoading]  = useState(true)

  useEffect(() => {
    articleService.getPublic({ per_page: 6 })
      .then(r => setArticles(r.data?.data || []))
      .catch(() => {})
      .finally(() => setLoading(false))
  }, [])

  return (
    <div className="min-h-screen bg-ink-50">
      <Navbar />
      <Sidebar items={sidebarItems} title="Espace lecteur" />
      <main className="pt-14 lg:pl-60">
        <div className="p-4 sm:p-6 lg:p-8 max-w-5xl mx-auto">

          <div className="mb-8">
            <h1 className="font-display text-3xl font-bold text-ink-900">
              Bonjour, {user?.name?.split(' ')[0]} 👋
            </h1>
            <p className="text-ink-500 text-sm mt-1">Voici les dernières publications.</p>
          </div>

          {loading ? (
            <div className="flex justify-center py-16"><Spinner size="lg" /></div>
          ) : (
            <div className="grid sm:grid-cols-2 gap-5">
              {articles.map(article => (
                <Link key={article.id} to={`/articles/${article.slug}`}>
                  <Card hover>
                    {article.image_principale && (
                      <img src={article.image_principale} alt="" className="w-full h-40 object-cover rounded-t-2xl" />
                    )}
                    <CardBody>
                      <h3 className="font-display font-bold text-ink-900 mb-1 line-clamp-2">{article.titre}</h3>
                      <p className="text-sm text-ink-500 line-clamp-2">{truncate(article.resume, 100)}</p>
                      <p className="text-xs text-ink-400 mt-3">{formatDate(article.publie_le)}</p>
                    </CardBody>
                  </Card>
                </Link>
              ))}
            </div>
          )}

        </div>
      </main>
    </div>
  )
}