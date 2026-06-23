import { useState } from 'react'
import { Link, useNavigate, useLocation } from 'react-router-dom'
import { Eye, EyeOff, Mail, Lock, ArrowRight } from 'lucide-react'
import toast from 'react-hot-toast'
import authService from '../../services/authService'
import useAuthStore from '../../stores/authStore'
import Button from '../../components/ui/Button'
import Input from '../../components/ui/Input'
import { getErrorMessage } from '../../lib/utils'

export default function LoginPage() {
  const navigate = useNavigate()
  const location = useLocation()
  const { login } = useAuthStore()
  const [form, setForm]       = useState({ email: '', password: '' })
  const [showPwd, setShowPwd] = useState(false)
  const [loading, setLoading] = useState(false)
  const [errors, setErrors]   = useState({})

  const from = location.state?.from?.pathname || '/dashboard'

  const handleChange = (e) => {
    setForm(f => ({ ...f, [e.target.name]: e.target.value }))
    setErrors(er => ({ ...er, [e.target.name]: '' }))
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    if (!form.email || !form.password) {
      setErrors({ email: !form.email ? 'Email requis' : '', password: !form.password ? 'Mot de passe requis' : '' })
      return
    }
    setLoading(true)
    try {
      const res = await authService.login(form)
      const { user, token } = res.data
      login(user, token)
      toast.success(`Bienvenue, ${user.name} !`)
      navigate(from, { replace: true })
    } catch (err) {
      const msg = getErrorMessage(err)
      toast.error(msg)
      if (err?.errors) setErrors(err.errors)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen gradient-hero grain flex">
      {/* Left panel — branding */}
      <div className="hidden lg:flex flex-col justify-between w-1/2 p-12 gradient-dark text-white">
        <Link to="/" className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-xl bg-white/10 grid place-items-center font-display font-bold text-lg">N</div>
          <span className="font-display text-xl font-bold">NexusPress</span>
        </Link>
        <div>
          <blockquote className="font-display text-3xl font-bold leading-tight mb-6">
            "Le journalisme rigoureux<br />
            <span className="italic font-normal opacity-70">commence ici.</span>"
          </blockquote>
          <p className="text-white/60 text-sm leading-relaxed max-w-sm">
            Rejoignez les maisons d'édition professionnelles qui font confiance à NexusPress
            pour gouverner leur processus éditorial.
          </p>
        </div>
        <div className="flex gap-4 text-white/40 text-xs">
          <span>© 2026 NexusPress</span>
          <span>·</span>
          <a href="#" className="hover:text-white/70">Confidentialité</a>
        </div>
      </div>

      {/* Right panel — form */}
      <div className="flex-1 flex items-center justify-center p-6">
        <div className="w-full max-w-md">
          {/* Mobile logo */}
          <Link to="/" className="flex lg:hidden items-center gap-2 mb-8 justify-center">
            <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-brand to-accent-purple grid place-items-center text-white font-display font-bold">N</div>
            <span className="font-display text-xl font-bold">NexusPress</span>
          </Link>

          <div className="mb-8">
            <h1 className="font-display text-3xl font-bold text-ink-900">Connexion</h1>
            <p className="mt-2 text-ink-500 text-sm">
              Pas encore de compte ?{' '}
              <Link to="/register" className="text-brand font-medium hover:underline">
                S'inscrire gratuitement
              </Link>
            </p>
          </div>

          <form onSubmit={handleSubmit} className="space-y-4">
            <Input
              label="Adresse email"
              name="email"
              type="email"
              placeholder="vous@exemple.com"
              value={form.email}
              onChange={handleChange}
              error={errors.email}
              leftIcon={<Mail className="w-4 h-4" />}
              required
              autoComplete="email"
            />
            <Input
              label="Mot de passe"
              name="password"
              type={showPwd ? 'text' : 'password'}
              placeholder="••••••••"
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
              autoComplete="current-password"
            />

            <div className="flex justify-end">
              <Link to="/forgot-password" className="text-sm text-brand hover:underline">
                Mot de passe oublié ?
              </Link>
            </div>

            <Button
              type="submit"
              size="lg"
              className="w-full"
              isLoading={loading}
              rightIcon={<ArrowRight className="w-4 h-4" />}
            >
              Se connecter
            </Button>
          </form>
        </div>
      </div>
    </div>
  )
}