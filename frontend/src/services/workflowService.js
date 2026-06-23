import api from '../config/axios'
import { buildQuery } from '../lib/utils'

const workflowService = {
  // Articles pending review (for reviewer)
  getPendingReviews: (params = {}) =>
    api.get(`/workflow/en-attente?${buildQuery(params)}`),

  // Articles in my pipeline (editor)
  getPipeline: (params = {}) =>
    api.get(`/workflow/pipeline?${buildQuery(params)}`),

  // Stats
  getWorkflowStats: () =>
    api.get('/workflow/statistiques'),

  // Annotations
  addAnnotation: (articleId, data) =>
    api.post(`/articles/${articleId}/annotations`, data),

  resolveAnnotation: (articleId, annotationId) =>
    api.post(`/articles/${articleId}/annotations/${annotationId}/resoudre`),

  // Comments
  addComment: (articleId, data) =>
    api.post(`/articles/${articleId}/commentaires`, data),

  getComments: (articleId) =>
    api.get(`/articles/${articleId}/commentaires`),
}

export default workflowService