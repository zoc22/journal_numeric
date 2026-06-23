import { create } from 'zustand';
import { TENANT_KEY } from '../config/constants';

const useTenantStore = create((set, get) => ({
  // State
  currentTenant: JSON.parse(localStorage.getItem(TENANT_KEY)) || null,
  tenants: [],
  isLoading: false,
  error: null,

  // Actions
  setCurrentTenant: (tenant) => {
    localStorage.setItem(TENANT_KEY, JSON.stringify(tenant));
    set({ currentTenant: tenant });
  },

  setTenants: (tenants) => {
    set({ tenants });
  },

  setLoading: (isLoading) => set({ isLoading }),

  setError: (error) => set({ error }),

  clearError: () => set({ error: null }),

  getTenantById: (id) => {
    return get().tenants.find((t) => t.id === id);
  },

  addTenant: (tenant) => {
    set({
      tenants: [...get().tenants, tenant],
    });
  },

  updateTenant: (id, updates) => {
    set({
      tenants: get().tenants.map((t) =>
        t.id === id ? { ...t, ...updates } : t
      ),
    });
  },

  removeTenant: (id) => {
    set({
      tenants: get().tenants.filter((t) => t.id !== id),
    });
  },
}));

export default useTenantStore;
