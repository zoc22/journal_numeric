import { cn } from '../../lib/utils'

const sizes = {
  sm: 'w-4 h-4 border-2',
  md: 'w-6 h-6 border-2',
  lg: 'w-10 h-10 border-[3px]',
  xl: 'w-16 h-16 border-4',
}

export default function Spinner({ size = 'md', className }) {
  return (
    <div
      className={cn(
        'rounded-full border-ink-200 border-t-brand animate-spin',
        sizes[size],
        className,
      )}
      aria-label="Chargement…"
      role="status"
    />
  )
}