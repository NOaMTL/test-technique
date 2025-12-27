<script setup lang="ts">
import type { SwitchRootEmits, SwitchRootProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { SwitchRoot, SwitchThumb, useForwardPropsEmits } from 'reka-ui';
import { cn } from '@/lib/utils';
import { computed } from 'vue';

const props = defineProps<SwitchRootProps & { class?: HTMLAttributes['class']; checked?: boolean }>();
const emits = defineEmits<SwitchRootEmits>();

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardPropsEmits(delegatedProps, emits);

const bgClass = computed(() => {
    return props.checked || props.modelValue 
        ? 'bg-msl-primary' 
        : 'bg-gray-200 dark:bg-gray-700';
});
</script>

<template>
    <SwitchRoot
        v-bind="forwarded"
        :class="
            cn(
                'peer inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-msl-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50',
                bgClass,
                props.class
            )
        "
    >
        <SwitchThumb
            :class="
                cn(
                    'pointer-events-none block h-4 w-4 rounded-full bg-white shadow-lg ring-0 transition-transform',
                    (props.checked || props.modelValue) ? 'translate-x-4' : 'translate-x-0'
                )
            "
        />
    </SwitchRoot>
</template>
