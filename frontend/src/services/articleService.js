import api from '../config/axios'
import { buildQuery } from '../lib/utils'

const articleService = {
  // Public
  getPublic: (params = {}) =>
    api.get(`/articles/public?${buildQuery(params)}`),

  getPublicBySlug: (slug) =>
    api.get(`/articles/public/${slug}`),

  // Authenticated
  getMyArticles: (params = {}) =>
    api.get(`/articles?${buildQuery(params)}`),

  getById: (id) =>
    api.get(`/articles/${id}`),

  create: (data) =>
    api.post('/articles', data),

  update: (id, data) =>
    api.put(`/articles/${id}`, data),

  delete: (id) =>
    api.delete(`/articles/${id}`),

  submit: (id) =>
    api.post(`/articles/${id}/soumettre`),

  // Workflow actions
  assignReviewers: (id, reviewerIds) =>
    api.post(`/articles/${id}/assigner-reviewers`, { reviewer_ids: reviewerIds }),

  review: (id, data) =>
    api.post(`/articles/${id}/review`, data),

  requestCorrection: (id, feedback) =>
    api.post(`/articles/${id}/demander-correction`, { feedback }),

  validateEditor: (id, data) =>
    api.post(`/articles/${id}/valider-editeur`, data),

  validateDirector: (id, data) =>
    api.post(`/articles/${id}/valider-directeur`, data),

  validateFinal: (id, data) =>
    api.post(`/articles/${id}/valider-final`, data),

  publish: (id, data) =>
    api.post(`/articles/${id}/publier`, data),

  reject: (id, reason) =>
    api.post(`/articles/${id}/rejeter`, { raison: reason }),

  archive: (id) =>
    api.post(`/articles/${id}/archiver`),

  // Versions
  getVersions: (id) =>
    api.get(`/articles/${id}/versions`),

  // Media upload
  uploadMedia: (id, formData) =>
    api.post(`/articles/${id}/media`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),

  // Stats
  getStats: (id) =>
    api.get(`/articles/${id}/stats`),
}

export default articleService