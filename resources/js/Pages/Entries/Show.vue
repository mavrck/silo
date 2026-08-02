<script setup lang="ts">
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import type { Entry, Tag } from '@/types';

const props = defineProps<{
    entry: Entry;
}>();

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
    <Head :title="entry.title ?? 'Entry'" />

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
                        {{ entry.title }}
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

                    <!--
                        entry.content is sanitized server-side (Symfony HtmlSanitizer,
                        see App\Services\Feeds\ContentSanitizer) before it is ever
                        stored, so rendering it here is safe.
                    -->
                    <div
                        class="prose prose-sm mt-6 max-w-none dark:prose-invert"
                        v-html="entry.content"
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
