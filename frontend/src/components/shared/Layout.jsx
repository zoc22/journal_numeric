import { Outlet } from 'react-router-dom'
import Navbar from './Navbar'

export function PublicLayout() {
  return (
    <div className="min-h-screen bg-ink-50">
      <Navbar />
      <main className="pt-14">
        <Outlet />
      </main>
    </div>
  )
}

export function DashboardLayout({ sidebar }) {
  return (
    <div className="min-h-screen bg-ink-50">
      <Navbar />
      {sidebar}
      <main className="pt-14 lg:pl-60">
        <div className="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">
          <Outlet />
        </div>
      </main>
    </div>
  )
}