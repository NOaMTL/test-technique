<script setup lang="ts">
import { TimeFieldInput, type TimeFieldInputProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<TimeFieldInputProps & { class?: string }>();
</script>

<template>
    <TimeFieldInput
        :class="cn(
            'inline-flex w-full items-center rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2',
            props.class
        )"
        v-bind="props"
    >
        <slot />
    </TimeFieldInput>
</template>
