export * from './auth';
export * from './navigation';
export * from './ui';
export * from './models';

import type { Auth } from './auth';

export interface FlashMessages {
    success?: string;
    error?: string;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    flash: FlashMessages;
    locale: string;
    [key: string]: unknown;
};
