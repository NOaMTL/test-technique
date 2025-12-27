<script setup lang="ts">
import { cn } from '@/lib/utils'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { computed, ref } from 'vue'

const props = defineProps<{
    modelValue?: Date
    disableWeekends?: boolean
    minDate?: Date
    maxDate?: Date
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: Date): void
}>()

const currentMonth = ref(props.modelValue ? new Date(props.modelValue) : new Date())
currentMonth.value.setDate(1) // Premier jour du mois

const monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']
const dayNames = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

const heading = computed(() => {
    return `${monthNames[currentMonth.value.getMonth()]} ${currentMonth.value.getFullYear()}`
})

const weeks = computed(() => {
    const firstDay = new Date(currentMonth.value)
    firstDay.setDate(1)
    const lastDay = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + 1, 0)
    
    const startDate = new Date(firstDay)
    // Commence au lundi (1 = lundi, 0 = dimanche)
    const dayOfWeek = firstDay.getDay()
    const diff = dayOfWeek === 0 ? -6 : 1 - dayOfWeek
    startDate.setDate(startDate.getDate() + diff)
    
    const weeksArray: any[] = []
    let currentDate = new Date(startDate)
    
    while (currentDate <= lastDay || weeksArray.length < 6) {
        const week: any[] = []
        for (let i = 0; i < 7; i++) {
            const date = new Date(currentDate)
            const isOutsideMonth = date.getMonth() !== currentMonth.value.getMonth()
            const isToday = date.toDateString() === new Date().toDateString()
            const isSelected = props.modelValue && date.toDateString() === props.modelValue.toDateString()
            
            let disabled = false
            if (props.disableWeekends && (date.getDay() === 0 || date.getDay() === 6)) {
                disabled = true
            }
            if (props.minDate && date < props.minDate) {
                disabled = true
            }
            if (props.maxDate && date > props.maxDate) {
                disabled = true
            }
            
            week.push({
                date,
                isOutsideMonth,
                isToday,
                selected: isSelected,
                disabled
            })
            currentDate.setDate(currentDate.getDate() + 1)
        }
        weeksArray.push(week)
        
        if (weeksArray.length === 6) break
    }
    
    return weeksArray
})

const prevPage = () => {
    currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() - 1, 1)
}

const nextPage = () => {
    currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + 1, 1)
}

const selectDate = (day: any) => {
    if (!day.disabled) {
        emit('update:modelValue', day.date)
    }
}
</script>

<template>
    <div class="rounded-md border p-3">
        <header class="flex items-center justify-between mb-4">
            <button
                type="button"
                class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground h-8 w-8 p-0 cursor-pointer"
                @click="prevPage"
            >
                <ChevronLeft class="h-4 w-4" />
            </button>
            <div class="font-medium">
                {{ heading }}
            </div>
            <button
                type="button"
                class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground h-8 w-8 p-0 cursor-pointer"
                @click="nextPage"
            >
                <ChevronRight class="h-4 w-4" />
            </button>
        </header>
        <div class="space-y-4">
            <table class="w-full border-collapse space-y-1">
                <thead>
                    <tr class="flex">
                        <th
                            v-for="day in dayNames"
                            :key="day"
                            class="text-muted-foreground rounded-md flex-1 font-normal text-xs"
                        >
                            {{ day }}
                        </th>
                    </tr>
                </thead>
                <tbody class="space-y-1">
                    <tr
                        v-for="(week, weekIndex) in weeks"
                        :key="`week-${weekIndex}`"
                        class="flex w-full mt-2"
                    >
                        <td
                            v-for="(day, dayIndex) in week"
                            :key="`day-${dayIndex}`"
                            class="relative p-0 text-center text-sm flex-1"
                        >
                            <button
                                type="button"
                                :disabled="day.disabled"
                                :class="cn(
                                    'inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors',
                                    'w-full h-9 p-0',
                                    day.disabled && 'text-muted-foreground opacity-50 cursor-not-allowed',
                                    day.isToday && !day.selected && 'bg-accent text-accent-foreground',
                                    day.selected && 'bg-msl-primary text-white hover:bg-msl-secondary',
                                    !day.selected && !day.disabled && 'hover:bg-accent hover:text-accent-foreground cursor-pointer',
                                    day.isOutsideMonth && 'text-muted-foreground opacity-50'
                                )"
                                @click="selectDate(day)"
                            >
                                {{ day.date.getDate() }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
