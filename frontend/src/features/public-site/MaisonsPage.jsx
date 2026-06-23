import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import { Search, Users, FileText, Building } from 'lucide-react'
import Navbar from '../../components/shared/Navbar'
import Spinner from '../../components/ui/Spinner'
import maisonService from '../../services/maisonService'

export default function MaisonsPage() {
  const [maisons,  setMaisons]  = useState([])
  const [loading,  setLoading]  = useState(true)
  const [search,   setSearch]   = useState('')

  useEffect(() => {
    maisonService.getPublic({ search })
      .then(r => setMaisons(r.data?.data || []))
      .catch(() => {})
      .finally(() => setLoading(false))
  }, [search])

  return (
    <div className="min-h-screen bg-ink-50">
      <Navbar />
      <main className="pt-14">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 py-12">

          <div className="mb-8">
            <div className="font-mono text-xs text-brand uppercase tracking-wider mb-2">Annuaire</div>
            <h1 className="font-display text-4xl font-bold text-ink-900 mb-6">Maisons d'édition</h1>
            <div className="relative max-w-md">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-400" />
              <input
                type="text"
                placeholder="Rechercher une maison…"
                value={search}
                onChange={e => setSearch(e.target.value)}
                className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-ink-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"
              />
            </div>
          </div>

          {loading ? (
            <div className="flex justify-center py-20"><Spinner size="xl" /></div>
          ) : (
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
              {maisons.map(m => (
                <div key={m.id} className="bg-white rounded-2xl border border-ink-100 shadow-soft hover:shadow-card transition-all p-6">
                  <div className="flex items-start gap-4 mb-4">
                    {m.logo_url ? (
                      <img src={m.logo_url} alt="" className="w-12 h-12 rounded-xl object-cover" />
                    ) : (
                      <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-brand to-accent-purple grid place-items-center text-white font-display font-bold text-lg">
                        {m.nom?.[0]}
                      </div>
                    )}
                    <div className="flex-1 min-w-0">
                      <h2 className="font-display font-bold text-ink-900 truncate">{m.nom}</h2>
                      <p className="text-xs text-brand font-mono">{m.slug}</p>
                    </div>
                  </div>
                  <p className="text-sm text-ink-500 line-clamp-3 mb-4">{m.description}</p>
                  <div className="flex items-center gap-4 text-xs text-ink-400 mb-4">
                    <span className="flex items-center gap-1"><Users className="w-3 h-3" />{m.membres_count || 0} membres</span>
                    <span className="flex items-center gap-1"><FileText className="w-3 h-3" />{m.articles_count || 0} articles</span>
                  </div>
                  <Link
                    to={`/maisons/${m.slug}`}
                    className="flex items-center justify-center w-full py-2 rounded-xl border border-brand text-brand text-sm font-medium hover:bg-brand hover:text-white transition-colors"
                  >
                    Voir la maison
                  </Link>
                </div>
              ))}
            </div>
          )}

          {maisons.length === 0 && !loading && (
            <div className="text-center py-20">
              <Building className="w-16 h-16 text-ink-200 mx-auto mb-4" />
              <p className="text-ink-500">Aucune maison d'édition trouvée.</p>
            </div>
          )}

        </div>
      </main>
    </div>
  )
}