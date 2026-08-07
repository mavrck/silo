<script setup lang="ts">
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Drawer from '@/Components/Drawer.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from './Partials/Sidebar.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { formatDuration } from '@/utils/duration';
import type {
    CategoryWithFeedCounts,
    Entry,
    EntryFilters,
    Paginated,
    SavedSearch,
    Tag,
} from '@/types';

const props = defineProps<{
    entries: Paginated<Entry>;
    sidebar: CategoryWithFeedCounts[];
    tags: Tag[];
    savedSearches: SavedSearch[];
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

const currentFilterLabel = computed(() => {
    if (props.filters.starred) return 'Starred';
    if (props.filters.unread) return `Unread (${totalUnread.value})`;

    if (props.filters.feed_id) {
        const feedId = Number(props.filters.feed_id);
        for (const category of props.sidebar) {
            const feed = category.feeds.find((f) => f.id === feedId);
            if (feed) return feed.title;
        }
    }

    if (props.filters.category_id) {
        const categoryId = Number(props.filters.category_id);
        const category = props.sidebar.find((c) => c.id === categoryId);
        if (category) return category.name;
    }

    if (props.filters.tag_id) {
        const tagId = Number(props.filters.tag_id);
        const tag = props.tags.find((t) => t.id === tagId);
        if (tag) return `#${tag.name}`;
    }

    if (props.filters.q) return `Search: "${props.filters.q}"`;

    return 'All entries';
});

type Query = Record<string, string | number | undefined>;

function visit(query: Query) {
    sidebarOpen.value = false;

    router.get(route('entries.index'), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function isActive(key: keyof EntryFilters, value?: string | number): boolean {
    if (value === undefined) {
        return !props.filters[key];
    }

    return props.filters[key] === String(value);
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

    router.patch(route('entries.mark-all-read'), { ...props.filters }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function formatDate(value: string | null): string {
    if (!value) return '';

    return new Date(value).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}

// Search
const searchTerm = ref(props.filters.q ?? '');

function submitSearch() {
    visit({ ...props.filters, q: searchTerm.value || undefined });
}

// Saved searches
const activeFilterCount = computed(
    () => Object.values(props.filters).filter(Boolean).length,
);

function applySavedSearch(search: SavedSearch) {
    const query: Query = {};
    const { q, feed_id, category_id, tag_id, unread, starred } = search.filters;
    if (q) query.q = q;
    if (feed_id) query.feed_id = feed_id;
    if (category_id) query.category_id = category_id;
    if (tag_id) query.tag_id = tag_id;
    if (unread) query.unread = 1;
    if (starred) query.starred = 1;

    searchTerm.value = q ?? '';
    visit(query);
}

function deleteSavedSearch(search: SavedSearch) {
    sidebarOpen.value = false;

    router.delete(route('saved-searches.destroy', search.id), {
        preserveScroll: true,
    });
}

const showSaveForm = ref(false);
const saveForm = useForm({
    name: '',
    q: '',
    feed_id: '' as string | number,
    category_id: '' as string | number,
    tag_id: '' as string | number,
    unread: '' as string | number,
    starred: '' as string | number,
});

function openSaveForm() {
    saveForm.reset();
    saveForm.q = props.filters.q ?? '';
    saveForm.feed_id = props.filters.feed_id ?? '';
    saveForm.category_id = props.filters.category_id ?? '';
    saveForm.tag_id = props.filters.tag_id ?? '';
    saveForm.unread = props.filters.unread ?? '';
    saveForm.starred = props.filters.starred ?? '';
    showSaveForm.value = true;
}

function submitSaveForm() {
    saveForm.post(route('saved-searches.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showSaveForm.value = false;
        },
    });
}
</script>

<template>
    <Head title="Reader" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                Reader
                <span
                    v-if="totalUnread"
                    class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400"
                >
                    {{ totalUnread }} unread
                </span>
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div
                    v-if="flashStatus"
                    class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900/50 dark:text-green-200"
                >
                    {{ flashStatus }}
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
                    <span class="truncate">{{ currentFilterLabel }}</span>
                </button>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-[220px_1fr]">
                    <!-- Sidebar -->
                    <aside
                        class="hidden h-fit overflow-hidden rounded-lg bg-white p-4 shadow-sm md:block dark:bg-gray-800"
                    >
                        <Sidebar
                            :sidebar="sidebar"
                            :tags="tags"
                            :saved-searches="savedSearches"
                            :filters="filters"
                            :total-unread="totalUnread"
                            :is-active="isActive"
                            :visit="visit"
                            :apply-saved-search="applySavedSearch"
                            :delete-saved-search="deleteSavedSearch"
                        />
                    </aside>

                    <!-- Entry list -->
                    <div
                        class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800"
                    >
                        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex items-center gap-2">
                                <form @submit.prevent="submitSearch" class="flex-1">
                                    <TextInput
                                        v-model="searchTerm"
                                        type="search"
                                        placeholder="Search your entries&hellip;"
                                        class="block w-full"
                                    />
                                </form>
                                <SecondaryButton
                                    v-if="unreadCount > 0"
                                    @click="markAllRead"
                                >
                                    Mark all as read
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="activeFilterCount"
                                    @click="openSaveForm"
                                >
                                    Save search
                                </SecondaryButton>
                            </div>

                            <form
                                v-if="showSaveForm"
                                @submit.prevent="submitSaveForm"
                                class="mt-3 flex items-center gap-2"
                            >
                                <TextInput
                                    v-model="saveForm.name"
                                    placeholder="Name this search"
                                    class="block flex-1"
                                    autofocus
                                />
                                <PrimaryButton :disabled="saveForm.processing">
                                    Save
                                </PrimaryButton>
                                <button
                                    type="button"
                                    class="text-sm text-gray-500 dark:text-gray-400"
                                    @click="showSaveForm = false"
                                >
                                    Cancel
                                </button>
                            </form>
                        </div>

                        <p
                            v-if="entries.data.length === 0"
                            class="p-6 text-sm text-gray-500 dark:text-gray-400"
                        >
                            No entries here. Subscribe to a feed or change filters.
                        </p>

                        <ul
                            v-else
                            class="divide-y divide-gray-200 dark:divide-gray-700"
                        >
                            <li v-for="entry in entries.data" :key="entry.id">
                                <div
                                    class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40"
                                >
                                    <span
                                        class="mt-2 h-2 w-2 shrink-0 rounded-full"
                                        :class="
                                            entry.is_read
                                                ? 'bg-transparent'
                                                : 'bg-indigo-500'
                                        "
                                    />
                                    <Link
                                        :href="route('entries.show', entry.id)"
                                        class="min-w-0 flex-1"
                                    >
                                        <p
                                            class="flex items-center gap-1.5 truncate"
                                            :class="
                                                entry.is_read
                                                    ? 'text-gray-500 dark:text-gray-400'
                                                    : 'font-medium text-gray-900 dark:text-gray-100'
                                            "
                                        >
                                            <span
                                                v-if="entry.enclosure_url"
                                                class="shrink-0 text-amber-600 dark:text-amber-400"
                                                title="Podcast episode"
                                            >
                                                &#9658;
                                            </span>
                                            <span class="truncate">{{ entry.title }}</span>
                                        </p>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                            {{ entry.feed?.title }}
                                            &middot;
                                            {{ formatDate(entry.published_at) }}
                                            <template v-if="entry.duration_seconds">
                                                &middot; {{ formatDuration(entry.duration_seconds) }}
                                            </template>
                                            <template v-if="entry.tags?.length">
                                                &middot;
                                                <span
                                                    v-for="tag in entry.tags"
                                                    :key="tag.id"
                                                    class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 text-gray-500 dark:bg-gray-700 dark:text-gray-400"
                                                >
                                                    #{{ tag.name }}
                                                </span>
                                            </template>
                                        </p>
                                    </Link>
                                    <button
                                        type="button"
                                        class="shrink-0 text-lg leading-none"
                                        :class="
                                            entry.is_starred
                                                ? 'text-amber-500'
                                                : 'text-gray-300 hover:text-gray-400 dark:text-gray-600'
                                        "
                                        :aria-label="entry.is_starred ? 'Unstar' : 'Star'"
                                        @click="toggleStar(entry)"
                                    >
                                        &#9733;
                                    </button>
                                </div>
                            </li>
                        </ul>

                        <div
                            v-if="entries.last_page > 1"
                            class="flex justify-center gap-1 border-t border-gray-200 p-4 dark:border-gray-700"
                        >
                            <Link
                                v-for="link in entries.links"
                                :key="link.label"
                                :href="link.url ?? '#'"
                                preserve-state
                                preserve-scroll
                                class="rounded px-3 py-1 text-sm"
                                :class="[
                                    link.active
                                        ? 'bg-indigo-600 text-white'
                                        : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                                    !link.url && 'pointer-events-none opacity-40',
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Drawer :show="sidebarOpen" title="Filters" @close="sidebarOpen = false">
            <Sidebar
                :sidebar="sidebar"
                :tags="tags"
                :saved-searches="savedSearches"
                :filters="filters"
                :total-unread="totalUnread"
                :is-active="isActive"
                :visit="visit"
                :apply-saved-search="applySavedSearch"
                :delete-saved-search="deleteSavedSearch"
            />
        </Drawer>
    </AuthenticatedLayout>
</template>
