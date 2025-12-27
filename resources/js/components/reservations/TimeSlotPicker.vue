<script setup lang="ts">
import { computed, watch } from 'vue';
import { Label } from '@/components/ui/label';

interface Props {
    date: string;
    startTime: string;
    endTime: string;
    openingTime: string;
    closingTime: string;
    slotDuration: number;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:startTime': [value: string];
    'update:endTime': [value: string];
}>();

const availableTimeSlots = computed(() => {
    const slots: string[] = [];
    const now = new Date();
    const today = now.toISOString().split('T')[0];
    const isToday = props.date === today;
    
    // Parser les heures d'ouverture et fermeture
    const [openHour, openMinute] = props.openingTime.split(':').map(Number);
    const [closeHour, closeMinute] = props.closingTime.split(':').map(Number);
    
    const openingMinutes = openHour * 60 + openMinute;
    const closingMinutes = closeHour * 60 + closeMinute;
    
    for (let minutes = openingMinutes; minutes < closingMinutes; minutes += props.slotDuration) {
        const hour = Math.floor(minutes / 60);
        const minute = minutes % 60;
        const timeString = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
        
        if (isToday) {
            // Si c'est aujourd'hui, ne montrer que les créneaux futurs
            const slotTime = new Date(now);
            slotTime.setHours(hour, minute, 0, 0);
            
            if (slotTime > now) {
                slots.push(timeString);
            }
        } else {
            slots.push(timeString);
        }
    }
    return slots;
});

const availableEndSlots = computed(() => {
    if (!props.startTime) return availableTimeSlots.value;
    
    // Filtrer pour ne garder que les créneaux après l'heure de début
    return availableTimeSlots.value.filter(slot => slot > props.startTime);
});

// Réinitialiser l'heure de fin si elle devient invalide
watch([() => props.startTime, () => props.date], () => {
    if (props.endTime && props.endTime <= props.startTime) {
        emit('update:endTime', '');
    }
});
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <Label for="heure_debut">Heure de début</Label>
            <select
                id="heure_debut"
                :value="startTime"
                @change="emit('update:startTime', ($event.target as HTMLSelectElement).value)"
                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <option value="">Sélectionner l'heure</option>
                <option
                    v-for="slot in availableTimeSlots"
                    :key="slot"
                    :value="slot"
                >
                    {{ slot }}
                </option>
            </select>
        </div>

        <div>
            <Label for="heure_fin">Heure de fin</Label>
            <select
                id="heure_fin"
                :value="endTime"
                @change="emit('update:endTime', ($event.target as HTMLSelectElement).value)"
                :disabled="!startTime"
                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <option value="">Sélectionner l'heure</option>
                <option
                    v-for="slot in availableEndSlots"
                    :key="slot"
                    :value="slot"
                >
                    {{ slot }}
                </option>
            </select>
        </div>
    </div>
</template>
