<script setup lang="ts">
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Drawer from '@/Components/Drawer.vue';
import EntryList from '@/Components/EntryList.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from '../Entries/Partials/Sidebar.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import type {
    CategoryWithFeedCounts,
    Entry,
    EntryFilters,
    FeedWithCategory,
    FeedWithUnreadCount,
    Paginated,
    Tag,
} from '@/types';

const props = defineProps<{
    feed: FeedWithCategory;
    entries: Paginated<Entry>;
    sidebar: CategoryWithFeedCounts[];
    tags: Tag[];
    filters: EntryFilters;
    unreadCount: number;
}>();

const page = usePage();
const flashStatus = computed(() => page.props.flash.status);

const totalUnread = computed(() =>
    props.sidebar.reduce(
        (sum, category) =>
            sum + category.feeds.reduce((s, feed) => s + feed.unread_count, 0),
        0,
    ),
);

const sidebarOpen = ref(false);

type Query = Record<string, string | number | undefined>;

function visit(query: Query) {
    sidebarOpen.value = false;

    router.get(route('entries.index'), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function goToFeed(feed: FeedWithUnreadCount) {
    sidebarOpen.value = false;

    if (feed.id === props.feed.id) return;

    router.visit(route('feeds.show', feed.id));
}

function isActive(key: keyof EntryFilters, value?: string | number): boolean {
    if (key === 'feed_id') {
        return value === props.feed.id;
    }

    if (value === undefined) {
        return !props.filters[key];
    }

    return props.filters[key] === String(value);
}

function feedQuery(overrides: Query) {
    router.get(
        route('feeds.show', props.feed.id),
        { ...props.filters, ...overrides },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function toggleFilter(key: 'unread' | 'starred') {
    feedQuery({ [key]: props.filters[key] ? undefined : 1 });
}

function toggleStar(entry: Entry) {
    router.patch(
        route('entries.star', entry.id),
        {},
        { preserveState: true, preserveScroll: true },
    );
}

function markAllRead() {
    const noun = props.unreadCount === 1 ? 'entry' : 'entries';

    if (!confirm(`Mark ${props.unreadCount} ${noun} as read?`)) {
        return;
    }

    router.patch(
        route('feeds.mark-all-read', props.feed.id),
        { ...props.filters },
        { preserveState: true, preserveScroll: true },
    );
}

// Search
const searchTerm = ref(props.filters.q ?? '');

function submitSearch() {
    feedQuery({ q: searchTerm.value || undefined });
}
</script>

<template>
    <Head :title="feed.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <Link
                        :href="route('entries.index')"
                        class="text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                    >
                        &larr; Back to Reader
                    </Link>
                    <h2
                        class="mt-1 text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
                    >
                        {{ feed.title }}
                        <span
                            v-if="unreadCount"
                            class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400"
                        >
                            {{ unreadCount }} unread
                        </span>
                    </h2>
                </div>
                <Link
                    :href="route('feeds.index')"
                    class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    Manage feed &rarr;
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div
                    v-if="flashStatus"
                    class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900/50 dark:text-green-200"
                >
                    {{ flashStatus }}
                </div>

                <div
                    v-if="feed.description || feed.site_url || feed.last_fetch_error || feed.last_fetched_at"
                    class="mb-6 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800"
                >
                    <p
                        v-if="feed.description"
                        class="text-sm text-gray-600 dark:text-gray-400"
                    >
                        {{ feed.description }}
                    </p>
                    <div
                        class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm"
                    >
                        <a
                            v-if="feed.site_url"
                            :href="feed.site_url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                        >
                            Visit site &rarr;
                        </a>
                        <span
                            v-if="feed.last_fetch_error"
                            class="text-red-600 dark:text-red-400"
                        >
                            {{ feed.last_fetch_error }}
                        </span>
                        <span
                            v-else-if="feed.last_fetched_at"
                            class="text-gray-500 dark:text-gray-400"
                        >
                            Last checked
                            {{ new Date(feed.last_fetched_at).toLocaleString() }}
                        </span>
                        <span v-else class="text-gray-500 dark:text-gray-400">
                            Waiting for first fetch&hellip;
                        </span>
                    </div>
                </div>

                <button
                    type="button"
                    class="mb-4 flex w-full items-center gap-2 rounded-lg bg-white p-3 text-left text-sm font-medium text-gray-700 shadow-sm md:hidden dark:bg-gray-800 dark:text-gray-300"
                    @click="sidebarOpen = true"
                >
                    <svg
                        class="h-5 w-5 shrink-0"
                        stroke="currentColor"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                    </svg>
                    <span class="truncate">{{ feed.category.name }}</span>
                </button>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-[220px_1fr]">
                    <!-- Sidebar -->
                    <aside
                        class="hidden h-fit overflow-hidden rounded-lg bg-white p-4 shadow-sm md:block dark:bg-gray-800"
                    >
                        <Sidebar
                            :sidebar="sidebar"
                            :tags="tags"
                            :filters="filters"
                            :total-unread="totalUnread"
                            :is-active="isActive"
                            :visit="visit"
                            :go-to-feed="goToFeed"
                        />
                    </aside>

                    <!-- Entry list -->
                    <div
                        class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800"
                    >
                        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex flex-wrap items-center gap-2">
                                <form
                                    @submit.prevent="submitSearch"
                                    class="min-w-[200px] flex-1"
                                >
                                    <TextInput
                                        v-model="searchTerm"
                                        type="search"
                                        placeholder="Search this feed&hellip;"
                                        class="block w-full"
                                    />
                                </form>
                                <button
                                    type="button"
                                    class="rounded-md px-3 py-2 text-sm"
                                    :class="
                                        isActive('unread', 1)
                                            ? 'bg-indigo-50 font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300'
                                            : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                                    "
                                    @click="toggleFilter('unread')"
                                >
                                    Unread
                                </button>
                                <button
                                    type="button"
                                    class="rounded-md px-3 py-2 text-sm"
                                    :class="
                                        isActive('starred', 1)
                                            ? 'bg-indigo-50 font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300'
                                            : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                                    "
                                    @click="toggleFilter('starred')"
                                >
                                    Starred
                                </button>
                                <SecondaryButton
                                    v-if="unreadCount > 0"
                                    @click="markAllRead"
                                >
                                    Mark all as read
                                </SecondaryButton>
                            </div>
                        </div>

                        <EntryList
                            :entries="entries"
                            :toggle-star="toggleStar"
                            :show-feed-name="false"
                            empty-message="No entries yet."
                        />
                    </div>
                </div>
            </div>
        </div>

        <Drawer :show="sidebarOpen" title="Filters" @close="sidebarOpen = false">
            <Sidebar
                :sidebar="sidebar"
                :tags="tags"
                :filters="filters"
                :total-unread="totalUnread"
                :is-active="isActive"
                :visit="visit"
                :go-to-feed="goToFeed"
            />
        </Drawer>
    </AuthenticatedLayout>
</template>
