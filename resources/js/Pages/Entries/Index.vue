<script setup lang="ts">
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import type {
    CategoryWithFeedCounts,
    Entry,
    EntryFilters,
    Paginated,
} from '@/types';

const props = defineProps<{
    entries: Paginated<Entry>;
    sidebar: CategoryWithFeedCounts[];
    filters: EntryFilters;
}>();

const totalUnread = computed(() =>
    props.sidebar.reduce(
        (sum, category) =>
            sum + category.feeds.reduce((s, feed) => s + feed.unread_count, 0),
        0,
    ),
);

function categoryUnread(category: CategoryWithFeedCounts): number {
    return category.feeds.reduce((sum, feed) => sum + feed.unread_count, 0);
}

function visit(query: Record<string, string | number | undefined>) {
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

function formatDate(value: string | null): string {
    if (!value) return '';

    return new Date(value).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
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
            <div class="mx-auto grid max-w-6xl grid-cols-1 gap-6 px-4 sm:px-6 md:grid-cols-[220px_1fr] lg:px-8">
                <!-- Sidebar -->
                <aside
                    class="h-fit overflow-hidden rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800"
                >
                    <nav class="space-y-4 text-sm">
                        <div class="space-y-1">
                            <button
                                type="button"
                                class="block w-full rounded px-2 py-1 text-left"
                                :class="
                                    isActive('unread')
                                        ? 'bg-indigo-50 font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300'
                                        : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/50'
                                "
                                @click="visit({})"
                            >
                                All entries
                            </button>
                            <button
                                type="button"
                                class="block w-full rounded px-2 py-1 text-left"
                                :class="
                                    isActive('unread', 1)
                                        ? 'bg-indigo-50 font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300'
                                        : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/50'
                                "
                                @click="visit({ unread: 1 })"
                            >
                                Unread ({{ totalUnread }})
                            </button>
                            <button
                                type="button"
                                class="block w-full rounded px-2 py-1 text-left"
                                :class="
                                    isActive('starred', 1)
                                        ? 'bg-indigo-50 font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300'
                                        : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/50'
                                "
                                @click="visit({ starred: 1 })"
                            >
                                Starred
                            </button>
                        </div>

                        <div
                            v-for="category in sidebar"
                            :key="category.id"
                            class="space-y-1"
                        >
                            <p
                                class="px-2 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500"
                            >
                                {{ category.name }}
                                <span v-if="categoryUnread(category)">
                                    ({{ categoryUnread(category) }})
                                </span>
                            </p>
                            <button
                                v-for="feed in category.feeds"
                                :key="feed.id"
                                type="button"
                                class="flex w-full items-center justify-between rounded px-2 py-1 text-left"
                                :class="
                                    isActive('feed_id', feed.id)
                                        ? 'bg-indigo-50 font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300'
                                        : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/50'
                                "
                                @click="visit({ feed_id: feed.id })"
                            >
                                <span class="truncate">{{ feed.title }}</span>
                                <span
                                    v-if="feed.unread_count"
                                    class="ml-2 shrink-0 text-xs text-gray-400"
                                >
                                    {{ feed.unread_count }}
                                </span>
                            </button>
                        </div>
                    </nav>
                </aside>

                <!-- Entry list -->
                <div
                    class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800"
                >
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
                                        class="truncate"
                                        :class="
                                            entry.is_read
                                                ? 'text-gray-500 dark:text-gray-400'
                                                : 'font-medium text-gray-900 dark:text-gray-100'
                                        "
                                    >
                                        {{ entry.title }}
                                    </p>
                                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                        {{ entry.feed?.title }}
                                        &middot;
                                        {{ formatDate(entry.published_at) }}
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
    </AuthenticatedLayout>
</template>
