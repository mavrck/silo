<script setup lang="ts">
import { ref } from 'vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { router, useForm } from '@inertiajs/vue3';
import type { ApiToken } from '@/types';

const props = defineProps<{
    tokens: ApiToken[];
    newToken?: string | null;
}>();

const createForm = useForm({
    name: '',
});

function createToken() {
    createForm.post(route('api-tokens.store'), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

function regenerateToken(token: ApiToken) {
    if (
        !confirm(
            `Regenerate "${token.name}"? The current key will stop working immediately.`,
        )
    ) {
        return;
    }

    router.post(
        route('api-tokens.regenerate', token.id),
        {},
        { preserveScroll: true },
    );
}

function revokeToken(token: ApiToken) {
    if (!confirm(`Revoke "${token.name}"? This can't be undone.`)) {
        return;
    }

    router.delete(route('api-tokens.destroy', token.id), {
        preserveScroll: true,
    });
}

const copied = ref(false);

function copyToken() {
    if (!props.newToken) return;

    navigator.clipboard.writeText(props.newToken);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
}

function formatDate(value: string | null): string {
    if (!value) return 'Never used';

    return new Date(value).toLocaleDateString(undefined, {
        dateStyle: 'medium',
    });
}
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                API Tokens
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Personal access tokens for authenticating with Silo's API.
                Treat them like passwords.
            </p>
        </header>

        <div
            v-if="newToken"
            class="mt-6 rounded-md border border-indigo-100 bg-indigo-50 p-4 dark:border-indigo-900 dark:bg-indigo-950/40"
        >
            <p
                class="mb-2 text-xs font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300"
            >
                Copy this token now &mdash; you won't be able to see it again
            </p>
            <div class="flex items-center gap-2">
                <code
                    class="flex-1 overflow-x-auto rounded bg-white px-3 py-2 text-sm text-indigo-900 dark:bg-gray-900 dark:text-indigo-100"
                >{{ newToken }}</code>
                <SecondaryButton type="button" @click="copyToken">
                    {{ copied ? 'Copied!' : 'Copy' }}
                </SecondaryButton>
            </div>
        </div>

        <ul
            v-if="tokens.length"
            class="mt-6 divide-y divide-gray-200 dark:divide-gray-700"
        >
            <li
                v-for="token in tokens"
                :key="token.id"
                class="flex items-center justify-between py-3"
            >
                <div class="min-w-0">
                    <p class="truncate font-medium text-gray-900 dark:text-gray-100">
                        {{ token.name }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Created {{ formatDate(token.created_at) }} &middot;
                        {{ formatDate(token.last_used_at) }}
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-3">
                    <button
                        type="button"
                        class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        @click="regenerateToken(token)"
                    >
                        Regenerate
                    </button>
                    <DangerButton type="button" @click="revokeToken(token)">
                        Revoke
                    </DangerButton>
                </div>
            </li>
        </ul>
        <p v-else class="mt-6 text-sm text-gray-500 dark:text-gray-400">
            No API tokens yet.
        </p>

        <form
            @submit.prevent="createToken"
            class="mt-6 flex items-start gap-2 border-t border-gray-200 pt-6 dark:border-gray-700"
        >
            <div class="flex-1">
                <InputLabel value="New token name" />
                <TextInput
                    v-model="createForm.name"
                    placeholder="e.g. My laptop, Feed reader app"
                    class="mt-1 block w-full max-w-xs"
                />
                <InputError :message="createForm.errors.name" class="mt-2" />
            </div>
            <PrimaryButton class="mt-6" :disabled="createForm.processing">
                Create
            </PrimaryButton>
        </form>
    </section>
</template>
