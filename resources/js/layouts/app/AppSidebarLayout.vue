<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import SkipToContent from '@/components/SkipToContent.vue';
import { Toaster } from '@/components/ui/toast';
import { useKeyboardShortcuts } from '@/composables/useKeyboardShortcuts';
import { router } from '@inertiajs/vue3';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

// Raccourcis clavier globaux
useKeyboardShortcuts([
    {
        key: 'n',
        alt: true,
        description: 'Nouvelle réservation',
        handler: () => router.visit('/reservations/create'),
    },
]);
</script>

<template>
    <AppShell variant="sidebar">
        <SkipToContent />
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <main id="main-content" tabindex="-1">
                <slot />
            </main>
        </AppContent>
        <Toaster />
    </AppShell>
</template>
