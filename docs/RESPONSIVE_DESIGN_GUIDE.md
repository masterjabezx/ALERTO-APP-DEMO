# ALERTO APP — Responsive Design & Form-Factor Guide

## 1. Device Targets & Priority Matrix

| Device Profile | Screen Resolution | Primary Persona | Navigation Model |
|---|---|---|---|
| **Mobile Smartphone** | `360px - 480px` | Citizen, Field Responders | Bottom Navigation Bar (44px min touch target) |
| **Tablet (Portrait / Landscape)** | `768px - 1024px` | Response Team Leader, Dispatch Desk | Top header with compact responsive grid |
| **Laptop & Desktop** | `1280px - 1920px` | Office Dispatcher, Municipal Admin | Collapsible Dark Navy Sidebar + Expanded GIS Canvas |
| **Command Center Wall Display** | `2560px - 4K Ultra-wide` | Hinoba-an LDRRMO EOC Watch-standers | Multi-column tactical layout with live GIS map |

---

## 2. Breakpoint Conventions (Tailwind v4)

- `sm: 640px` — Mobile cards switch to 2-column layouts; dialog modals center.
- `md: 768px` — Tablet navigation appears; desktop top bars replace bottom tabs on citizen portal.
- `lg: 1024px` — Admin collapsible sidebar activates; 3-column GIS layout renders.
- `xl: 1280px` — Full command center data grid expansion.

---

## 3. Touch Ergonomics & Accessibility

- **Minimum Touch Target:** `touch-target` class ensures minimum `44px x 44px` clickable dimensions on all buttons, select fields, and radio cards.
- **Safe Area Insets:** Layouts account for mobile bottom system gestures (`pb-20 md:pb-0`).
- **No Overflow Rule:** Horizontal scrollbars are strictly forbidden on main viewport roots (`w-full overflow-x-hidden` or localized table scrolling via `.alerto-scrollbar`).

