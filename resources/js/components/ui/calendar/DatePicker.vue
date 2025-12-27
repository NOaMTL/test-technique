<script setup lang="ts">
import { ref, watch } from 'vue'
import { Calendar as CalendarIcon } from 'lucide-vue-next'
import { Calendar } from '@/components/ui/calendar'
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog'

const props = defineProps<{
    modelValue?: string
    disabled?: boolean
    placeholder?: string
    disableWeekends?: boolean
    minDate?: string
    maxDate?: string
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

const open = ref(false)
const selectedDate = ref<Date | undefined>(
    props.modelValue ? new Date(props.modelValue) : undefined
)

watch(() => props.modelValue, (newValue) => {
    selectedDate.value = newValue ? new Date(newValue) : undefined
})

const handleDateSelect = (date: Date) => {
    selectedDate.value = date
    const formatted = date.toISOString().split('T')[0]
    emit('update:modelValue', formatted)
    open.value = false
}

const formattedDate = () => {
    if (!selectedDate.value) return props.placeholder || 'Sélectionner une date'
    return selectedDate.value.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    })
}

const minDateObj = props.minDate ? new Date(props.minDate) : undefined
const maxDateObj = props.maxDate ? new Date(props.maxDate) : undefined
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button
                variant="outline"
                :disabled="disabled"
                class="w-full justify-start text-left font-normal"
                type="button"
            >
                <CalendarIcon class="mr-2 h-4 w-4" />
                {{ formattedDate() }}
            </Button>
        </DialogTrigger>
        <DialogContent class="max-w-auto">
            <DialogHeader>
                <DialogTitle>Sélectionner une date</DialogTitle>
            </DialogHeader>
            <Calendar
                :model-value="selectedDate"
                :disable-weekends="disableWeekends"
                :min-date="minDateObj"
                :max-date="maxDateObj"
                @update:model-value="handleDateSelect"
            />
        </DialogContent>
    </Dialog>
</template>
