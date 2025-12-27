<script setup lang="ts">
import { AlertDialogDescription } from 'reka-ui'
import { cn } from '@/lib/utils'

defineProps<{ class?: string }>()
</script>

<template>
  <AlertDialogDescription :class="cn('text-sm text-gray-500', $props.class)">
    <slot />
  </AlertDialogDescription>
</template>
