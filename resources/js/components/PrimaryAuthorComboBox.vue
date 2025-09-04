<script setup lang="ts">
import { ref, onMounted } from "vue"
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
    ComboboxList,
} from "@/components/ui/combobox"

// reactive list for authors
const authors = ref<{ value: string; label: string }[]>([])

// selected value
const selectedAuthor = ref<{ value: string; label: string } | null>(null)

// fetch authors from API
const fetchAuthors = async () => {
    try {
        const res = await fetch("/api/authors") // change URL to your endpoint
        const data = await res.json()

        // map API response into { value, label }
        authors.value = data.map((author: any) => ({
            value: author.id, // or whatever field identifies the author
            label: author.name,
        }))
    } catch (err) {
        console.error("Failed to load authors:", err)
    }
}

onMounted(() => {
    fetchAuthors()
})
</script>

<template>
    <Combobox v-model="selectedAuthor" by="label">
        <ComboboxAnchor>
            <div class="relative w-full max-w-sm items-center">
                <ComboboxInput
                    class="pl-2 border"
                    :display-value="(val) => val?.label ?? ''"
                    placeholder="Select author..."
                />
                <span class="absolute start-0 inset-y-0 flex items-center justify-center px-3">
          <Search class="size-4 text-muted-foreground" />
        </span>
            </div>
        </ComboboxAnchor>

        <ComboboxList>
            <ComboboxEmpty>No author found.</ComboboxEmpty>

            <ComboboxGroup>
                <ComboboxItem
                    v-for="author in authors"
                    :key="author.value"
                    :value="author"
                >
                    {{ author.label }}

                    <ComboboxItemIndicator>
                        <Check :class="cn('ml-auto h-4 w-4')" />
                    </ComboboxItemIndicator>
                </ComboboxItem>
            </ComboboxGroup>
        </ComboboxList>
    </Combobox>
</template>
