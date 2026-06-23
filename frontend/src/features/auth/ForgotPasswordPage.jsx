import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Mail, ArrowLeft, CheckCircle } from 'lucide-react'
import toast from 'react-hot-toast'
import authService from '../../services/authService'
import Button from '../../components/ui/Button'
import Input from '../../components/ui/Input'

export default function ForgotPasswordPage() {
  const [email,   setEmail]   = useState('')
  const [loading, setLoading] = useState(false)
  const [sent,    setSent]    = useState(false)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setLoading(true)
    try {
      await authService.forgotPassword(email)
      setSent(true)
    } catch {
      toast.error('Une erreur est survenue. Vérifiez votre email.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen gradient-hero grain flex items-center justify-center p-6">
      <div className="w-full max-w-md">
        <Link to="/" className="flex items-center gap-2 mb-8 justify-center">
          <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-brand to-accent-purple grid place-items-center text-white font-display font-bold">N</div>
          <span className="font-display text-xl font-bold">NexusPress</span>
        </Link>

        <div className="bg-white rounded-2xl shadow-soft border border-ink-100 p-8">
          {sent ? (
            <div className="text-center py-4">
              <div className="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <CheckCircle className="w-8 h-8 text-emerald-600" />
              </div>
              <h2 className="font-display text-xl font-bold text-ink-900 mb-2">Email envoyé !</h2>
              <p className="text-ink-500 text-sm mb-6">
                Nous avons envoyé un lien de réinitialisation à <strong>{email}</strong>.
                Vérifiez votre boîte de réception.
              </p>
              <Link to="/login" className="text-sm text-brand font-medium hover:underline">
                Retour à la connexion
              </Link>
            </div>
          ) : (
            <>
              <div className="mb-6">
                <h1 className="font-display text-2xl font-bold text-ink-900">Mot de passe oublié</h1>
                <p className="mt-1 text-ink-500 text-sm">
                  Entrez votre email pour recevoir un lien de réinitialisation.
                </p>
              </div>
              <form onSubmit={handleSubmit} className="space-y-4">
                <Input
                  label="Adresse email"
                  type="email"
                  placeholder="vous@exemple.com"
                  value={email}
                  onChange={e => setEmail(e.target.value)}
                  leftIcon={<Mail className="w-4 h-4" />}
                  required
                />
                <Button type="submit" size="lg" className="w-full" isLoading={loading}>
                  Envoyer le lien
                </Button>
              </form>
              <Link to="/login" className="flex items-center justify-center gap-1.5 mt-5 text-sm text-ink-500 hover:text-ink-800">
                <ArrowLeft className="w-4 h-4" /> Retour à la connexion
              </Link>
            </>
          )}
        </div>
      </div>
    </div>
  )
}