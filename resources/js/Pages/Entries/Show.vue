<script setup lang="ts">
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { formatDuration } from '@/utils/duration';
import type { Entry, Tag } from '@/types';

const props = defineProps<{
    entry: Entry;
    languages: Record<string, string>;
}>();

const showOriginal = ref(false);

const hasTranslation = computed(() => !!props.entry.translated_content);

const displayTitle = computed(() =>
    hasTranslation.value && !showOriginal.value
        ? props.entry.translated_title
        : props.entry.title,
);

const displayContent = computed(() =>
    hasTranslation.value && !showOriginal.value
        ? props.entry.translated_content
        : props.entry.content,
);

const translatedLanguageName = computed(() =>
    props.entry.translated_language
        ? (props.languages[props.entry.translated_language] ?? props.entry.translated_language)
        : null,
);

function markUnread() {
    router.patch(route('entries.unread', props.entry.id));
}

function toggleStar() {
    router.patch(
        route('entries.star', props.entry.id),
        {},
        { preserveScroll: true },
    );
}

function deleteEntry() {
    if (!confirm('Delete this entry? This cannot be undone.')) {
        return;
    }

    router.delete(route('entries.destroy', props.entry.id));
}

function formatDate(value: string | null): string {
    if (!value) return '';

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

// Tags
const newTagName = ref('');
const tagForm = useForm({ name: '' });

function addTag() {
    if (!newTagName.value.trim()) return;

    tagForm.name = newTagName.value.trim();
    tagForm.post(route('entries.tags.attach', props.entry.id), {
        preserveScroll: true,
        onSuccess: () => {
            newTagName.value = '';
        },
    });
}

function removeTag(tag: Tag) {
    router.delete(route('entries.tags.detach', [props.entry.id, tag.id]), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="displayTitle ?? 'Entry'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <Link
                    :href="route('entries.index')"
                    class="text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                >
                    &larr; Back to Reader
                </Link>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="text-xl leading-none"
                        :class="
                            entry.is_starred
                                ? 'text-amber-500'
                                : 'text-gray-300 hover:text-gray-400 dark:text-gray-600'
                        "
                        :aria-label="entry.is_starred ? 'Unstar' : 'Star'"
                        @click="toggleStar"
                    >
                        &#9733;
                    </button>
                    <SecondaryButton @click="markUnread">
                        Mark unread
                    </SecondaryButton>
                    <DangerButton @click="deleteEntry">
                        Delete
                    </DangerButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <article
                    class="overflow-hidden rounded-lg bg-white p-8 shadow-sm dark:bg-gray-800"
                >
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ entry.feed?.title }}
                        <span v-if="entry.author"> &middot; {{ entry.author }}</span>
                        <span v-if="entry.published_at">
                            &middot; {{ formatDate(entry.published_at) }}
                        </span>
                    </p>
                    <h1
                        class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100"
                    >
                        {{ displayTitle }}
                    </h1>
                    <a
                        v-if="entry.url"
                        :href="entry.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-1 inline-block text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                    >
                        View original &rarr;
                    </a>

                    <p
                        v-if="hasTranslation"
                        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                    >
                        <template v-if="showOriginal">
                            Showing the original text.
                            <button
                                type="button"
                                class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                                @click="showOriginal = false"
                            >
                                Show translation
                            </button>
                        </template>
                        <template v-else>
                            Translated to {{ translatedLanguageName }}.
                            <button
                                type="button"
                                class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                                @click="showOriginal = true"
                            >
                                Show original
                            </button>
                        </template>
                    </p>

                    <div
                        v-if="entry.enclosure_url"
                        class="mt-4 flex gap-4 rounded-md border border-gray-200 p-4 dark:border-gray-700"
                    >
                        <img
                            v-if="entry.image_url"
                            :src="entry.image_url"
                            alt=""
                            class="h-16 w-16 shrink-0 rounded object-cover"
                        />
                        <div class="min-w-0 flex-1">
                            <p
                                v-if="entry.season_number || entry.episode_number"
                                class="mb-1 text-xs text-gray-500 dark:text-gray-400"
                            >
                                <span v-if="entry.season_number">Season {{ entry.season_number }}</span>
                                <span v-if="entry.season_number && entry.episode_number"> &middot; </span>
                                <span v-if="entry.episode_number">Episode {{ entry.episode_number }}</span>
                                <span v-if="entry.duration_seconds">
                                    &middot; {{ formatDuration(entry.duration_seconds) }}
                                </span>
                            </p>
                            <audio
                                v-if="entry.enclosure_type?.startsWith('audio/')"
                                :src="entry.enclosure_url"
                                controls
                                preload="none"
                                class="w-full"
                            />
                            <video
                                v-else-if="entry.enclosure_type?.startsWith('video/')"
                                :src="entry.enclosure_url"
                                controls
                                preload="none"
                                class="w-full rounded"
                            />
                            <a
                                v-else
                                :href="entry.enclosure_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                            >
                                Download episode &rarr;
                            </a>
                        </div>
                    </div>

                    <div
                        v-if="entry.summary"
                        class="mt-4 rounded-md border border-indigo-100 bg-indigo-50 p-4 dark:border-indigo-900 dark:bg-indigo-950/40"
                    >
                        <p
                            class="mb-1 text-xs font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300"
                        >
                            AI summary
                        </p>
                        <p class="text-sm text-indigo-900 dark:text-indigo-100">
                            {{ entry.summary }}
                        </p>
                    </div>

                    <!--
                        entry.content and entry.translated_content are both sanitized
                        server-side (Symfony HtmlSanitizer, see
                        App\Services\Feeds\ContentSanitizer) before they are ever
                        stored, so rendering either here is safe.
                    -->
                    <div
                        class="prose prose-sm mt-6 max-w-none dark:prose-invert"
                        v-html="displayContent"
                    />

                    <div class="mt-8 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                v-for="tag in entry.tags"
                                :key="tag.id"
                                class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                            >
                                #{{ tag.name }}
                                <button
                                    type="button"
                                    class="text-gray-400 hover:text-red-500"
                                    :aria-label="`Remove tag ${tag.name}`"
                                    @click="removeTag(tag)"
                                >
                                    &times;
                                </button>
                            </span>
                            <form @submit.prevent="addTag" class="inline-flex items-center gap-1">
                                <TextInput
                                    v-model="newTagName"
                                    placeholder="Add tag"
                                    class="!py-1 text-xs"
                                />
                            </form>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
