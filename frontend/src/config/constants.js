// src/config/constants.js

// API Configuration
export const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
export const APP_NAME = 'NexusPress'
export const APP_DOMAIN = import.meta.env.VITE_APP_DOMAIN || 'localhost'

// Storage keys
export const TOKEN_KEY  = 'auth_token'
export const USER_KEY   = 'user_data'
export const TENANT_KEY = 'current_tenant'

// Roles (correspond to backend Spatie roles)
export const ROLES = {
  LECTEUR:              'lecteur',
  JOURNALISTE:          'journaliste',
  REVIEWER:             'reviewer',
  EDITEUR_ASSOCIE:      'editeur_associe',
  DIRECTEUR_COLLECTION: 'directeur_collection',
  EDITEUR_CHEF:         'editeur_chef',
  ADMIN:                'admin',
  SUPER_ADMIN:          'super_admin',
}

// Permissions
export const PERMISSIONS = {
  ARTICLE_CREATE:               'article.creer',
  ARTICLE_EDIT:                 'article.modifier',
  ARTICLE_DELETE:               'article.supprimer',
  ARTICLE_SUBMIT:               'article.soumettre',
  REVIEW_ASSIGN:                'review.assigner',
  REVIEW_DO:                    'review.effectuer',
  WORKFLOW_VALIDATE_REVIEWER:   'workflow.valider_reviewer',
  WORKFLOW_VALIDATE_EDITOR:     'workflow.valider_editeur',
  WORKFLOW_VALIDATE_DIRECTOR:   'workflow.valider_directeur',
  WORKFLOW_VALIDATE_FINAL:      'workflow.valider_final',
  WORKFLOW_PUBLISH:             'workflow.publier',
  MAISON_MANAGE:                'maison.gerer',
  USER_MANAGE:                  'utilisateur.gerer',
  USER_ROLE_ASSIGN:             'utilisateur.role.assigner',
}

// Article workflow statuses
export const ARTICLE_STATUS = {
  BROUILLON:           'brouillon',
  SOUMIS:              'soumis',
  EN_REVIEW:           'en_review',
  VALIDE_REVIEWER:     'valide_reviewer',
  VALIDE_EDITEUR:      'valide_editeur',
  VALIDE_DIRECTEUR:    'valide_directeur',
  VALIDE_FINAL:        'valide_final',
  PUBLIE:              'publie',
  CORRECTION_DEMANDEE: 'correction_demandee',
  REJETE:              'rejete',
  ARCHIVE:             'archive',
}

export const STATUS_LABELS = {
  brouillon:           'Brouillon',
  soumis:              'Soumis',
  en_review:           'En relecture',
  valide_reviewer:     'Validé reviewer',
  valide_editeur:      'Validé éditeur',
  valide_directeur:    'Validé directeur',
  valide_final:        'Validé final',
  publie:              'Publié',
  correction_demandee: 'Correction demandée',
  rejete:              'Rejeté',
  archive:             'Archivé',
}

export const STATUS_COLORS = {
  brouillon:           'bg-ink-100 text-ink-600',
  soumis:              'bg-blue-50 text-blue-700',
  en_review:           'bg-amber-50 text-amber-700',
  valide_reviewer:     'bg-indigo-50 text-indigo-700',
  valide_editeur:      'bg-violet-50 text-violet-700',
  valide_directeur:    'bg-purple-50 text-purple-700',
  valide_final:        'bg-teal-50 text-teal-700',
  publie:              'bg-emerald-50 text-emerald-700',
  correction_demandee: 'bg-orange-50 text-orange-700',
  rejete:              'bg-red-50 text-red-700',
  archive:             'bg-ink-100 text-ink-400',
}

export const HTTP_STATUS = {
  SUCCESS:       200,
  CREATED:       201,
  NO_CONTENT:    204,
  BAD_REQUEST:   400,
  UNAUTHORIZED:  401,
  FORBIDDEN:     403,
  NOT_FOUND:     404,
  SERVER_ERROR:  500,
}