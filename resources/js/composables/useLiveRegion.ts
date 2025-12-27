import { ref } from 'vue';

export function useLiveRegion() {
    const message = ref('');
    const politeness = ref<'polite' | 'assertive'>('polite');

    const announce = (text: string, urgent: boolean = false) => {
        politeness.value = urgent ? 'assertive' : 'polite';
        message.value = text;
    };

    return {
        message,
        politeness,
        announce,
    };
}
