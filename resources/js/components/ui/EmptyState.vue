<script setup lang="ts">
import type { LucideIcon } from 'lucide-vue-next';

interface Props {
    icon: LucideIcon;
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex flex-col items-center justify-center py-12 text-center">
        <component
            :is="icon"
            class="h-12 w-12 text-gray-400 mb-4"
        />
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            {{ title }}
        </h3>
        <p v-if="description" class="text-sm text-gray-500 dark:text-gray-400 max-w-md">
            {{ description }}
        </p>
        <slot name="action" />
    </div>
</template>
