<script setup lang="ts">
import { ref, watch } from 'vue';

interface Props {
    message?: string;
    politeness?: 'polite' | 'assertive';
}

const props = withDefaults(defineProps<Props>(), {
    message: '',
    politeness: 'polite',
});

const announcement = ref('');

// Mettre à jour l'annonce quand le message change
watch(() => props.message, (newMessage) => {
    if (newMessage) {
        // Petit délai pour s'assurer que le lecteur d'écran capte le changement
        setTimeout(() => {
            announcement.value = newMessage;
            // Réinitialiser après un délai pour permettre de réannoncer le même message
            setTimeout(() => {
                announcement.value = '';
            }, 1000);
        }, 100);
    }
});
</script>

<template>
    <div
        :aria-live="politeness"
        aria-atomic="true"
        class="sr-only"
        role="status"
    >
        {{ announcement }}
    </div>
</template>
