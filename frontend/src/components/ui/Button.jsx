import { forwardRef } from 'react'
import { cn } from '../../lib/utils'
import Spinner from './Spinner'

const variants = {
  primary:   'bg-ink-900 text-white hover:bg-brand shadow-sm',
  brand:     'bg-brand text-white hover:bg-brand-dark shadow-glow',
  secondary: 'bg-white border border-ink-200 text-ink-800 hover:border-ink-400',
  ghost:     'text-ink-600 hover:bg-ink-100 hover:text-ink-900',
  danger:    'bg-accent-rose text-white hover:bg-red-700',
  outline:   'border border-brand text-brand hover:bg-brand-50',
}

const sizes = {
  sm:  'px-3 py-1.5 text-xs rounded-lg gap-1.5',
  md:  'px-4 py-2 text-sm rounded-xl gap-2',
  lg:  'px-6 py-3 text-sm rounded-xl gap-2',
  xl:  'px-8 py-4 text-base rounded-2xl gap-2.5',
}

const Button = forwardRef(function Button(
  { variant = 'primary', size = 'md', className, children, isLoading, disabled, leftIcon, rightIcon, ...props },
  ref,
) {
  return (
    <button
      ref={ref}
      disabled={disabled || isLoading}
      className={cn(
        'inline-flex items-center justify-center font-semibold transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/50 disabled:opacity-50 disabled:cursor-not-allowed',
        variants[variant],
        sizes[size],
        className,
      )}
      {...props}
    >
      {isLoading ? (
        <Spinner size="sm" className="border-white/30 border-t-white" />
      ) : leftIcon}
      {children}
      {!isLoading && rightIcon}
    </button>
  )
})

export default Button