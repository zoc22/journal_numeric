import { useState } from 'react'
import { User, Mail, Lock, Camera, Save } from 'lucide-react'
import Navbar from '../../components/shared/Navbar'
import { Card, CardBody, CardHeader } from '../../components/ui/Card'
import Button from '../../components/ui/Button'
import Input from '../../components/ui/Input'
import useAuthStore from '../../stores/authStore'
import authService from '../../services/authService'
import { getInitials } from '../../lib/utils'
import toast from 'react-hot-toast'

export default function ProfilePage() {
  const { user, updateUser } = useAuthStore()
  const [profileForm, setProfileForm] = useState({ name: user?.name || '', email: user?.email || '' })
  const [pwdForm,     setPwdForm]     = useState({ current_password: '', password: '', password_confirmation: '' })
  const [savingProfile, setSavingProfile] = useState(false)
  const [savingPwd,     setSavingPwd]     = useState(false)

  const handleProfileSave = async (e) => {
    e.preventDefault()
    setSavingProfile(true)
    try {
      const res = await authService.updateProfile(profileForm)
      updateUser(res.data?.data || res.data)
      toast.success('Profil mis à jour !')
    } catch {
      toast.error('Erreur lors de la mise à jour')
    } finally {
      setSavingProfile(false)
    }
  }

  const handlePwdSave = async (e) => {
    e.preventDefault()
    if (pwdForm.password !== pwdForm.password_confirmation) {
      toast.error('Les mots de passe ne correspondent pas')
      return
    }
    setSavingPwd(true)
    try {
      await authService.updatePassword(pwdForm)
      toast.success('Mot de passe modifié !')
      setPwdForm({ current_password: '', password: '', password_confirmation: '' })
    } catch {
      toast.error('Mot de passe actuel incorrect')
    } finally {
      setSavingPwd(false)
    }
  }

  return (
    <div className="min-h-screen bg-ink-50">
      <Navbar />
      <main className="pt-14">
        <div className="max-w-2xl mx-auto px-4 sm:px-6 py-10">

          <div className="mb-8">
            <h1 className="font-display text-3xl font-bold text-ink-900">Mon profil</h1>
            <p className="text-ink-500 text-sm mt-1">Gérez vos informations personnelles.</p>
          </div>

          {/* Avatar */}
          <Card className="mb-6">
            <CardBody className="flex items-center gap-5">
              <div className="relative">
                {user?.avatar ? (
                  <img src={user.avatar} alt="" className="w-20 h-20 rounded-2xl object-cover" />
                ) : (
                  <div className="w-20 h-20 rounded-2xl bg-gradient-to-br from-brand to-accent-purple grid place-items-center text-white font-display text-2xl font-bold">
                    {getInitials(user?.name)}
                  </div>
                )}
                <button className="absolute -bottom-1 -right-1 w-7 h-7 bg-white border border-ink-200 rounded-full grid place-items-center shadow-sm hover:bg-ink-50">
                  <Camera className="w-3.5 h-3.5 text-ink-600" />
                </button>
              </div>
              <div>
                <p className="font-display font-bold text-ink-900 text-lg">{user?.name}</p>
                <p className="text-sm text-ink-500">{user?.email}</p>
                <span className="text-xs font-medium text-brand bg-brand-50 px-2 py-0.5 rounded-full mt-1 inline-block capitalize">
                  {user?.role}
                </span>
              </div>
            </CardBody>
          </Card>

          {/* Profile form */}
          <Card className="mb-6">
            <CardHeader>
              <h2 className="font-display font-bold text-ink-900">Informations personnelles</h2>
            </CardHeader>
            <CardBody>
              <form onSubmit={handleProfileSave} className="space-y-4">
                <Input
                  label="Nom complet"
                  value={profileForm.name}
                  onChange={e => setProfileForm(f => ({ ...f, name: e.target.value }))}
                  leftIcon={<User className="w-4 h-4" />}
                  required
                />
                <Input
                  label="Email"
                  type="email"
                  value={profileForm.email}
                  onChange={e => setProfileForm(f => ({ ...f, email: e.target.value }))}
                  leftIcon={<Mail className="w-4 h-4" />}
                  required
                />
                <Button type="submit" isLoading={savingProfile} leftIcon={<Save className="w-4 h-4" />}>
                  Enregistrer
                </Button>
              </form>
            </CardBody>
          </Card>

          {/* Password form */}
          <Card>
            <CardHeader>
              <h2 className="font-display font-bold text-ink-900">Changer le mot de passe</h2>
            </CardHeader>
            <CardBody>
              <form onSubmit={handlePwdSave} className="space-y-4">
                <Input
                  label="Mot de passe actuel"
                  type="password"
                  value={pwdForm.current_password}
                  onChange={e => setPwdForm(f => ({ ...f, current_password: e.target.value }))}
                  leftIcon={<Lock className="w-4 h-4" />}
                  required
                />
                <Input
                  label="Nouveau mot de passe"
                  type="password"
                  value={pwdForm.password}
                  onChange={e => setPwdForm(f => ({ ...f, password: e.target.value }))}
                  leftIcon={<Lock className="w-4 h-4" />}
                  required
                />
                <Input
                  label="Confirmer le nouveau mot de passe"
                  type="password"
                  value={pwdForm.password_confirmation}
                  onChange={e => setPwdForm(f => ({ ...f, password_confirmation: e.target.value }))}
                  leftIcon={<Lock className="w-4 h-4" />}
                  required
                />
                <Button type="submit" variant="secondary" isLoading={savingPwd} leftIcon={<Save className="w-4 h-4" />}>
                  Changer le mot de passe
                </Button>
              </form>
            </CardBody>
          </Card>

        </div>
      </main>
    </div>
  )
}