import { useState, useEffect } from 'react'
import { useParams, Link, useNavigate } from 'react-router-dom'
import { ArrowLeft, Clock, Eye, Heart, Share2, BookmarkPlus } from 'lucide-react'
import Navbar from '../../components/shared/Navbar'
import Spinner from '../../components/ui/Spinner'
import { StatusBadge } from '../../components/ui/Badge'
import articleService from '../../services/articleService'
import { formatDate, formatNumber } from '../../lib/utils'

export default function ArticleDetailPage() {
  const { slug }  = useParams()
  const navigate  = useNavigate()
  const [article, setArticle] = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    articleService.getPublicBySlug(slug)
      .then(r => setArticle(r.data?.data || r.data))
      .catch(() => navigate('/articles', { replace: true }))
      .finally(() => setLoading(false))
  }, [slug]) // eslint-disable-line

  if (loading) return <div className="min-h-screen flex items-center justify-center"><Spinner size="xl" /></div>
  if (!article) return null

  return (
    <div className="min-h-screen bg-ink-50">
      <Navbar />
      <main className="pt-14">

        {/* Hero */}
        {article.image_principale && (
          <div className="w-full h-64 sm:h-80 lg:h-96 overflow-hidden">
            <img src={article.image_principale} alt="" className="w-full h-full object-cover" />
          </div>
        )}

        <div className="max-w-3xl mx-auto px-4 sm:px-6 py-10">

          {/* Breadcrumb */}
          <Link to="/articles" className="flex items-center gap-1.5 text-sm text-ink-500 hover:text-ink-900 mb-6">
            <ArrowLeft className="w-4 h-4" /> Retour aux articles
          </Link>

          {/* Meta */}
          <div className="flex flex-wrap items-center gap-2 mb-4">
            <StatusBadge status={article.statut} />
            {article.maison && (
              <span className="text-xs text-ink-500 bg-ink-100 px-2 py-0.5 rounded-full">
                {article.maison.nom}
              </span>
            )}
          </div>

          {/* Title */}
          <h1 className="font-display text-3xl sm:text-4xl lg:text-5xl font-bold text-ink-900 leading-tight mb-4">
            {article.titre}
          </h1>

          {/* Resume */}
          {article.resume && (
            <p className="text-lg text-ink-600 leading-relaxed mb-6 font-light">
              {article.resume}
            </p>
          )}

          {/* Author bar */}
          <div className="flex items-center justify-between py-4 border-y border-ink-100 mb-8">
            <div className="flex items-center gap-3">
              <div className="w-9 h-9 rounded-full bg-gradient-to-br from-brand to-accent-purple grid place-items-center text-white text-sm font-bold">
                {article.auteur?.name?.[0]}
              </div>
              <div>
                <p className="text-sm font-semibold text-ink-900">{article.auteur?.name}</p>
                <p className="text-xs text-ink-400">{formatDate(article.publie_le)}</p>
              </div>
            </div>
            <div className="flex items-center gap-4 text-xs text-ink-400">
              <span className="flex items-center gap-1"><Clock className="w-3.5 h-3.5" />{article.temps_lecture || 5} min</span>
              <span className="flex items-center gap-1"><Eye className="w-3.5 h-3.5" />{formatNumber(article.nb_vues || 0)}</span>
              <button className="flex items-center gap-1 hover:text-accent-rose transition-colors">
                <Heart className="w-3.5 h-3.5" />{formatNumber(article.nb_likes || 0)}
              </button>
              <button className="flex items-center gap-1 hover:text-brand transition-colors">
                <Share2 className="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          {/* Content */}
          <div
            className="prose prose-lg max-w-none text-ink-800 leading-[1.8]
              prose-headings:font-display prose-headings:text-ink-900
              prose-a:text-brand prose-a:no-underline hover:prose-a:underline
              prose-blockquote:border-brand prose-blockquote:text-ink-600
              prose-code:bg-ink-100 prose-code:text-ink-800 prose-code:rounded"
            dangerouslySetInnerHTML={{ __html: article.contenu }}
          />

        </div>
      </main>
    </div>
  )
}