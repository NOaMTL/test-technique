<script setup lang="ts">
import { TimeFieldRoot, type TimeFieldRootEmits, type TimeFieldRootProps, useForwardPropsEmits } from 'reka-ui';

const props = defineProps<TimeFieldRootProps>();
const emits = defineEmits<TimeFieldRootEmits>();

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <TimeFieldRoot v-bind="forwarded">
        <slot />
    </TimeFieldRoot>
</template>
