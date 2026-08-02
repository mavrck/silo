<script setup lang="ts">
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import type { CategoryWithFeeds, Feed } from '@/types';

const props = defineProps<{
    categories: CategoryWithFeeds[];
}>();

const page = usePage();
const flashStatus = computed(() => page.props.flash.status);

const feedCount = computed(() =>
    props.categories.reduce((total, category) => total + category.feeds.length, 0),
);

const subscribeForm = useForm({
    url: '',
    title: '',
    category_id: '' as number | '',
    summarize: false,
});

function subscribe() {
    subscribeForm.post(route('feeds.store'), {
        preserveScroll: true,
        onSuccess: () => subscribeForm.reset(),
    });
}

function unsubscribe(feedId: number, title: string) {
    if (!confirm(`Unsubscribe from "${title}"? Its articles will be deleted.`)) {
        return;
    }

    router.delete(route('feeds.destroy', feedId), { preserveScroll: true });
}

function toggleSummarize(feed: Feed) {
    router.patch(
        route('feeds.summarize', feed.id),
        {},
        { preserveScroll: true, preserveState: true },
    );
}

const importForm = useForm<{ file: File | null }>({
    file: null,
});

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    importForm.file = target.files?.[0] ?? null;
}

function importOpml() {
    importForm.post(route('opml.import'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => importForm.reset(),
    });
}
</script>

<template>
    <Head title="Feeds" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                Feeds
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <div
                    v-if="flashStatus"
                    class="rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900/50 dark:text-green-200"
                >
                    {{ flashStatus }}
                </div>

                <!-- Subscribe -->
                <div
                    class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800"
                >
                    <h3 class="mb-4 font-medium text-gray-900 dark:text-gray-100">
                        Add a feed
                    </h3>
                    <form @submit.prevent="subscribe" class="space-y-4">
                        <div>
                            <InputLabel value="Feed URL" />
                            <TextInput
                                v-model="subscribeForm.url"
                                type="url"
                                placeholder="https://example.com/feed.xml"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="subscribeForm.errors.url" />
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <InputLabel value="Title (optional)" />
                                <TextInput
                                    v-model="subscribeForm.title"
                                    placeholder="Uses the feed's own title if left blank"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="subscribeForm.errors.title" />
                            </div>
                            <div class="w-56">
                                <InputLabel value="Category" />
                                <select
                                    v-model="subscribeForm.category_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                >
                                    <option value="">Uncategorized</option>
                                    <option
                                        v-for="category in categories"
                                        :key="category.id"
                                        :value="category.id"
                                    >
                                        {{ category.name }}
                                    </option>
                                </select>
                                <InputError :message="subscribeForm.errors.category_id" />
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <Checkbox v-model:checked="subscribeForm.summarize" />
                            Summarize new articles with AI
                        </label>
                        <PrimaryButton :disabled="subscribeForm.processing">
                            Subscribe
                        </PrimaryButton>
                    </form>
                </div>

                <!-- OPML -->
                <div
                    class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800"
                >
                    <h3 class="mb-4 font-medium text-gray-900 dark:text-gray-100">
                        OPML
                    </h3>
                    <div class="flex flex-wrap items-center gap-4">
                        <form
                            @submit.prevent="importOpml"
                            class="flex items-center gap-2"
                        >
                            <input
                                type="file"
                                accept=".opml,.xml,text/xml"
                                @change="onFileChange"
                                class="text-sm text-gray-600 dark:text-gray-400"
                            />
                            <SecondaryButton
                                type="submit"
                                :disabled="!importForm.file || importForm.processing"
                            >
                                Import
                            </SecondaryButton>
                        </form>
                        <InputError :message="importForm.errors.file" />
                        <a
                            :href="route('opml.export')"
                            class="text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                        >
                            Export subscriptions &rarr;
                        </a>
                    </div>
                </div>

                <!-- Subscriptions -->
                <div
                    class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800"
                >
                    <h3 class="mb-4 font-medium text-gray-900 dark:text-gray-100">
                        Subscriptions
                    </h3>

                    <p
                        v-if="feedCount === 0"
                        class="text-sm text-gray-500 dark:text-gray-400"
                    >
                        No feeds yet. Add one above or import an OPML file.
                    </p>

                    <div v-else class="space-y-6">
                        <div
                            v-for="category in categories.filter((c) => c.feeds.length)"
                            :key="category.id"
                        >
                            <h4
                                class="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                            >
                                {{ category.name }}
                            </h4>
                            <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                                <li
                                    v-for="feed in category.feeds"
                                    :key="feed.id"
                                    class="flex items-center justify-between py-3"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="truncate font-medium text-gray-900 dark:text-gray-100"
                                        >
                                            {{ feed.title }}
                                        </p>
                                        <p
                                            v-if="feed.last_fetch_error"
                                            class="truncate text-sm text-red-600 dark:text-red-400"
                                        >
                                            {{ feed.last_fetch_error }}
                                        </p>
                                        <p
                                            v-else-if="feed.last_fetched_at"
                                            class="truncate text-sm text-gray-500 dark:text-gray-400"
                                        >
                                            Last checked
                                            {{ new Date(feed.last_fetched_at).toLocaleString() }}
                                        </p>
                                        <p
                                            v-else
                                            class="truncate text-sm text-gray-500 dark:text-gray-400"
                                        >
                                            Waiting for first fetch&hellip;
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-4">
                                        <label
                                            class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            <Checkbox
                                                :checked="feed.summarize"
                                                @update:checked="toggleSummarize(feed)"
                                            />
                                            AI summary
                                        </label>
                                        <DangerButton
                                            type="button"
                                            @click="unsubscribe(feed.id, feed.title)"
                                        >
                                            Unsubscribe
                                        </DangerButton>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
