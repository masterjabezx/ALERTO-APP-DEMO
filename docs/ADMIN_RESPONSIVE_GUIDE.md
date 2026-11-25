# ALERTO EOC — Responsive Design Guide

## 1. Breakpoint Strategy
Tailwind CSS v4 utility breakpoints govern all EOC command views across Desktop (`>= 1024px`, `lg`/`xl`), Tablet (`768px`, `md`), and Mobile (`390px`, base/`sm`):
- **Desktop (`>= 1024px`):** Persistent collapsible sidebar (`lg:w-64` expanded or `lg:w-20` icon rail persisted via `localStorage['alerto_sidebar_open']`) alongside offset main content (`lg:pl-64` / `lg:pl-20`).
- **Tablet & Mobile (`< 1024px`):** Fixed sidebar translates cleanly off-canvas (`-translate-x-full`) with backdrop overlay (`x-show="mobileMenuOpen"`) and accessible hamburger trigger inside the sticky `.eoc-topbar`.

## 2. Responsive Grid & Surface Adaptation
- **Dashboard Metrics:**
  - Core Incident KPI grid shifts cleanly `grid-cols-2 sm:grid-cols-4 lg:grid-cols-7`.
  - User Presence & Performance bar adapts `grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`.
  - Chart Analytics Grid adapts `grid-cols-1 md:grid-cols-2 lg:grid-cols-3`.
- **3-Panel Messaging Consoles (`admin/messages/index.blade.php` & `admin/communications/facebook-messenger/inbox.blade.php`):**
  - Root flex containers use `min-h-[680px] lg:h-[calc(100vh-5.5rem)]` and stack vertically on small screens (`flex-col lg:flex-row`) while switching to independent scrollable horizontal columns on desktop (`lg:w-80 xl:w-96`, `flex-1`, `lg:w-72`/`lg:w-80`).
- **Tabular Data (`components/table.blade.php`):**
  - All data tables are wrapped inside `.overflow-x-auto.alerto-scrollbar` containers so wide operational queues remain horizontally scrollable without causing page-level horizontal overflow.
- **Notification Center Drawer:**
  - Uses `max-w-md w-full` right-aligned slide-over panel with responsive internal padding (`p-4 sm:p-5`).

