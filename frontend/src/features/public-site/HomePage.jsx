import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { ArrowRight, Shield, Users, FileCheck, TrendingUp, Star, ChevronRight } from 'lucide-react'
import Navbar from '../../components/shared/Navbar'
import articleService from '../../services/articleService'
import maisonService from '../../services/maisonService'
import { formatDate, formatNumber, truncate } from '../../lib/utils'
import { StatusBadge } from '../../components/ui/Badge'

const WORKFLOW_STEPS = [
  { step: '01', label: 'Rédaction',           desc: 'Le journaliste rédige et soumet son article.', color: 'bg-brand-50 text-brand' },
  { step: '02', label: 'Relecture Reviewer',  desc: 'Au moins 2 reviewers évaluent le contenu.', color: 'bg-amber-50 text-amber-700' },
  { step: '03', label: 'Validation Éditeur',  desc: 'L\'éditeur associé contrôle la qualité.', color: 'bg-violet-50 text-violet-700' },
  { step: '04', label: 'Validation Directeur',desc: 'Le directeur de collection valide thématiquement.', color: 'bg-purple-50 text-purple-700' },
  { step: '05', label: 'Validation Finale',   desc: 'L\'éditeur en chef approuve la publication.', color: 'bg-teal-50 text-teal-700' },
  { step: '06', label: 'Publication',         desc: 'L\'article est publié et promu.', color: 'bg-emerald-50 text-emerald-700' },
]

const FEATURES = [
  { icon: Shield,    title: 'Gouvernance éditoriale', desc: 'Workflow à 5 niveaux de validation inspiré des standards Springer & Nature.' },
  { icon: Users,     title: 'Multi-tenant',           desc: 'Chaque maison d\'édition dispose de son espace indépendant et sécurisé.' },
  { icon: FileCheck, title: 'Traçabilité totale',     desc: 'Chaque décision éditoriale est horodatée et attribuée nominativement.' },
  { icon: TrendingUp,title: 'Monétisation intégrée',  desc: 'Abonnements, contenus premium et dons directement dans la plateforme.' },
]

