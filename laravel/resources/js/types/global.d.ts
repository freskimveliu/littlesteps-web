import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import type { route as ziggyRoute } from 'ziggy-js';

declare global {
    const route: typeof ziggyRoute;
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof ziggyRoute;
    }
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps {
        appName: string;
        auth: {
            user: {
                id: number;
                name: string;
                email: string;
            } | null;
        };
        flash: {
            success: string | null;
            error: string | null;
        };
    }
}

export {};
