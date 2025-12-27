<script setup lang="ts">
import { Star } from 'lucide-vue-next'

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
    is_favorite?: boolean
    images?: RoomImage[]
    min_participants?: number
}

interface Props {
    room: Room
    selected?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    selected: false
})

const emit = defineEmits<{
    select: [roomId: number]
}>()

const handleClick = () => {
    emit('select', props.room.id)
}
</script>

<template>
    <div
        @click="handleClick"
        class="p-3 border-2 rounded-lg cursor-pointer transition flex gap-3"
        :class="selected 
            ? 'border-msl-primary bg-msl-primary/10 shadow-msl-s' 
            : 'border-gray-200 dark:border-gray-700 hover:border-msl-primary/50 hover:bg-gray-50 dark:hover:bg-gray-700'"
    >
        <!-- Image placeholder -->
        <div class="w-20 h-20 flex-shrink-0 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center overflow-hidden">
            <img
                v-if="room.images && room.images.length > 0"
                :src="`/storage/${room.images[0].path}`"
                :alt="room.nom"
                class="w-full h-full object-cover"
            />
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </svg>
        </div>
        
        <!-- Contenu -->
        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold truncate">{{ room.nom }}</h3>
                        <Star 
                            v-if="room.is_favorite" 
                            class="w-4 h-4 text-yellow-500 fill-yellow-500 flex-shrink-0"
                        />
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5 line-clamp-2">{{ room.description }}</p>
                </div>
                <div v-if="selected" class="text-msl-primary ml-2 flex-shrink-0">
                    ✓
                </div>
            </div>
            <div class="mt-2 text-xs space-y-1">
                <div><strong>Capacité:</strong> {{ room.capacite }} pers. | <strong>Étage:</strong> {{ room.etage }}</div>
                <div v-if="room.equipement.length > 0" class="flex flex-wrap gap-1 mt-1">
                    <span
                        v-for="(eq, idx) in room.equipement"
                        :key="idx"
                        class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 text-xs rounded"
                    >
                        {{ eq }}
                    </span>
                </div>
                <div v-if="room.min_participants" class="text-orange-600 dark:text-orange-400 text-xs mt-1">
                    ⚠️ Minimum {{ room.min_participants }} participant(s) requis
                </div>
            </div>
        </div>
    </div>
</template>
