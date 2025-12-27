<script setup lang="ts">
import { ref } from 'vue';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';

interface Shortcut {
    keys: string[];
    description: string;
}

const isOpen = ref(false);

const shortcuts: Shortcut[] = [
    { keys: ['?'], description: 'Afficher cette aide' },
    { keys: ['Alt', 'N'], description: 'Nouvelle réservation' },
    { keys: ['Échap'], description: 'Fermer les dialogues' },
    { keys: ['Tab'], description: 'Navigation entre les éléments' },
    { keys: ['Shift', 'Tab'], description: 'Navigation arrière' },
    { keys: ['Entrée'], description: 'Activer/Sélectionner' },
    { keys: ['Espace'], description: 'Cocher/Décocher' },
];

// Exposer pour usage externe
defineExpose({
    open: () => isOpen.value = true,
});
</script>

<template>
    <Dialog v-model:open="isOpen">

            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Raccourcis clavier</DialogTitle>
                    <DialogDescription>
                        Utilisez ces raccourcis pour naviguer plus rapidement dans l'application
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-3 py-4">
                    <div
                        v-for="(shortcut, index) in shortcuts"
                        :key="index"
                        class="flex items-center justify-between py-2 border-b last:border-b-0"
                    >
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            {{ shortcut.description }}
                        </span>
                        <div class="flex gap-1">
                            <kbd
                                v-for="(key, idx) in shortcut.keys"
                                :key="idx"
                                class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600"
                            >
                                {{ key }}
                            </kbd>
                        </div>
                    </div>
                </div>

                <div class="text-sm text-gray-500 dark:text-gray-400 border-t pt-4">
                    <p>💡 Astuce : Appuyez sur <kbd class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">?</kbd> à tout moment pour afficher cette aide</p>
                </div>
            </DialogContent>
        </Dialog>
</template>
