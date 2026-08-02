<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
}>();

const page = usePage();
</script>

<template>
    <Head title="Welcome" />

    <div
        class="flex min-h-screen flex-col items-center justify-center px-6"
        style="background-color: #f2e8d5"
    >
        <div class="w-full max-w-md text-center">
            <ApplicationLogo class="mx-auto h-24 w-24" />

            <h1 class="mt-6 text-4xl font-bold" style="color: #3b2a1e">
                Silo
            </h1>
            <p class="mt-1 text-sm font-medium" style="color: #7a6a55">
                Grain by grain.
            </p>
            <p class="mt-4 text-base" style="color: #7a6a55">
                A self-hosted feed reader. Subscribe to RSS and Atom feeds,
                read them in one place, and let AI summarize the ones you
                don't have time for.
            </p>

            <div class="mt-8 flex justify-center gap-4">
                <Link
                    v-if="page.props.auth.user"
                    :href="route('entries.index')"
                    class="rounded-md px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
                    style="background-color: #2f4b33"
                >
                    Go to your Reader
                </Link>
                <template v-else>
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="rounded-md px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
                        style="background-color: #2f4b33"
                    >
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="rounded-md border px-5 py-2.5 text-sm font-semibold transition hover:opacity-70"
                        style="border-color: #2f4b33; color: #2f4b33"
                    >
                        Sign up
                    </Link>
                </template>
            </div>
        </div>
    </div>
</template>
