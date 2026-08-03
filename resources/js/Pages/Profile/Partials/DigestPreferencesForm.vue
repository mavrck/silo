<script setup lang="ts">
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
    digestFrequency: string;
}>();

const options: { value: string; label: string; description: string }[] = [
    { value: 'off', label: 'Off', description: "Don't send me a digest." },
    {
        value: 'daily',
        label: 'Daily',
        description: 'Unread entries from the last 24 hours.',
    },
    {
        value: 'weekly',
        label: 'Weekly',
        description: 'Unread entries from the last 7 days.',
    },
];

const form = useForm({
    digest_frequency: props.digestFrequency,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Email Digest
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Get a summary of your unread entries sent to your inbox.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('digest-preference.update'))"
            class="mt-6 space-y-6"
        >
            <div class="space-y-3">
                <InputLabel value="Frequency" />

                <div
                    v-for="option in options"
                    :key="option.value"
                    class="flex items-start gap-3"
                >
                    <input
                        :id="`digest-${option.value}`"
                        type="radio"
                        :value="option.value"
                        v-model="form.digest_frequency"
                        class="mt-1 border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                    />
                    <label :for="`digest-${option.value}`" class="cursor-pointer">
                        <span
                            class="block text-sm font-medium text-gray-900 dark:text-gray-100"
                        >
                            {{ option.label }}
                        </span>
                        <span class="block text-sm text-gray-500 dark:text-gray-400">
                            {{ option.description }}
                        </span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600 dark:text-gray-400"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
