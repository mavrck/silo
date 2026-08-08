<script setup lang="ts">
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Drawer from '@/Components/Drawer.vue';
import EntryList from '@/Components/EntryList.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Sidebar from './Partials/Sidebar.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import type {
    CategoryWithFeedCounts,
    Entry,
    EntryFilters,
    FeedWithUnreadCount,
    Paginated,
    Tag,
} from '@/types';

const props = defineProps<{
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

const currentFilterLabel = computed(() => {
    if (props.filters.starred) return 'Starred';
    if (props.filters.unread) return `Unread (${totalUnread.value})`;

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

function goToFeed(feed: FeedWithUnreadCount) {
    sidebarOpen.value = false;
    router.visit(route('feeds.show', feed.id));
}

function toggleStar(entry: Entry) {
    router.patch(
        route('entries.star', entry.id),
        {},
        { preserveState: true, preserveScroll: true },
    );
}

function deleteEntry(entry: Entry) {
    if (!confirm(`Delete "${entry.title ?? 'this entry'}"?`)) {
        return;
    }

    router.delete(route('entries.destroy', entry.id), {
        preserveState: true,
        preserveScroll: true,
    });
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

// Search
const searchTerm = ref(props.filters.q ?? '');

function submitSearch() {
    visit({ ...props.filters, q: searchTerm.value || undefined });
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
                            </div>
                        </div>

                        <EntryList
                            :entries="entries"
                            :toggle-star="toggleStar"
                            :delete-entry="deleteEntry"
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
