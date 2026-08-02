export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export interface Category {
    id: number;
    name: string;
    position: number;
}

export interface Feed {
    id: number;
    category_id: number;
    title: string;
    url: string;
    site_url: string | null;
    description: string | null;
    last_fetched_at: string | null;
    last_fetch_error: string | null;
}

export interface CategoryWithFeeds extends Category {
    feeds: Feed[];
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    flash: {
        status: string | null;
    };
};
