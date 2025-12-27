<script setup lang="ts">
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Star, MapPin, Users, Wifi, Projector, Mic, Coffee } from 'lucide-vue-next';

interface Room {
    id: number;
    nom: string;
    capacite: number;
    etage?: number;
    equipement?: string[];
    description?: string;
    is_favorite?: boolean;
}

interface Props {
    room: Room;
    showFavorite?: boolean;
    compact?: boolean;
}

withDefaults(defineProps<Props>(), {
    showFavorite: true,
    compact: false,
});

const emit = defineEmits<{
    click: [room: Room];
    toggleFavorite: [room: Room];
}>();

const equipmentIcons: Record<string, any> = {
    'Wifi': Wifi,
    'Vidéoprojecteur': Projector,
    'Projecteur': Projector,
    'Visioconférence': Mic,
    'Machine à café': Coffee,
};

const getEquipmentIcon = (equipmentName: string) => {
    for (const [key, icon] of Object.entries(equipmentIcons)) {
        if (equipmentName.toLowerCase().includes(key.toLowerCase())) {
            return icon;
        }
    }
    return null;
};
</script>

<template>
    <Card 
        :class="[
            'shadow-msl-s hover:shadow-msl-m transition-shadow overflow-hidden cursor-pointer',
            room.is_favorite ? 'border-2 border-msl-primary' : '',
            compact ? 'p-0 gap-0' : '',
        ]"
        @click="emit('click', room)"
    >
        <!-- Image placeholder -->
        <div :class="compact ? 'h-24' : 'h-32'" class="bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
            <svg 
                xmlns="http://www.w3.org/2000/svg" 
                :class="compact ? 'h-12 w-12' : 'h-16 w-16'"
                class="text-gray-400 dark:text-gray-500" 
                fill="none" 
                viewBox="0 0 24 24" 
                stroke="currentColor" 
                stroke-width="1.5"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </svg>
        </div>

        <CardHeader :class="compact ? 'px-3 py-2 pb-1' : ''">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <CardTitle :class="compact ? 'text-base leading-tight' : ''">
                        {{ room.nom }}
                    </CardTitle>
                    <CardDescription :class="['flex items-center gap-1', compact ? 'mt-0.5 text-xs' : 'mt-1']">
                        <MapPin :class="compact ? 'w-3 h-3' : 'w-4 h-4'" />
                        <span v-if="room.etage !== undefined">Étage {{ room.etage }}</span>
                    </CardDescription>
                </div>
                <Button
                    v-if="showFavorite"
                    @click.stop="emit('toggleFavorite', room)"
                    variant="ghost"
                    size="sm"
                    class="cursor-pointer bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 h-8 w-8 p-0"
                >
                    <Star 
                        :class="[
                            'w-5 h-5',
                            room.is_favorite 
                                ? 'text-yellow-500 fill-yellow-500' 
                                : 'text-gray-400 dark:text-gray-500'
                        ]"
                    />
                </Button>
            </div>
        </CardHeader>

        <CardContent :class="compact ? 'px-3 py-2 space-y-1.5' : 'space-y-3'">
            <div v-if="room.description" :class="['text-gray-600 dark:text-gray-400', compact ? 'text-xs line-clamp-2' : 'text-sm']">
                {{ room.description }}
            </div>

            <div class="flex items-center gap-2">
                <Badge variant="secondary" :class="compact ? 'text-xs' : ''">
                    <Users :class="compact ? 'w-3 h-3 mr-1' : 'w-4 h-4 mr-1'" />
                    {{ room.capacite }} pers.
                </Badge>
            </div>

            <div v-if="room.equipement && room.equipement.length > 0" class="flex flex-wrap gap-1.5">
                <Badge
                    v-for="(equip, index) in room.equipement.slice(0, 4)"
                    :key="index"
                    variant="outline"
                    :class="compact ? 'text-xs px-1.5 py-0.5' : ''"
                >
                    <component
                        v-if="getEquipmentIcon(equip)"
                        :is="getEquipmentIcon(equip)"
                        :class="compact ? 'w-3 h-3 mr-1' : 'w-3.5 h-3.5 mr-1'"
                    />
                    {{ equip }}
                </Badge>
                <Badge
                    v-if="room.equipement.length > 4"
                    variant="outline"
                    :class="compact ? 'text-xs px-1.5 py-0.5' : ''"
                >
                    +{{ room.equipement.length - 4 }}
                </Badge>
            </div>
        </CardContent>
    </Card>
</template>
