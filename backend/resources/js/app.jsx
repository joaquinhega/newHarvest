import './bootstrap';
import '../css/app.css';

import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ConfirmProvider } from './Contexts/ConfirmContext';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'New Harvest';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(
            <ConfirmProvider>
                <App {...props} />
            </ConfirmProvider>
        );
    },
    progress: {
        color: '#8A2E93',
    },
});