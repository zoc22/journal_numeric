import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { Search, Filter, Clock } from 'lucide-react'
import Navbar from '../../components/shared/Navbar'
import { StatusBadge } from '../../components/ui/Badge'
import Spinner from '../../components/ui/Spinner'
import articleService from '../../services/articleService'
import { formatDate, truncate } from '../../lib/utils'

export default function ArticlesPage() {
  const [articles,  setArticles]  = useState([])
  const [loading,   setLoading]   = useState(true)
  const [search,    setSearch]    = useState('')
  const [page,      setPage]      = useState(1)
  const [meta,      setMeta]      = useState({})

  useEffect(() => {
    setLoading(true)
    articleService.getPublic({ search, page, per_page: 9 })
      .then(r => {
        setArticles(r.data?.data || [])
        setMeta(r.data?.meta   || {})
      })
      .catch(() => {})
      .finally(() => setLoading(false))
  }, [search, page])

  return (
    <div className="min-h-screen bg-ink-50">
      <Navbar />
      <main className="pt-14">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-12">

          {/* Header */}
          <div className="mb-8">
            <div className="font-mono text-xs text-brand uppercase tracking-wider mb-2">Articles</div>
            <h1 className="font-display text-4xl font-bold text-ink-900 mb-6">Toutes les publications</h1>
            <div className="flex gap-3">
              <div className="relative flex-1 max-w-md">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400" />
                <input
                  type="text"
                  placeholder="Rechercher un article…"
                  value={search}
                  onChange={e => { setSearch(e.target.value); setPage(1) }}
                  className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-ink-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"
                />
              </div>
            </div>
          </div>

          {/* Grid */}
          {loading ? (
            <div className="flex justify-center py-20"><Spinner size="xl" /></div>
          ) : articles.length === 0 ? (
            <div className="text-center py-20">
              <p className="text-ink-500">Aucun article trouvé.</p>
            </div>
          ) : (
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
              {articles.map(article => (
                <Link key={article.id} to={`/articles/${article.slug}`} className="group">
                  <div className="bg-white rounded-2xl border border-ink-100 shadow-soft hover:shadow-card transition-all overflow-hidden h-full flex flex-col">
                    {article.image_principale ? (
                      <img src={article.image_principale} alt="" className="w-full h-48 object-cover" />
                    ) : (
                      <div className="w-full h-48 bg-gradient-to-br from-brand-50 to-ink-100" />
                    )}
                    <div className="p-5 flex flex-col flex-1">
                      <StatusBadge status={article.statut} />
                      <h3 className="font-display font-bold text-ink-900 mt-3 mb-2 group-hover:text-brand transition-colors line-clamp-2 flex-1">
                        {article.titre}
                      </h3>
                      <p className="text-sm text-ink-500 line-clamp-2 mb-4">{truncate(article.resume, 120)}</p>
                      <div className="flex items-center gap-2 text-xs text-ink-400">
                        <span>{formatDate(article.publie_le)}</span>
                        <span>·</span>
                        <span className="flex items-center gap-1">
                          <Clock className="w-3 h-3" />{article.temps_lecture || 3} min
                        </span>
                      </div>
                    </div>
                  </div>
                </Link>
              ))}
            </div>
          )}

          {/* Pagination */}
          {meta.last_page > 1 && (
            <div className="flex items-center justify-center gap-2 mt-12">
              {Array.from({ length: meta.last_page }, (_, i) => i + 1).map(p => (
                <button
                  key={p}
                  onClick={() => setPage(p)}
                  className={`w-9 h-9 rounded-lg text-sm font-medium transition-colors ${
                    page === p ? 'bg-brand text-white' : 'bg-white border border-ink-200 text-ink-600 hover:border-brand hover:text-brand'
                  }`}
                >
                  {p}
                </button>
              ))}
            </div>
          )}

        </div>
      </main>
    </div>
  )
}