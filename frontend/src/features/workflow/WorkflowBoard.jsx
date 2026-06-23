import { useState, useEffect } from 'react'
import { useSearchParams, useNavigate } from 'react-router-dom'
import { ArrowLeft, CheckCircle, XCircle, AlertCircle, MessageSquare, Clock } from 'lucide-react'
import Navbar from '../../components/shared/Navbar'
import { Card, CardBody, CardHeader } from '../../components/ui/Card'
import { StatusBadge } from '../../components/ui/Badge'
import Button from '../../components/ui/Button'
import Spinner from '../../components/ui/Spinner'
import articleService from '../../services/articleService'
import useAuthStore from '../../stores/authStore'
import { ROLES, ARTICLE_STATUS } from '../../config/constants'
import { formatDate, timeAgo } from '../../lib/utils'
import toast from 'react-hot-toast'

export default function WorkflowBoard() {
  const { user }          = useAuthStore()
  const [params]          = useSearchParams()
  const navigate          = useNavigate()
  const articleId         = params.get('article')

  const [article,  setArticle]  = useState(null)
  const [loading,  setLoading]  = useState(true)
  const [feedback, setFeedback] = useState('')
  const [acting,   setActing]   = useState(false)

  useEffect(() => {
    if (!articleId) { setLoading(false); return }
    articleService.getById(articleId)
      .then(r => setArticle(r.data?.data || r.data))
      .catch(() => toast.error('Article introuvable'))
      .finally(() => setLoading(false))
  }, [articleId])

  const act = async (actionFn, successMsg) => {
    setActing(true)
    try {
      await actionFn()
      toast.success(successMsg)
      navigate('/dashboard')
    } catch {
      toast.error('Une erreur est survenue')
    } finally {
      setActing(false)
    }
  }

  // Determine what actions this user can do
  const role    = user?.role
  const statut  = article?.statut

  const canReview         = role === ROLES.REVIEWER && statut === ARTICLE_STATUS.EN_REVIEW
  const canValidateEditor = role === ROLES.EDITEUR_ASSOCIE && statut === ARTICLE_STATUS.VALIDE_REVIEWER
  const canValidateDir    = role === ROLES.DIRECTEUR_COLLECTION && statut === ARTICLE_STATUS.VALIDE_EDITEUR
  const canValidateChef   = role === ROLES.EDITEUR_CHEF && statut === ARTICLE_STATUS.VALIDE_DIRECTEUR
  const canPublish        = role === ROLES.EDITEUR_CHEF && statut === ARTICLE_STATUS.VALIDE_FINAL

  if (loading) return <div className="min-h-screen flex items-center justify-center"><Spinner size="xl" /></div>

  if (!article) return (
    <div className="min-h-screen bg-ink-50">
      <Navbar />
      <div className="pt-14 flex items-center justify-center min-h-[60vh]">
        <div className="text-center">
          <AlertCircle className="w-16 h-16 text-ink-200 mx-auto mb-4" />
          <h2 className="font-display text-xl font-bold text-ink-700 mb-2">Aucun article sélectionné</h2>
          <p className="text-ink-500 mb-4">Sélectionnez un article depuis votre tableau de bord.</p>
          <Button onClick={() => navigate('/dashboard')} leftIcon={<ArrowLeft className="w-4 h-4" />} variant="secondary">
            Retour
          </Button>
        </div>
      </div>
    </div>
  )

  return (
    <div className="min-h-screen bg-ink-50">
      <Navbar />
      <main className="pt-14">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 py-8">

          {/* Back */}
          <button onClick={() => navigate(-1)} className="flex items-center gap-1.5 text-sm text-ink-500 hover:text-ink-900 mb-6">
            <ArrowLeft className="w-4 h-4" /> Retour au tableau de bord
          </button>

          {/* Article header */}
          <Card className="mb-6">
            <CardBody>
              <div className="flex items-start justify-between gap-4 mb-4">
                <div>
                  <StatusBadge status={article.statut} />
                  <h1 className="font-display text-2xl font-bold text-ink-900 mt-2">{article.titre}</h1>
                  <p className="text-sm text-ink-500 mt-1">
                    Par {article.auteur?.name} · {timeAgo(article.updated_at)}
                  </p>
                </div>
                <div className="flex items-center gap-2 text-xs text-ink-400 shrink-0">
                  <Clock className="w-3.5 h-3.5" />
                  {article.temps_lecture || 5} min
                </div>
              </div>
              {article.resume && (
                <p className="text-ink-600 border-t border-ink-100 pt-4">{article.resume}</p>
              )}
            </CardBody>
          </Card>

          {/* Article content */}
          <Card className="mb-6">
            <CardHeader>
              <h2 className="font-display font-bold text-ink-900">Contenu de l'article</h2>
            </CardHeader>
            <CardBody>
              <div
                className="prose max-w-none text-ink-700 leading-relaxed"
                dangerouslySetInnerHTML={{ __html: article.contenu }}
              />
            </CardBody>
          </Card>

          {/* Action panel */}
          {(canReview || canValidateEditor || canValidateDir || canValidateChef || canPublish) && (
            <Card>
              <CardHeader>
                <h2 className="font-display font-bold text-ink-900">
                  {canReview         && 'Votre avis de relecture'}
                  {canValidateEditor && 'Validation éditeur associé'}
                  {canValidateDir    && 'Validation thématique'}
                  {canValidateChef   && 'Validation finale'}
                  {canPublish        && 'Publication'}
                </h2>
              </CardHeader>
              <CardBody className="space-y-4">
                <div>
                  <label className="text-sm font-medium text-ink-700 mb-1.5 block">
                    Commentaires / feedback
                  </label>
                  <textarea
                    value={feedback}
                    onChange={e => setFeedback(e.target.value)}
                    placeholder="Ajoutez vos remarques, corrections ou notes…"
                    rows={4}
                    className="w-full rounded-xl border border-ink-200 px-4 py-3 text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand resize-none"
                  />
                </div>

                <div className="flex flex-wrap gap-3 pt-2">
                  {/* Reviewer actions */}
                  {canReview && (
                    <>
                      <Button
                        onClick={() => act(
                          () => articleService.review(article.id, { decision: 'approve', commentaires: feedback }),
                          'Relecture validée !'
                        )}
                        isLoading={acting}
                        leftIcon={<CheckCircle className="w-4 h-4" />}
                        variant="brand"
                      >
                        Valider l'article
                      </Button>
                      <Button
                        onClick={() => act(
                          () => articleService.requestCorrection(article.id, feedback),
                          'Corrections demandées'
                        )}
                        isLoading={acting}
                        leftIcon={<AlertCircle className="w-4 h-4" />}
                        variant="secondary"
                      >
                        Demander corrections
                      </Button>
                      <Button
                        onClick={() => act(
                          () => articleService.reject(article.id, feedback),
                          'Article rejeté'
                        )}
                        isLoading={acting}
                        leftIcon={<XCircle className="w-4 h-4" />}
                        variant="danger"
                      >
                        Rejeter
                      </Button>
                    </>
                  )}

                  {/* Editor actions */}
                  {canValidateEditor && (
                    <>
                      <Button
                        onClick={() => act(
                          () => articleService.validateEditor(article.id, { commentaires: feedback }),
                          'Validation éditeur effectuée !'
                        )}
                        isLoading={acting}
                        leftIcon={<CheckCircle className="w-4 h-4" />}
                        variant="brand"
                      >
                        Valider (éditeur)
                      </Button>
                      <Button
                        onClick={() => act(
                          () => articleService.requestCorrection(article.id, feedback),
                          'Corrections demandées'
                        )}
                        isLoading={acting}
                        variant="secondary"
                      >
                        Demander corrections
                      </Button>
                    </>
                  )}

                  {/* Director actions */}
                  {canValidateDir && (
                    <Button
                      onClick={() => act(
                        () => articleService.validateDirector(article.id, { commentaires: feedback }),
                        'Validation thématique effectuée !'
                      )}
                      isLoading={acting}
                      leftIcon={<CheckCircle className="w-4 h-4" />}
                      variant="brand"
                    >
                      Valider thématiquement
                    </Button>
                  )}

                  {/* Editor-in-chief actions */}
                  {canValidateChef && (
                    <Button
                      onClick={() => act(
                        () => articleService.validateFinal(article.id, { commentaires: feedback }),
                        'Validation finale effectuée !'
                      )}
                      isLoading={acting}
                      leftIcon={<CheckCircle className="w-4 h-4" />}
                      variant="brand"
                    >
                      Validation finale
                    </Button>
                  )}

                  {canPublish && (
                    <Button
                      onClick={() => act(
                        () => articleService.publish(article.id),
                        'Article publié avec succès !'
                      )}
                      isLoading={acting}
                      leftIcon={<CheckCircle className="w-4 h-4" />}
                      className="bg-emerald-600 text-white hover:bg-emerald-700"
                    >
                      Publier l'article
                    </Button>
                  )}
                </div>
              </CardBody>
            </Card>
          )}

        </div>
      </main>
    </div>
  )
}