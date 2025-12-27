<script setup lang="ts">
import { AlertDialogTrigger } from 'reka-ui'
import { cn } from '@/lib/utils'

defineProps<{ class?: string }>()
</script>

<template>
  <AlertDialogTrigger v-bind="$attrs" :class="cn($props.class)">
    <slot />
  </AlertDialogTrigger>
</template>
