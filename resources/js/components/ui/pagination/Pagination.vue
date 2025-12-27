<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { ChevronLeft, ChevronRight, MoreHorizontal } from 'lucide-vue-next'
import { computed } from 'vue'

const props = defineProps<{
    total: number
    currentPage: number
    perPage?: number
    siblingCount?: number
}>()

const emit = defineEmits<{
    (e: 'update:currentPage', page: number): void
}>()

const totalPages = computed(() => Math.ceil(props.total / (props.perPage || 10)))

const pages = computed(() => {
    const pages: (number | 'ellipsis')[] = []
    const siblingCount = props.siblingCount || 1
    const totalPageNumbers = siblingCount + 5

    if (totalPages.value <= totalPageNumbers) {
        for (let i = 1; i <= totalPages.value; i++) {
            pages.push(i)
        }
    } else {
        const leftSiblingIndex = Math.max(props.currentPage - siblingCount, 1)
        const rightSiblingIndex = Math.min(props.currentPage + siblingCount, totalPages.value)

        const shouldShowLeftDots = leftSiblingIndex > 2
        const shouldShowRightDots = rightSiblingIndex < totalPages.value - 1

        if (!shouldShowLeftDots && shouldShowRightDots) {
            const leftItemCount = 3 + 2 * siblingCount
            for (let i = 1; i <= leftItemCount; i++) {
                pages.push(i)
            }
            pages.push('ellipsis')
            pages.push(totalPages.value)
        } else if (shouldShowLeftDots && !shouldShowRightDots) {
            pages.push(1)
            pages.push('ellipsis')
            const rightItemCount = 3 + 2 * siblingCount
            for (let i = totalPages.value - rightItemCount + 1; i <= totalPages.value; i++) {
                pages.push(i)
            }
        } else if (shouldShowLeftDots && shouldShowRightDots) {
            pages.push(1)
            pages.push('ellipsis')
            for (let i = leftSiblingIndex; i <= rightSiblingIndex; i++) {
                pages.push(i)
            }
            pages.push('ellipsis')
            pages.push(totalPages.value)
        }
    }

    return pages
})

const goToPage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        emit('update:currentPage', page)
    }
}
</script>

<template>
    <div class="flex items-center gap-1">
        <Button
            variant="outline"
            size="icon"
            class="h-10 w-10 cursor-pointer"
            :disabled="currentPage === 1"
            @click="goToPage(currentPage - 1)"
        >
            <ChevronLeft class="h-4 w-4" />
        </Button>

        <template v-for="(page, index) in pages" :key="index">
            <Button
                v-if="page !== 'ellipsis'"
                variant="outline"
                class="h-10 w-10 cursor-pointer"
                :class="{ 'bg-msl-primary text-white hover:bg-msl-secondary border-msl-primary': page === currentPage }"
                @click="goToPage(page)"
            >
                {{ page }}
            </Button>

            <div
                v-else
                class="flex h-10 w-10 items-center justify-center"
            >
                <MoreHorizontal class="h-4 w-4" />
            </div>
        </template>

        <Button
            variant="outline"
            size="icon"
            class="h-10 w-10 cursor-pointer"
            :disabled="currentPage === totalPages"
            @click="goToPage(currentPage + 1)"
        >
            <ChevronRight class="h-4 w-4" />
        </Button>
    </div>
</template>
