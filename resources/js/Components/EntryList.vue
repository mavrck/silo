<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { formatDuration } from '@/utils/duration';
import type { Entry, Paginated } from '@/types';

withDefaults(
    defineProps<{
        entries: Paginated<Entry>;
        toggleStar: (entry: Entry) => void;
        deleteEntry: (entry: Entry) => void;
        showFeedName?: boolean;
        emptyMessage?: string;
    }>(),
    {
        showFeedName: true,
        emptyMessage: 'No entries here. Subscribe to a feed or change filters.',
    },
);

function formatDate(value: string | null): string {
    if (!value) return '';

    return new Date(value).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <p
        v-if="entries.data.length === 0"
        class="p-6 text-sm text-gray-500 dark:text-gray-400"
    >
        {{ emptyMessage }}
    </p>

    <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700">
        <li v-for="entry in entries.data" :key="entry.id">
            <div
                class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40"
            >
                <span
                    class="mt-2 h-2 w-2 shrink-0 rounded-full"
                    :class="entry.is_read ? 'bg-transparent' : 'bg-indigo-500'"
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
                        <template v-if="showFeedName">
                            {{ entry.feed?.title }}
                            &middot;
                        </template>
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
                <button
                    type="button"
                    class="shrink-0 text-base leading-none text-gray-300 hover:text-red-500 dark:text-gray-600"
                    aria-label="Delete"
                    @click="deleteEntry(entry)"
                >
                    &#128465;
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
</template>
