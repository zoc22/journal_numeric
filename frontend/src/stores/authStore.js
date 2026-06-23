import { create } from 'zustand';
import { TOKEN_KEY, USER_KEY } from '../config/constants';

const useAuthStore = create((set, get) => ({
  // State
  user: JSON.parse(localStorage.getItem(USER_KEY)) || null,
  token: localStorage.getItem(TOKEN_KEY) || null,
  isAuthenticated: !!localStorage.getItem(TOKEN_KEY),
  isLoading: false,
  error: null,

  // Actions
  setUser: (user) => {
    localStorage.setItem(USER_KEY, JSON.stringify(user));
    set({ user });
  },

  setToken: (token) => {
    if (token) {
      localStorage.setItem(TOKEN_KEY, token);
    } else {
      localStorage.removeItem(TOKEN_KEY);
    }
    set({ token, isAuthenticated: !!token });
  },

  setLoading: (isLoading) => set({ isLoading }),

  setError: (error) => set({ error }),

  clearError: () => set({ error: null }),

  login: (user, token) => {
    set({
      user,
      token,
      isAuthenticated: true,
    });
    localStorage.setItem(USER_KEY, JSON.stringify(user));
    localStorage.setItem(TOKEN_KEY, token);
  },

  logout: () => {
    set({
      user: null,
      token: null,
      isAuthenticated: false,
    });
    localStorage.removeItem(USER_KEY);
    localStorage.removeItem(TOKEN_KEY);
  },

  updateUser: (updates) => {
    const currentUser = get().user;
    const updatedUser = { ...currentUser, ...updates };
    set({ user: updatedUser });
    localStorage.setItem(USER_KEY, JSON.stringify(updatedUser));
  },

  hasRole: (role) => {
    const user = get().user;
    return user?.role === role;
  },

  hasPermission: (permission) => {
    const user = get().user;
    return user?.permissions?.includes(permission) || false;
  },

  hasAnyPermission: (permissions) => {
    const user = get().user;
    return permissions.some((p) => user?.permissions?.includes(p));
  },
}));

export default useAuthStore;
