<script setup lang="ts">
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative min-h-svh overflow-hidden bg-[linear-gradient(135deg,_var(--msl-bg-brand-primary)_0%,_var(--msl-bg-brand-secondary)_40%,_var(--msl-bg-brand-tertiary)_100%)]"
    >
        <div class="pointer-events-none absolute inset-0">
            <div
                class="absolute -top-32 -left-24 h-[420px] w-[420px] rounded-full bg-white/15 blur-3xl"
            ></div>
            <div
                class="absolute bottom-[-180px] right-[-120px] h-[520px] w-[520px] rounded-full bg-white/10 blur-3xl"
            ></div>
            <div
                class="absolute inset-x-16 top-24 mx-auto h-[420px] max-w-4xl rounded-[48px] border border-white/20 bg-white/10 opacity-40 blur-2xl"
            ></div>
        </div>

        <div class="relative z-10 flex min-h-svh items-center justify-center px-6 py-12">
            <div class="w-full max-w-[420px]">
                <div class="mb-8 flex justify-center">
                    <Link
                        :href="home()"
                        class="flex items-center gap-3 rounded-full border border-white/35 bg-white/10 px-5 py-2 shadow-[var(--msl-boxShadow-s)] backdrop-blur"
                    >
                        <span
                            class="flex size-10 items-center justify-center rounded-full bg-white shadow-sm"
                        >
                            <img
                                src="/logo.svg"
                                alt="Logo"
                                class="h-8 w-8"
                            />
                        </span>
                        <span class="text-left">
                            <span class="block text-sm font-semibold uppercase tracking-[0.24em] text-white">
                                Réservation de salles
                            </span>
                            <span class="block text-xs font-medium text-white/70">
                                Région Locale
                            </span>
                        </span>
                    </Link>
                </div>

                <div
                    class="rounded-[32px] border border-white/30 bg-white/95 p-8 shadow-[var(--msl-boxShadow-l)] backdrop-blur-md dark:border-white/10 dark:bg-gray-950/85"
                >
                    <div class="space-y-2 text-center">
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ title }}
                        </h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ description }}
                        </p>
                    </div>

                    <div class="mt-8">
                        <slot />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