export default function HomePage() {
  const [articles, setArticles] = useState([])
  const [maisons,  setMaisons]  = useState([])

  useEffect(() => {
    articleService.getPublic({ per_page: 3 }).then(r => setArticles(r.data?.data || [])).catch(() => {})
    maisonService.getPublic({ per_page: 4 }).then(r => setMaisons(r.data?.data || [])).catch(() => {})
  }, [])

  return (
    <div className="min-h-screen bg-ink-50">
      <Navbar />
      <main className="pt-14">

        {/* ── HERO ── */}
        <section className="relative gradient-hero grain overflow-hidden">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 pt-16 sm:pt-24 pb-20 sm:pb-32 relative">
            <div className="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-ink-200 rounded-full text-xs font-mono text-ink-600 mb-8">
              <span className="w-1.5 h-1.5 rounded-full bg-accent-emerald animate-pulse-dot" />
              v1.0 · Plateforme éditoriale multi-tenant
            </div>
            <h1 className="font-display text-5xl sm:text-7xl lg:text-8xl font-bold tracking-tight leading-[.95] max-w-5xl">
              Le journalisme<br />
              <span className="italic font-normal">gouverné par</span>{' '}
              <span className="text-gradient">la rigueur.</span>
            </h1>
            <p className="mt-8 max-w-2xl text-lg sm:text-xl text-ink-600 leading-relaxed">
              Une infrastructure éditoriale professionnelle pour les maisons d'édition africaines.
              Workflow de validation multi-niveaux, traçabilité totale, monétisation intégrée.
            </p>
            <div className="mt-10 flex flex-wrap items-center gap-3">
              <Link
                to="/register"
                className="inline-flex items-center gap-2 px-6 py-3.5 bg-ink-900 text-white font-semibold rounded-xl hover:bg-brand transition shadow-glow"
              >
                Créer ma maison d'édition <ArrowRight className="w-4 h-4" />
              </Link>
              <Link
                to="/articles"
                className="inline-flex items-center gap-2 px-6 py-3.5 bg-white border border-ink-200 text-ink-800 font-semibold rounded-xl hover:border-ink-400 transition"
              >
                Découvrir les articles
              </Link>
            </div>

            {/* KPI strip */}
            <div className="mt-16 grid grid-cols-2 sm:grid-cols-4 gap-px bg-ink-200 border border-ink-200 rounded-2xl overflow-hidden">
              {[
                { label: 'Maisons',       value: '47.' },
                { label: 'Articles publiés', value: '12.8K' },
                { label: 'Journalistes',  value: '3.2K' },
                { label: 'Lecteurs / mois', value: '2.4M' },
              ].map(({ label, value }) => (
                <div key={label} className="bg-white p-5">
                  <div className="font-mono text-[10px] text-ink-400 uppercase tracking-wider">{label}</div>
                  <div className="font-display text-3xl font-bold mt-1">{value}</div>
                </div>
              ))}
            </div>
          </div>

          {/* Marquee */}
          <div className="relative border-y border-ink-200 bg-white/60 py-5 overflow-hidden">
            <div className="flex marquee-track whitespace-nowrap gap-12 text-ink-400 font-display italic text-2xl">
              {['Le Monde Afrique','Jeune Afrique','Nature Africa','Cameroon Tribune','Africa Report','Quartz Africa',
                'Le Monde Afrique','Jeune Afrique','Nature Africa','Cameroon Tribune','Africa Report','Quartz Africa'].map((n, i) => (
                <span key={i}>{n}&nbsp;&nbsp;·</span>
              ))}
            </div>
          </div>
        </section>

        {/* ── WORKFLOW ── */}
        <section className="max-w-7xl mx-auto px-4 sm:px-6 py-20 sm:py-32">
          <div className="grid lg:grid-cols-12 gap-12">
            <div className="lg:col-span-4">
              <div className="sticky top-24">
                <div className="font-mono text-xs text-brand uppercase tracking-wider mb-3">§ 01 — Workflow</div>
                <h2 className="font-display text-4xl sm:text-5xl font-bold tracking-tight">
                  Six étapes.<br />Une seule vérité.
                </h2>
                <p className="mt-5 text-ink-600 leading-relaxed">
                  Inspiré des standards Springer & Nature, chaque article traverse un pipeline
                  de validation rigoureux. Aucune publication sans double relecture.
                </p>
              </div>
            </div>
            <div className="lg:col-span-8 space-y-3">
              {WORKFLOW_STEPS.map(({ step, label, desc, color }) => (
                <div key={step} className="flex gap-4 p-5 bg-white rounded-2xl border border-ink-100 shadow-soft hover:shadow-card transition-shadow">
                  <span className={`inline-flex items-center justify-center w-10 h-10 rounded-xl font-mono text-sm font-bold shrink-0 ${color}`}>
                    {step}
                  </span>
                  <div>
                    <h3 className="font-semibold text-ink-900 mb-1">{label}</h3>
                    <p className="text-sm text-ink-500">{desc}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* ── FEATURES ── */}
        <section className="gradient-dark text-white py-20 sm:py-32">
          <div className="max-w-7xl mx-auto px-4 sm:px-6">
            <div className="font-mono text-xs text-brand-light uppercase tracking-wider mb-4">§ 02 — Fonctionnalités</div>
            <h2 className="font-display text-4xl sm:text-5xl font-bold tracking-tight mb-12 max-w-2xl">
              Une plateforme conçue pour les professionnels.
            </h2>
            <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
              {FEATURES.map(({ icon: Icon, title, desc }) => (
                <div key={title} className="p-6 bg-white/5 hover:bg-white/10 rounded-2xl border border-white/10 transition-colors">
                  <div className="w-10 h-10 bg-brand/20 rounded-xl grid place-items-center mb-4">
                    <Icon className="w-5 h-5 text-brand-light" />
                  </div>
                  <h3 className="font-semibold text-white mb-2">{title}</h3>
                  <p className="text-white/50 text-sm leading-relaxed">{desc}</p>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* ── RECENT ARTICLES ── */}
        {articles.length > 0 && (
          <section className="max-w-7xl mx-auto px-4 sm:px-6 py-20 sm:py-32">
            <div className="flex items-end justify-between mb-10">
              <div>
                <div className="font-mono text-xs text-brand uppercase tracking-wider mb-3">§ 03 — Articles récents</div>
                <h2 className="font-display text-4xl font-bold">Dernières publications</h2>
              </div>
              <Link to="/articles" className="hidden sm:flex items-center gap-1.5 text-sm font-medium text-brand hover:underline">
                Tout voir <ChevronRight className="w-4 h-4" />
              </Link>
            </div>
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
              {articles.map(article => (
                <Link key={article.id} to={`/articles/${article.slug}`} className="group">
                  <div className="bg-white rounded-2xl border border-ink-100 shadow-soft hover:shadow-card transition-all overflow-hidden">
                    {article.image_principale && (
                      <img src={article.image_principale} alt="" className="w-full h-48 object-cover" />
                    )}
                    <div className="p-5">
                      <StatusBadge status={article.statut} />
                      <h3 className="font-display font-bold text-ink-900 mt-3 mb-2 group-hover:text-brand transition-colors line-clamp-2">
                        {article.titre}
                      </h3>
                      <p className="text-sm text-ink-500 line-clamp-2">{truncate(article.resume, 120)}</p>
                      <div className="mt-4 flex items-center gap-2 text-xs text-ink-400">
                        <span>{formatDate(article.publie_le)}</span>
                        <span>·</span>
                        <span>{article.temps_lecture || 3} min de lecture</span>
                      </div>
                    </div>
                  </div>
                </Link>
              ))}
            </div>
          </section>
        )}

        {/* ── CTA ── */}
        <section className="max-w-7xl mx-auto px-4 sm:px-6 pb-24">
          <div className="gradient-hero grain relative rounded-3xl border border-ink-200 p-12 sm:p-20 text-center overflow-hidden">
            <div className="font-mono text-xs text-brand uppercase tracking-wider mb-4">Rejoignez-nous</div>
            <h2 className="font-display text-4xl sm:text-5xl font-bold tracking-tight mb-5">
              Prêt à professionnaliser<br />votre média ?
            </h2>
            <p className="text-ink-600 max-w-xl mx-auto mb-8">
              Créez votre maison d'édition, recrutez votre équipe et publiez vos premiers articles
              en quelques minutes.
            </p>
            <Link
              to="/register"
              className="inline-flex items-center gap-2 px-8 py-4 bg-ink-900 text-white font-semibold rounded-2xl hover:bg-brand transition shadow-glow text-lg"
            >
              Commencer gratuitement <ArrowRight className="w-5 h-5" />
            </Link>
          </div>
        </section>

        {/* Footer */}
        <footer className="border-t border-ink-200 bg-white py-8">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-ink-400">
            <span>© 2026 NexusPress · Plateforme éditoriale pour l'Afrique</span>
            <div className="flex gap-6">
              <a href="#" className="hover:text-ink-700">Conditions</a>
              <a href="#" className="hover:text-ink-700">Confidentialité</a>
              <a href="#" className="hover:text-ink-700">Contact</a>
            </div>
          </div>
        </footer>
      </main>
    </div>
  )
}