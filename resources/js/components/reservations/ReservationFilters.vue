<script setup lang="ts">
import { computed } from 'vue';
import { MultiSelect } from '@/components/ui/multi-select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Search, X } from 'lucide-vue-next';

interface Room {
    id: number;
    nom: string;
    capacite: number;
    etage: number;
}

interface Filters {
    roomIds: number[];
    search: string;
    dateStart: string;
    dateEnd: string;
}

interface Props {
    rooms: Room[];
    filters: Filters;
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
});

const emit = defineEmits<{
    'update:filters': [value: Filters];
    'clear': [];
}>();

const roomOptions = computed(() => {
    return [...props.rooms]
        .sort((a, b) => a.etage - b.etage || a.nom.localeCompare(b.nom))
        .map(room => ({
            value: room.id,
            label: room.nom,
            description: `Capacité: ${room.capacite} personnes`,
            group: `Étage ${room.etage}`
        }));
});

const updateRoomIds = (value: number[]) => {
    emit('update:filters', { ...props.filters, roomIds: value });
};

const updateSearch = (event: Event) => {
    const target = event.target as HTMLInputElement;
    emit('update:filters', { ...props.filters, search: target.value });
};

const updateDateStart = (event: Event) => {
    const target = event.target as HTMLInputElement;
    emit('update:filters', { ...props.filters, dateStart: target.value });
};

const updateDateEnd = (event: Event) => {
    const target = event.target as HTMLInputElement;
    emit('update:filters', { ...props.filters, dateEnd: target.value });
};

const hasActiveFilters = computed(() => {
    return props.filters.roomIds.length > 0 ||
        props.filters.search !== '' ||
        props.filters.dateStart !== '' ||
        props.filters.dateEnd !== '';
});
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Recherche -->
            <div>
                <Label for="search">Rechercher</Label>
                <div class="relative">
                    <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        id="search"
                        type="text"
                        placeholder="Titre, description, salle..."
                        :value="filters.search"
                        @input="updateSearch"
                        class="pl-8"
                    />
                </div>
            </div>

            <!-- Salles -->
            <div>
                <Label>Salles</Label>
                <MultiSelect
                    :options="roomOptions"
                    :model-value="filters.roomIds"
                    @update:model-value="updateRoomIds"
                    placeholder="Toutes les salles"
                />
            </div>

            <!-- Date de début -->
            <div>
                <Label for="date_start">Date de début</Label>
                <Input
                    id="date_start"
                    type="date"
                    :value="filters.dateStart"
                    @input="updateDateStart"
                />
            </div>

            <!-- Date de fin -->
            <div>
                <Label for="date_end">Date de fin</Label>
                <Input
                    id="date_end"
                    type="date"
                    :value="filters.dateEnd"
                    @input="updateDateEnd"
                    :min="filters.dateStart"
                />
            </div>
        </div>

        <!-- Bouton de réinitialisation -->
        <div v-if="hasActiveFilters" class="flex justify-end">
            <Button
                variant="outline"
                size="sm"
                @click="emit('clear')"
                :disabled="loading"
            >
                <X class="mr-2 h-4 w-4" />
                Réinitialiser les filtres
            </Button>
        </div>
    </div>
</template>
