<script setup lang="ts">
import { AlertDialogCancel } from 'reka-ui'
import { cn } from '@/lib/utils'

defineProps<{ class?: string }>()
</script>

<template>
  <AlertDialogCancel :class="cn($props.class)">
    <slot />
  </AlertDialogCancel>
</template>
