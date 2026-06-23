import { NavLink } from 'react-router-dom'
import { cn } from '../../lib/utils'

export default function Sidebar({ items, title, footer }) {
  return (
    <aside className="fixed left-0 top-14 bottom-0 w-60 bg-white border-r border-ink-100 flex flex-col z-30 hidden lg:flex">
      {title && (
        <div className="px-4 py-4 border-b border-ink-100">
          <p className="font-mono text-[10px] text-ink-400 uppercase tracking-widest">{title}</p>
        </div>
      )}
      <nav className="flex-1 overflow-y-auto scrollbar-thin px-3 py-3 space-y-0.5">
        {items.map((item) => {
          if (item.type === 'divider') return (
            <div key={item.key} className="my-2 border-t border-ink-100" />
          )
          if (item.type === 'label') return (
            <p key={item.key} className="px-3 pt-3 pb-1 font-mono text-[10px] text-ink-400 uppercase tracking-widest">
              {item.label}
            </p>
          )
          const Icon = item.icon
          return (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.end}
              className={({ isActive }) => cn(
                'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                isActive
                  ? 'bg-brand-50 text-brand'
                  : 'text-ink-600 hover:bg-ink-100 hover:text-ink-900',
              )}
            >
              {Icon && <Icon className="w-4 h-4 shrink-0" />}
              <span className="truncate">{item.label}</span>
              {item.badge && (
                <span className="ml-auto bg-accent-rose text-white text-xs font-bold px-1.5 py-0.5 rounded-full min-w-5 text-center">
                  {item.badge}
                </span>
              )}
            </NavLink>
          )
        })}
      </nav>
      {footer && <div className="border-t border-ink-100 p-4">{footer}</div>}
    </aside>
  )
}