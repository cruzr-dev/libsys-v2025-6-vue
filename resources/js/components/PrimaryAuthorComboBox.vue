<script setup lang="ts">
import { Check, Search } from "lucide-vue-next"
import { cn } from "@/utils"
import {
    Combobox,
    ComboboxAnchor,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxList
} from "@/components/ui/combobox"

// Example authors data
const authors = [
    { name: "Charles Eliot", author_number: "E42" },
    { name: "Robert Maynard Hutchins", author_number: "H97" },
    { name: "Alfredo R. Roces", author_number: "R56" },
    { name: "Lucila L. Salcedo", author_number: "S23" },
    { name: "William Lambe", author_number: "L34" },
]
</script>

<template>
    <Combobox by="name">
        <ComboboxAnchor class="border w-full">
            <div class="relative w-full max-w-sm items-center">
                <ComboboxInput
                    class="pl-2"
                    :display-value="(val) => val ? `${val.name} (${val.author_number})` : ''"
                    placeholder="Search author..."
                />
                <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
          <Search class="size-4 text-muted-foreground" />
        </span>
            </div>
        </ComboboxAnchor>

        <ComboboxList>
            <ComboboxEmpty>
                No author found.
            </ComboboxEmpty>

            <ComboboxGroup>
                <ComboboxItem
                    v-for="author in authors"
                    :key="author.author_number"
                    :value="author"
                >
                    {{ author.name }} ({{ author.author_number }})

                    <ComboboxItemIndicator>
                        <Check :class="cn('ml-auto h-4 w-4')" />
                    </ComboboxItemIndicator>
                </ComboboxItem>
            </ComboboxGroup>
        </ComboboxList>
    </Combobox>
</template>
