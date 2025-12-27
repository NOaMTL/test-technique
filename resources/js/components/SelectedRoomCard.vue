<script setup lang="ts">
interface RoomImage {
    id: number
    path: string
    order: number
}

interface Room {
    id: number
    nom: string
    description: string
    capacite: number
    etage: number
    equipement: string[]
    images?: RoomImage[]
    min_participants?: number
}

interface Props {
    room: Room
}

defineProps<Props>()
</script>

<template>
    <div class="p-4 bg-gradient-to-r from-msl-primary/10 to-msl-primary/20 dark:from-msl-primary/30 dark:to-msl-primary/20 border-2 border-msl-primary rounded-lg shadow-msl-m flex gap-4">
        <!-- Image placeholder -->
        <div class="w-24 h-24 flex-shrink-0 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center overflow-hidden">
            <img
                v-if="room.images && room.images.length > 0"
                :src="`/storage/${room.images[0].path}`"
                :alt="room.nom"
                class="w-full h-full object-cover"
            />
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </svg>
        </div>
        
        <!-- Contenu -->
        <div class="flex-1">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <div class="text-xs font-medium text-msl-primary dark:text-msl-primary mb-1">SALLE SÉLECTIONNÉE</div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ room.nom }}</h3>
                    <p class="text-xs text-gray-700 dark:text-gray-300 mt-0.5">{{ room.description }}</p>
                </div>
                <div class="text-2xl">✓</div>
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="bg-white/60 dark:bg-gray-800/40 rounded p-2">
                    <div class="text-xs text-gray-600 dark:text-gray-400">Capacité</div>
                    <div class="font-bold">{{ room.capacite }} pers.</div>
                </div>
                <div class="bg-white/60 dark:bg-gray-800/40 rounded p-2">
                    <div class="text-xs text-gray-600 dark:text-gray-400">Étage</div>
                    <div class="font-bold">{{ room.etage }}</div>
                </div>
            </div>
            <div v-if="room.equipement.length > 0" class="mt-2">
                <div class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Équipements :</div>
                <div class="flex flex-wrap gap-1.5">
                    <span
                        v-for="(eq, idx) in room.equipement"
                        :key="idx"
                        class="px-2 py-0.5 bg-white dark:bg-gray-800 text-xs font-medium rounded-full border border-msl-primary/30 dark:border-msl-primary"
                    >
                        {{ eq }}
                    </span>
                </div>
            </div>
            <div v-if="room.min_participants" class="mt-2 text-sm font-medium text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/20 rounded p-2">
                ⚠️ Cette salle nécessite au minimum {{ room.min_participants }} participant(s) (vous inclus). Ajoutez {{ room.min_participants - 1 }} autre(s) participant(s) pour débloquer la réservation.
            </div>
        </div>
    </div>
</template>
