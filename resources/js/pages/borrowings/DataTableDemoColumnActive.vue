<script setup>
import { MoreHorizontal } from 'lucide-vue-next'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu/index.ts'
import { Button } from '@/components/ui/button/index.ts'

const props = defineProps({
    transaction: Object,
    onReturn: Function,
})

const returnBook = (id) => {
    props.onReturn(id);
}

</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" class="w-8 h-8 p-0">
                <span class="sr-only">Open menu</span>
                <MoreHorizontal class="w-4 h-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuLabel>Actions</DropdownMenuLabel>
            <DropdownMenuItem @click="returnBook(transaction.id)">
                Return Book
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
