<script setup lang="ts">
import { computed, ref } from 'vue';
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
    languages: Record<string, string>;
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
    translate_to: '',
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

function updateTranslation(feed: Feed, translateTo: string) {
    router.patch(
        route('feeds.translate', feed.id),
        { translate_to: translateTo || null },
        { preserveScroll: true, preserveState: true },
    );
}

const editingId = ref<number | null>(null);
const editForm = useForm({
    title: '',
    category_id: '' as number | '',
});

function startEditing(feed: Feed) {
    editingId.value = feed.id;
    editForm.title = feed.title;
    editForm.category_id = feed.category_id;
}

function cancelEditing() {
    editingId.value = null;
    editForm.reset();
    editForm.clearErrors();
}

function saveFeed(feed: Feed) {
    editForm.patch(route('feeds.update', feed.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
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
                        <div class="flex items-center gap-2">
                            <label
                                for="subscribe-translate-to"
                                class="text-sm text-gray-600 dark:text-gray-400"
                            >
                                Translate articles to
                            </label>
                            <select
                                id="subscribe-translate-to"
                                v-model="subscribeForm.translate_to"
                                class="rounded-md border-gray-300 py-1 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >
                                <option value="">Don't translate</option>
                                <option
                                    v-for="(name, code) in languages"
                                    :key="code"
                                    :value="code"
                                >
                                    {{ name }}
                                </option>
                            </select>
                            <InputError :message="subscribeForm.errors.translate_to" />
                        </div>
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
                                    class="py-3"
                                >
                                    <form
                                        v-if="editingId === feed.id"
                                        @submit.prevent="saveFeed(feed)"
                                        class="flex flex-wrap items-start gap-2"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <TextInput
                                                v-model="editForm.title"
                                                class="block w-full"
                                                autofocus
                                            />
                                            <InputError :message="editForm.errors.title" />
                                        </div>
                                        <div class="w-48">
                                            <select
                                                v-model="editForm.category_id"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                            >
                                                <option
                                                    v-for="c in categories"
                                                    :key="c.id"
                                                    :value="c.id"
                                                >
                                                    {{ c.name }}
                                                </option>
                                            </select>
                                            <InputError :message="editForm.errors.category_id" />
                                        </div>
                                        <PrimaryButton :disabled="editForm.processing">
                                            Save
                                        </PrimaryButton>
                                        <button
                                            type="button"
                                            class="text-sm text-gray-500 dark:text-gray-400"
                                            @click="cancelEditing"
                                        >
                                            Cancel
                                        </button>
                                    </form>
                                    <div v-else class="flex items-center justify-between">
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
                                            <select
                                                :value="feed.translate_to ?? ''"
                                                class="rounded-md border-gray-300 py-1 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                                @change="
                                                    updateTranslation(
                                                        feed,
                                                        ($event.target as HTMLSelectElement).value,
                                                    )
                                                "
                                            >
                                                <option value="">Don't translate</option>
                                                <option
                                                    v-for="(name, code) in languages"
                                                    :key="code"
                                                    :value="code"
                                                >
                                                    {{ name }}
                                                </option>
                                            </select>
                                            <button
                                                type="button"
                                                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                                @click="startEditing(feed)"
                                            >
                                                Edit
                                            </button>
                                            <DangerButton
                                                type="button"
                                                @click="unsubscribe(feed.id, feed.title)"
                                            >
                                                Unsubscribe
                                            </DangerButton>
                                        </div>
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
