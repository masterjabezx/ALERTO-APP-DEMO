/**
 * ALERTO APP — Lucide Icons Helper
 * Initializes and dynamically parses data-lucide icon nodes.
 */

import { createIcons, icons } from 'lucide';

export function initializeIcons(root = document) {
    createIcons({
        icons,
        nameAttr: 'data-lucide',
        attrs: {
            'stroke-width': 2,
            class: 'lucide-icon',
        },
        root: root,
    });
}

// Auto-run on DOMContentLoaded
if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        initializeIcons();
    });
}

export default initializeIcons;

