<script setup lang="ts">
import { Spinner } from '@/components/ui/spinner';
import { dashboard, login } from '@/routes';
import { Head, router } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

// Redirect based on authentication status
watchEffect(() => {
    const user = (window as any).$page?.props?.auth?.user;
    if (user) {
        router.visit(dashboard());
    } else {
        router.visit(login());
    }
});
</script>

<template>
    <Head title="Chargement..." />

    <div
        class="relative min-h-svh overflow-hidden bg-[linear-gradient(135deg,_var(--msl-bg-brand-primary)_0%,_var(--msl-bg-brand-secondary)_40%,_var(--msl-bg-brand-tertiary)_100%)]"
    >
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -top-32 -left-24 h-[420px] w-[420px] rounded-full bg-white/15 blur-3xl"></div>
            <div class="absolute bottom-[-180px] right-[-120px] h-[520px] w-[520px] rounded-full bg-white/10 blur-3xl"></div>
        </div>

        <div class="relative z-10 flex min-h-svh items-center justify-center">
            <div class="text-center">
                <Spinner class="mb-4 h-8 w-8 text-white" />
                <p class="text-sm text-white/80">Redirection en cours...</p>
            </div>
        </div>
    </div>
</template>
