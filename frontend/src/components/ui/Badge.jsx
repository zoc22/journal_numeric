import { cn } from '../../lib/utils'
import { STATUS_COLORS, STATUS_LABELS } from '@/config/constants'

export function Badge({ children, variant = 'default', className }) {
  const variants = {
    default:  'bg-ink-100 text-ink-700',
    brand:    'bg-brand-50 text-brand font-medium',
    success:  'bg-emerald-50 text-emerald-700',
    warning:  'bg-amber-50 text-amber-700',
    danger:   'bg-red-50 text-red-700',
    info:     'bg-sky-50 text-sky-700',
    purple:   'bg-purple-50 text-purple-700',
  }
  return (
    <span className={cn('inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium', variants[variant], className)}>
      {children}
    </span>
  )
}

export function StatusBadge({ status }) {
  return (
    <span className={cn('inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium', STATUS_COLORS[status] || 'bg-ink-100 text-ink-600')}>
      <span className="w-1.5 h-1.5 rounded-full bg-current opacity-70" />
      {STATUS_LABELS[status] || status}
    </span>
  )
}