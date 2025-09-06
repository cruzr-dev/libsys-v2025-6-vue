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
    <Combobox v-model="selected" by="key">
        <ComboboxAnchor>
            <div class="relative w-full max-w-sm items-center">
                <ComboboxInput
                    :display-value="(val) => val?.name ?? ''"
                    placeholder="Select cover type..."
                />
                <ComboboxTrigger
                    class="absolute end-0 inset-y-0 flex items-center justify-center px-3"
                >
                    <ChevronsUpDown class="size-4 text-muted-foreground" />
                </ComboboxTrigger>
            </div>
        </ComboboxAnchor>

        <ComboboxList>
            <ComboboxEmpty> Nothing found. </ComboboxEmpty>

            <ComboboxGroup>
                <ComboboxItem
                    v-for="cover in coverTypes"
                    :key="cover.key"
                    :value="cover"
                >
                    {{ cover.name }}

                    <ComboboxItemIndicator>
                        <Check :class="cn('ml-auto h-4 w-4')" />
                    </ComboboxItemIndicator>
                </ComboboxItem>
            </ComboboxGroup>
        </ComboboxList>
    </Combobox>

    <p class="mt-2 text-sm text-gray-500">
        Selected: {{ selected?.name || "None" }}
    </p>
</template>
