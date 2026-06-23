import { useEffect, useRef } from 'react'
import { createPortal } from 'react-dom'
import { X } from 'lucide-react'
import { cn } from '../../lib/utils'

const sizes = {
  sm:  'max-w-md',
  md:  'max-w-lg',
  lg:  'max-w-2xl',
  xl:  'max-w-4xl',
  full:'max-w-6xl',
}

export default function Modal({ isOpen, onClose, title, children, size = 'md', className }) {
  const overlayRef = useRef(null)

  useEffect(() => {
    if (!isOpen) return
    const handleKey = (e) => { if (e.key === 'Escape') onClose?.() }
    document.addEventListener('keydown', handleKey)
    document.body.style.overflow = 'hidden'
    return () => {
      document.removeEventListener('keydown', handleKey)
      document.body.style.overflow = ''
    }
  }, [isOpen, onClose])

  if (!isOpen) return null

  return createPortal(
    <div
      ref={overlayRef}
      onClick={(e) => { if (e.target === overlayRef.current) onClose?.() }}
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink-900/50 backdrop-blur-sm"
    >
      <div className={cn(
        'relative w-full bg-white rounded-2xl shadow-xl animate-slide-up',
        sizes[size],
        className,
      )}>
        {title && (
          <div className="flex items-center justify-between px-6 py-4 border-b border-ink-100">
            <h2 className="font-display text-lg font-bold text-ink-900">{title}</h2>
            <button
              onClick={onClose}
              className="p-1.5 rounded-lg hover:bg-ink-100 text-ink-500 hover:text-ink-900 transition-colors"
            >
              <X className="w-4 h-4" />
            </button>
          </div>
        )}
        <div className="overflow-y-auto max-h-[80vh]">
          {children}
        </div>
      </div>
    </div>,
    document.body,
  )
}