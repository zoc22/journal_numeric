import { cn } from '../../lib/utils'

export function Card({ children, className, hover = false, ...props }) {
  return (
    <div
      className={cn(
        'bg-white rounded-2xl border border-ink-100 shadow-soft',
        hover && 'hover:shadow-card hover:border-ink-200 transition-all duration-200',
        className,
      )}
      {...props}
    >
      {children}
    </div>
  )
}

export function CardHeader({ children, className }) {
  return <div className={cn('px-6 py-5 border-b border-ink-100', className)}>{children}</div>
}

export function CardBody({ children, className }) {
  return <div className={cn('px-6 py-5', className)}>{children}</div>
}

export function CardFooter({ children, className }) {
  return <div className={cn('px-6 py-4 border-t border-ink-100 bg-ink-50/50 rounded-b-2xl', className)}>{children}</div>
}