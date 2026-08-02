<script setup lang="ts">
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import type { TagWithCount } from '@/types';

defineProps<{
    tags: TagWithCount[];
}>();

const editingId = ref<number | null>(null);
const editForm = useForm({ name: '' });

function startEditing(tag: TagWithCount) {
    editingId.value = tag.id;
    editForm.name = tag.name;
}

function cancelEditing() {
    editingId.value = null;
    editForm.reset();
    editForm.clearErrors();
}

function saveTag(tag: TagWithCount) {
    editForm.patch(route('tags.update', tag.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
}

function deleteTag(tag: TagWithCount) {
    if (!confirm(`Delete "#${tag.name}"? It will be removed from all entries.`)) {
        return;
    }

    router.delete(route('tags.destroy', tag.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Tags" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                Tags
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800"
                >
                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        Tags are created from an entry's page. Manage or
                        remove them here.
                    </p>

                    <ul
                        v-if="tags.length"
                        class="divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        <li
                            v-for="tag in tags"
                            :key="tag.id"
                            class="flex items-center justify-between py-3"
                        >
                            <template v-if="editingId === tag.id">
                                <form
                                    @submit.prevent="saveTag(tag)"
                                    class="flex flex-1 items-center gap-2"
                                >
                                    <TextInput
                                        v-model="editForm.name"
                                        class="block w-full max-w-xs"
                                        autofocus
                                    />
                                    <InputError :message="editForm.errors.name" />
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
                            </template>
                            <template v-else>
                                <Link
                                    :href="route('entries.index', { tag_id: tag.id })"
                                    class="text-gray-900 hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400"
                                >
                                    #{{ tag.name }}
                                    <span class="text-sm text-gray-400">
                                        ({{ tag.entries_count }})
                                    </span>
                                </Link>
                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                        @click="startEditing(tag)"
                                    >
                                        Rename
                                    </button>
                                    <DangerButton
                                        type="button"
                                        @click="deleteTag(tag)"
                                    >
                                        Delete
                                    </DangerButton>
                                </div>
                            </template>
                        </li>
                    </ul>
                    <p
                        v-else
                        class="text-sm text-gray-500 dark:text-gray-400"
                    >
                        No tags yet. Add one from an entry's page.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
