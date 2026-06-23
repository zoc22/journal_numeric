import api from '../config/axios'
import { buildQuery } from '../lib/utils'

const maisonService = {
  // Public
  getPublic: (params = {}) =>
    api.get(`/maisons/public?${buildQuery(params)}`),

  getPublicBySlug: (slug) =>
    api.get(`/maisons/public/${slug}`),

  // Authenticated
  getMy: () =>
    api.get('/maisons/mes-maisons'),

  getById: (id) =>
    api.get(`/maisons/${id}`),

  create: (data) =>
    api.post('/maisons', data),

  update: (id, data) =>
    api.put(`/maisons/${id}`, data),

  uploadLogo: (id, formData) =>
    api.post(`/maisons/${id}/logo`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),

  // Team management
  getMembers: (id) =>
    api.get(`/maisons/${id}/membres`),

  inviteMember: (id, data) =>
    api.post(`/maisons/${id}/inviter`, data),

  updateMemberRole: (id, userId, role) =>
    api.put(`/maisons/${id}/membres/${userId}`, { role }),

  removeMember: (id, userId) =>
    api.delete(`/maisons/${id}/membres/${userId}`),

  // Candidatures (appels)
  getAppels: (id) =>
    api.get(`/maisons/${id}/appels`),

  createAppel: (id, data) =>
    api.post(`/maisons/${id}/appels`, data),

  getCandidatures: (id, appelId) =>
    api.get(`/maisons/${id}/appels/${appelId}/candidatures`),

  reviewCandidature: (id, appelId, candidatureId, data) =>
    api.put(`/maisons/${id}/appels/${appelId}/candidatures/${candidatureId}`, data),

  // Apply to a maison
  apply: (id, data) =>
    api.post(`/maisons/${id}/postuler`, data),

  // Admin
  validate: (id) =>
    api.post(`/admin/maisons/${id}/valider`),

  suspend: (id, reason) =>
    api.post(`/admin/maisons/${id}/suspendre`, { raison: reason }),

  getAll: (params = {}) =>
    api.get(`/admin/maisons?${buildQuery(params)}`),
}

export default maisonService