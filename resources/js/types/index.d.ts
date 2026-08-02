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

export interface FeedWithUnreadCount extends Feed {
    unread_count: number;
}

export interface CategoryWithFeedCounts extends Category {
    feeds: FeedWithUnreadCount[];
}

export interface EntryFeed {
    id: number;
    title: string;
    category_id?: number;
    site_url?: string | null;
}

export interface Tag {
    id: number;
    name: string;
}

export interface TagWithCount extends Tag {
    entries_count: number;
}

export interface Entry {
    id: number;
    feed_id: number;
    guid: string;
    url: string | null;
    title: string | null;
    author: string | null;
    content: string | null;
    published_at: string | null;
    is_read: boolean;
    read_at: string | null;
    is_starred: boolean;
    feed?: EntryFeed;
    tags?: Tag[];
}

export interface SavedSearchFilters {
    q?: string;
    feed_id?: number;
    category_id?: number;
    tag_id?: number;
    unread?: boolean;
    starred?: boolean;
}

export interface SavedSearch {
    id: number;
    name: string;
    filters: SavedSearchFilters;
}

export interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    total: number;
}

export interface EntryFilters {
    feed_id?: string;
    category_id?: string;
    tag_id?: string;
    q?: string;
    unread?: string;
    starred?: string;
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
