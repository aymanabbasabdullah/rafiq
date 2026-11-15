import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import type { ComponentType } from 'react';
import { createRoot } from 'react-dom/client';
import { DirectionProvider } from '@/lib/direction';

type PageModule = { default: ComponentType };

const pages = import.meta.glob<PageModule>('./pages/**/*.tsx');

void createInertiaApp({
    // Browser tab titles stay neutral so a shared screen does not reveal the topic (PRIV-021).
    title: () => 'Rafiq | رفيق',
    resolve: async (name) => {
        const page = pages[`./pages/${name}.tsx`];

        if (page === undefined) {
            throw new Error(`Inertia page not found: ${name}`);
        }

        return (await page()).default;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <App {...props}>
                {({ Component, props: pageProps, key }) => (
                    <DirectionProvider>
                        <Component key={key} {...pageProps} />
                    </DirectionProvider>
                )}
            </App>,
        );
    },
    progress: {
        color: '#0f6b62',
        showSpinner: false,
    },
});
