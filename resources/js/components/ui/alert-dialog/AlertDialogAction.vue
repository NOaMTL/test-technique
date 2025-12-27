<script setup lang="ts">
import { AlertDialogAction } from 'reka-ui'
import { cn } from '@/lib/utils'

defineProps<{ class?: string }>()
</script>

<template>
  <AlertDialogAction :class="cn($props.class)">
    <slot />
  </AlertDialogAction>
</template>
