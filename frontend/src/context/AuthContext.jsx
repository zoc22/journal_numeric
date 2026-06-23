import { createContext, useContext, useEffect, useState } from 'react'
import useAuthStore from '../stores/authStore'
import authService from '../services/authService'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [loading, setLoading] = useState(true)
  const authStore = useAuthStore() // 1. On récupère tout le store Zustand

  useEffect(() => {
    const init = async () => {
      if (!authStore.token) { 
        setLoading(false)
        return 
      }
      try {
        const res = await authService.me()
        // On s'assure d'enregistrer l'utilisateur dans le store
        authStore.setUser(res.data.data || res.data)
      } catch (error) {
        console.error("Échec de la récupération de l'utilisateur :", error)
        authStore.logout()
      } finally {
        setLoading(false)
      }
    }
    init()
  }, []) // eslint-disable-line

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-ink-50">
        <div className="w-10 h-10 rounded-full border-[3px] border-ink-200 border-t-brand animate-spin" />
      </div>
    )
  }

  // 2. Syntaxe React 19 : <AuthContext> au lieu de <AuthContext.Provider>
  // 3. On propage le loading ET tout le contenu de ton store d'authentification
  return (
    <AuthContext value={{ loading, ...authStore }}>
      {children}
    </AuthContext>
  )
}

export const useAuth = () => useContext(AuthContext)