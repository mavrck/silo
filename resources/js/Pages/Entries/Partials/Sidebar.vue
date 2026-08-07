<script setup lang="ts">
import type { CategoryWithFeedCounts, EntryFilters, Tag } from '@/types';

defineProps<{
    sidebar: CategoryWithFeedCounts[];
    tags: Tag[];
    filters: EntryFilters;
    totalUnread: number;
    isActive: (key: keyof EntryFilters, value?: string | number) => boolean;
    visit: (query: Record<string, string | number | undefined>) => void;
}>();

function categoryUnread(category: CategoryWithFeedCounts): number {
    return category.feeds.reduce((sum, feed) => sum + feed.unread_count, 0);
}
</script>

<template>
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

        <div v-if="tags.length" class="space-y-1">
            <p
                class="px-2 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500"
            >
                Tags
            </p>
            <button
                v-for="tag in tags"
                :key="tag.id"
                type="button"
                class="block w-full truncate rounded px-2 py-1 text-left"
                :class="
                    isActive('tag_id', tag.id)
                        ? 'bg-indigo-50 font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300'
                        : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/50'
                "
                @click="visit({ tag_id: tag.id })"
            >
                #{{ tag.name }}
            </button>
        </div>
    </nav>
</template>
