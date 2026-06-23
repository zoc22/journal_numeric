import api from '../config/axios'
import { buildQuery } from '../lib/utils'

const userService = {
  getAll: (params = {}) =>
    api.get(`/admin/utilisateurs?${buildQuery(params)}`),

  getById: (id) =>
    api.get(`/admin/utilisateurs/${id}`),

  updateRole: (id, role) =>
    api.put(`/admin/utilisateurs/${id}/role`, { role }),

  activate: (id) =>
    api.post(`/admin/utilisateurs/${id}/activer`),

  deactivate: (id) =>
    api.post(`/admin/utilisateurs/${id}/desactiver`),

  getActivityLogs: (id, params = {}) =>
    api.get(`/admin/utilisateurs/${id}/activites?${buildQuery(params)}`),
}

export default userService