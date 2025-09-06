<script setup lang="ts">
import { ref } from "vue"
import { Check, ChevronsUpDown } from "lucide-vue-next"
import { cn } from "@/utils"
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList,
    ComboboxTrigger,
} from "@/components/ui/combobox"

const coverTypes = [
    { key: "hc", name: "Hardcover" },
    { key: "pb", name: "Paperback" },
    { key: "sc", name: "Softcover" },
    { key: "dj", name: "Dust Jacket" },
    { key: "sl", name: "Slipcase" },
]

const selected = ref<typeof coverTypes[number] | null>(null)
</script>

<template>
    <Combobox v-model="selected" by="key" class="w-full">
        <ComboboxAnchor class="w-full">
            <div
                class="relative w-full flex items-center rounded-md border border-input bg-background px-3 text-sm ring-offset-background focus-within:outline-none focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2"
            >
                <ComboboxInput
                    :display-value="(val) => val?.name ?? ''"
                    placeholder="Select cover type..."
                    class="flex-1 bg-transparent outline-none placeholder:text-muted-foreground"
                />
                <ComboboxTrigger
                    class="absolute end-0 inset-y-0 flex items-center justify-center px-3"
                >
                    <ChevronsUpDown class="size-4 text-muted-foreground" />
                </ComboboxTrigger>
            </div>
        </ComboboxAnchor>

        <ComboboxList>
            <ComboboxEmpty>Nothing found.</ComboboxEmpty>

            <ComboboxGroup>
                <ComboboxItem
                    v-for="cover in coverTypes"
                    :key="cover.key"
                    :value="cover"
                    class="flex items-center gap-2"
                >
                    {{ cover.name }}
                    <ComboboxItemIndicator>
                        <Check class="ml-auto h-4 w-4" />
                    </ComboboxItemIndicator>
                </ComboboxItem>
            </ComboboxGroup>
        </ComboboxList>
    </Combobox>
</template>
