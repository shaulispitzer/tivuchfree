// export * from './auth';
// export * from './navigation';
// export * from './ui';

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

type PaginationLink = {
    active: boolean;
    label: string;
    url: string | null;
};

export type Paginator<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    from: number | null;
    last_page: number;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
    meta?: {
        current_page: number;
        from: number | null;
        last_page: number;
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
};
