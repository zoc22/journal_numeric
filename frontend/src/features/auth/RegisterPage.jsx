import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { User, Mail, Lock, Eye, EyeOff, ArrowRight } from 'lucide-react'
import toast from 'react-hot-toast'
import authService from '../../services/authService'
import useAuthStore from '../../stores/authStore'
import Button from '../../components/ui/Button'
import Input from '../../components/ui/Input'
import { getErrorMessage } from '../../lib/utils'

export default function RegisterPage() {
  const navigate  = useNavigate()
  const { login } = useAuthStore()
  const [form, setForm]       = useState({ name: '', email: '', password: '', password_confirmation: '' })
  const [showPwd, setShowPwd] = useState(false)
  const [loading, setLoading] = useState(false)
  const [errors, setErrors]   = useState({})

  const handleChange = (e) => {
    setForm(f => ({ ...f, [e.target.name]: e.target.value }))
    setErrors(er => ({ ...er, [e.target.name]: '' }))
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    if (form.password !== form.password_confirmation) {
      setErrors({ password_confirmation: 'Les mots de passe ne correspondent pas' })
      return
    }
    setLoading(true)
    try {
      const res = await authService.register(form)
      const { user, token } = res.data
      login(user, token)
      toast.success('Compte créé avec succès !')
      navigate('/dashboard')
    } catch (err) {
      const msg = getErrorMessage(err)
      toast.error(msg)
      if (err?.errors) setErrors(err.errors)
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
          <div className="mb-6">
            <h1 className="font-display text-2xl font-bold text-ink-900">Créer un compte</h1>
            <p className="mt-1 text-ink-500 text-sm">
              Déjà inscrit ?{' '}
              <Link to="/login" className="text-brand font-medium hover:underline">Se connecter</Link>
            </p>
          </div>

          <form onSubmit={handleSubmit} className="space-y-4">
            <Input
              label="Nom complet"
              name="name"
              placeholder="Jean Dupont"
              value={form.name}
              onChange={handleChange}
              error={errors.name}
              leftIcon={<User className="w-4 h-4" />}
              required
            />
            <Input
              label="Email"
              name="email"
              type="email"
              placeholder="vous@exemple.com"
              value={form.email}
              onChange={handleChange}
              error={errors.email}
              leftIcon={<Mail className="w-4 h-4" />}
              required
            />
            <Input
              label="Mot de passe"
              name="password"
              type={showPwd ? 'text' : 'password'}
              placeholder="Minimum 8 caractères"
              value={form.password}
              onChange={handleChange}
              error={errors.password}
              leftIcon={<Lock className="w-4 h-4" />}
              rightIcon={
                <button type="button" onClick={() => setShowPwd(s => !s)} className="text-ink-400 hover:text-ink-700">
                  {showPwd ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                </button>
              }
              required
            />
            <Input
              label="Confirmer le mot de passe"
              name="password_confirmation"
              type={showPwd ? 'text' : 'password'}
              placeholder="••••••••"
              value={form.password_confirmation}
              onChange={handleChange}
              error={errors.password_confirmation}
              leftIcon={<Lock className="w-4 h-4" />}
              required
            />

            <p className="text-xs text-ink-400">
              En créant un compte, vous acceptez nos{' '}
              <a href="#" className="text-brand hover:underline">Conditions d'utilisation</a>{' '}
              et notre{' '}
              <a href="#" className="text-brand hover:underline">Politique de confidentialité</a>.
            </p>

            <Button type="submit" size="lg" className="w-full" isLoading={loading} rightIcon={<ArrowRight className="w-4 h-4" />}>
              Créer mon compte
            </Button>
          </form>
        </div>
      </div>
    </div>
  )
}