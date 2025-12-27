<script setup lang="ts">
import { Toaster as Sonner } from 'vue-sonner'
</script>

<template>
    <Sonner 
        position="top-right"
        :duration="4000"
        :close-button="true"
        rich-colors
        role="region"
        aria-live="polite"
        aria-label="Notifications"
    />
</template>
