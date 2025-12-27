import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export function useRooms() {
    const rooms = ref<any[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    const activeRooms = computed(() => 
        rooms.value.filter(room => room.is_active)
    );

    const inactiveRooms = computed(() => 
        rooms.value.filter(room => !room.is_active)
    );

    /**
     * Fetch all rooms
     */
    const fetchRooms = async () => {
        loading.value = true;
        error.value = null;
        
        try {
            const response = await fetch('/api/rooms');
            const data = await response.json();
            rooms.value = data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Erreur lors du chargement des salles';
        } finally {
            loading.value = false;
        }
    };

    /**
     * Filter rooms by criteria
     */
    const filterRooms = (criteria: {
        capacite?: number;
        etage?: number;
        equipement?: string[];
    }) => {
        return computed(() => {
            let filtered = activeRooms.value;

            if (criteria.capacite) {
                filtered = filtered.filter(room => room.capacite >= criteria.capacite!);
            }

            if (criteria.etage !== undefined) {
                filtered = filtered.filter(room => room.etage === criteria.etage);
            }

            if (criteria.equipement && criteria.equipement.length > 0) {
                filtered = filtered.filter(room => 
                    criteria.equipement!.every((eq: string) => 
                        room.equipement?.includes(eq)
                    )
                );
            }

            return filtered;
        });
    };

    /**
     * Get unique equipments from all rooms
     */
    const uniqueEquipments = computed(() => {
        const equipments = new Set<string>();
        activeRooms.value.forEach(room => {
            room.equipement?.forEach((eq: string) => equipments.add(eq));
        });
        return Array.from(equipments).sort();
    });

    /**
     * Get unique floors from all rooms
     */
    const uniqueFloors = computed(() => {
        const floors = new Set<number>();
        activeRooms.value.forEach(room => floors.add(room.etage));
        return Array.from(floors).sort((a, b) => a - b);
    });

    /**
     * Create a new room (admin)
     */
    const createRoom = (data: any) => {
        router.post('/admin/rooms', data as any, {
            onSuccess: () => {
                // Success handled by Inertia
            },
            onError: () => {
                error.value = 'Erreur lors de la création de la salle';
            },
        });
    };

    /**
     * Update a room (admin)
     */
    const updateRoom = (id: number, data: any) => {
        router.put(`/admin/rooms/${id}`, data as any, {
            onSuccess: () => {
                // Success handled by Inertia
            },
            onError: () => {
                error.value = 'Erreur lors de la mise à jour de la salle';
            },
        });
    };

    /**
     * Toggle room active status (admin)
     */
    const toggleRoomStatus = (id: number) => {
        router.post(`/admin/rooms/${id}/toggle-active`, {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    /**
     * Delete a room (admin)
     */
    const deleteRoom = (id: number) => {
        router.delete(`/admin/rooms/${id}`, {
            onSuccess: () => {
                // Success handled by Inertia
            },
            onError: () => {
                error.value = 'Erreur lors de la suppression de la salle';
            },
        });
    };

    return {
        rooms,
        loading,
        error,
        activeRooms,
        inactiveRooms,
        uniqueEquipments,
        uniqueFloors,
        fetchRooms,
        filterRooms,
        createRoom,
        updateRoom,
        toggleRoomStatus,
        deleteRoom,
    };
}
