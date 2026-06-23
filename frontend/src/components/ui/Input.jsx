import { forwardRef } from 'react'
import { cn } from '../../lib/utils'

const Input = forwardRef(function Input(
  { label, error, hint, leftIcon, rightIcon, className, wrapperClassName, ...props },
  ref,
) {
  return (
    <div className={cn('flex flex-col gap-1.5', wrapperClassName)}>
      {label && (
        <label className="text-sm font-medium text-ink-700">
          {label}
          {props.required && <span className="text-accent-rose ml-1">*</span>}
        </label>
      )}
      <div className="relative">
        {leftIcon && (
          <div className="absolute left-3 top-1/2 -translate-y-1/2 text-ink-400 pointer-events-none">
            {leftIcon}
          </div>
        )}
        <input
          ref={ref}
          className={cn(
            'w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-ink-900 placeholder:text-ink-400 transition-all',
            'focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand',
            error ? 'border-accent-rose ring-1 ring-accent-rose/20' : 'border-ink-200 hover:border-ink-300',
            leftIcon && 'pl-10',
            rightIcon && 'pr-10',
            className,
          )}
          {...props}
        />
        {rightIcon && (
          <div className="absolute right-3 top-1/2 -translate-y-1/2 text-ink-400">
            {rightIcon}
          </div>
        )}
      </div>
      {error && <p className="text-xs text-accent-rose">{error}</p>}
      {hint && !error && <p className="text-xs text-ink-400">{hint}</p>}
    </div>
  )
})

export default Input