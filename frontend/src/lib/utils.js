import { clsx } from 'clsx' // fallback: manual join
import { format, formatDistanceToNow, parseISO } from 'date-fns'
import { fr } from 'date-fns/locale'

// Simple className merger (no clsx dep needed if not installed)
export function cn(...classes) {
  return classes.filter(Boolean).join(' ')
}

// Date formatting
export function formatDate(date, pattern = 'dd MMM yyyy') {
  if (!date) return ''
  const d = typeof date === 'string' ? parseISO(date) : date
  return format(d, pattern, { locale: fr })
}

export function timeAgo(date) {
  if (!date) return ''
  const d = typeof date === 'string' ? parseISO(date) : date
  return formatDistanceToNow(d, { addSuffix: true, locale: fr })
}

// Truncate text
export function truncate(str, n = 100) {
  if (!str) return ''
  return str.length > n ? str.slice(0, n - 1) + '…' : str
}

// Reading time estimate
export function readingTime(content) {
  if (!content) return 1
  const words = content.trim().split(/\s+/).length
  return Math.max(1, Math.round(words / 200))
}

// Slugify
export function slugify(str) {
  return str
    .toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9\s-]/g, '')
    .trim()
    .replace(/\s+/g, '-')
}

// Get initials
export function getInitials(name = '') {
  return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()
}

// Format number (1200 → 1.2k)
export function formatNumber(n) {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
  if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'k'
  return String(n)
}

// Get error message from API error
export function getErrorMessage(error) {
  if (error?.response?.data?.message) return error.response.data.message
  if (error?.message) return error.message
  return 'Une erreur est survenue'
}

// Build query string from object
export function buildQuery(params) {
  const q = new URLSearchParams()
  Object.entries(params).forEach(([k, v]) => {
    if (v !== undefined && v !== null && v !== '') q.append(k, v)
  })
  return q.toString()
}