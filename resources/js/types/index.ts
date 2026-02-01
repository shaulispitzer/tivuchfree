export * from './auth';
export * from './navigation';
export * from './ui';

import type { User } from './auth';

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    user: User | null;
    locale?: string;
    sidebarOpen: boolean;
    [key: string]: unknown;
};
