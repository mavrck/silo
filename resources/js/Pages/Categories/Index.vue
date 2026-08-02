<script setup lang="ts">
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import type { Category } from '@/types';

defineProps<{
    categories: Category[];
}>();

const createForm = useForm({
    name: '',
});

function createCategory() {
    createForm.post(route('categories.store'), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

const editingId = ref<number | null>(null);
const editForm = useForm({
    name: '',
});

function startEditing(category: Category) {
    editingId.value = category.id;
    editForm.name = category.name;
}

function cancelEditing() {
    editingId.value = null;
    editForm.reset();
    editForm.clearErrors();
}

function saveCategory(category: Category) {
    editForm.patch(route('categories.update', category.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
}

function deleteCategory(category: Category) {
    if (!confirm(`Delete "${category.name}"? Feeds in this category will need to be reassigned.`)) {
        return;
    }

    router.delete(route('categories.destroy', category.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Categories" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                Categories
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800"
                >
                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        Categories group your feeds. Every feed belongs to
                        exactly one category.
                    </p>

                    <ul
                        v-if="categories.length"
                        class="mb-6 divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        <li
                            v-for="category in categories"
                            :key="category.id"
                            class="flex items-center justify-between py-3"
                        >
                            <template v-if="editingId === category.id">
                                <form
                                    @submit.prevent="saveCategory(category)"
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
                                <span class="text-gray-900 dark:text-gray-100">
                                    {{ category.name }}
                                </span>
                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                        @click="startEditing(category)"
                                    >
                                        Rename
                                    </button>
                                    <DangerButton
                                        type="button"
                                        @click="deleteCategory(category)"
                                    >
                                        Delete
                                    </DangerButton>
                                </div>
                            </template>
                        </li>
                    </ul>
                    <p
                        v-else
                        class="mb-6 text-sm text-gray-500 dark:text-gray-400"
                    >
                        No categories yet.
                    </p>

                    <form
                        @submit.prevent="createCategory"
                        class="flex items-start gap-2"
                    >
                        <div>
                            <TextInput
                                v-model="createForm.name"
                                placeholder="New category name"
                                class="block w-full max-w-xs"
                            />
                            <InputError :message="createForm.errors.name" />
                        </div>
                        <PrimaryButton :disabled="createForm.processing">
                            Add
                        </PrimaryButton>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
