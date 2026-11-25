# ALERTO APP — Frontend Architecture & Component Directory

## 1. Directory Structure

```
resources/
├── css/
│   └── app.css               # Design tokens, Tailwind @theme, custom scrollbars, glass utilities
├── js/
│   ├── animations.js         # GSAP animation engine with reduced-motion protection
│   ├── icons.js              # Lucide icons initializers and dynamic DOM scanner
│   ├── app.js                # Alpine.js bootstrapper, global directives, event listeners
│   └── bootstrap.js          # Axios & network headers
└── views/
    ├── components/           # Reusable Blade component library
    │   ├── layouts/          # Portal layouts accessible via <x-layouts.*>
    │   │   ├── citizen.blade.php
    │   │   ├── personnel.blade.php
    │   │   ├── office.blade.php
    │   │   └── admin.blade.php
    │   ├── button.blade.php
    │   ├── input.blade.php
    │   ├── password-input.blade.php
    │   ├── select.blade.php
    │   ├── card.blade.php
    │   ├── stat-card.blade.php
    │   ├── badge.blade.php
    │   ├── modal.blade.php
    │   ├── confirm-dialog.blade.php
    │   ├── dropdown.blade.php
    │   ├── table.blade.php
    │   ├── empty-state.blade.php
    │   ├── skeleton.blade.php
    │   ├── toast.blade.php
    │   ├── incident-card.blade.php
    │   ├── file-upload.blade.php
    │   ├── search-bar.blade.php
    │   ├── filter-panel.blade.php
    │   ├── map-container.blade.php
    │   └── icon.blade.php
    ├── dashboards/           # Portal dashboard implementations
    │   ├── citizen.blade.php
    │   ├── personnel.blade.php
    │   ├── office.blade.php
    │   └── admin.blade.php
    └── layouts/              # Root auth layout & legacy includes
        └── auth.blade.php
```

---

## 2. Reusable Component Usage Guide

### `<x-button>`
```html
<x-button variant="primary|secondary|emergency|success|warning|outline|ghost" size="sm|md|lg" icon="send">
    Submit Report
</x-button>
```

### `<x-stat-card>`
```html
<x-stat-card 
    title="Critical Incidents" 
    value="3" 
    variant="red" 
    icon="flame" 
    trend="2 active dispatches" 
    trendDirection="down" 
/>
```

### `<x-incident-card>`
```html
<x-incident-card 
    category="Medical Emergency"
    reference="INC-HNO-0042"
    location="Poblacion 1, Hinoba-an"
    status="dispatched"
    priority="critical"
    reportedAt="8 mins ago"
>
    <x-button size="sm" variant="success">Accept Dispatch</x-button>
</x-incident-card>
```

### `<x-modal>` & `<x-confirm-dialog>`
```html
<button x-data x-on:click="$dispatch('open-modal', 'dispatch-confirm')">Open</button>

<x-confirm-dialog 
    name="dispatch-confirm"
    title="Confirm Responder Dispatch"
    message="This will alert MDRRMO Ambulance Unit 1."
    confirmText="Dispatch Now"
    variant="emergency"
/>
```

### `<x-toast>`
```javascript
// Trigger from anywhere in Alpine or JS:
$dispatch('show-toast', {
    title: 'Incident Verified',
    message: 'Dispatched to BFP Hinoba-an Station.',
    type: 'success', // success | error | warning | info
    duration: 4000
});
```

