<script setup lang="ts">
import { ref, onMounted, watch } from "vue"
import { Check, ChevronsUpDown } from "lucide-vue-next"
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

// Define the interface for cover types
interface CoverType {
    id: number
    key: string
    name: string
}

// Props and emits
const props = defineProps<{
    coverTypeId: number | null
}>()
const emit = defineEmits<{
    (e: "update:coverTypeId", value: number | null): void
}>()

// Reactive state
const coverTypes = ref<CoverType[]>([])
const selected = ref<CoverType | null>(null)

// Emit id when selection changes
watch(selected, (val) => {
    emit("update:coverTypeId", val?.id ?? null)
})

// Watch coverTypeId prop to set initial selection
watch(
    () => props.coverTypeId,
    (newId) => {
        if (newId !== null && coverTypes.value.length > 0) {
            const match = coverTypes.value.find((cover) => cover.id === newId)
            selected.value = match ?? null
        } else {
            selected.value = null
        }
    },
    { immediate: true }
)

// Fetch cover types
const fetchCoverTypes = async () => {
    try {
        const response = await fetch("/api/books/cover-types", {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        })
        if (!response.ok) throw new Error("Failed to fetch cover types")
        const data = await response.json()
        coverTypes.value = data

        // Set initial selection after fetching cover types
        if (props.coverTypeId !== null) {
            const match = coverTypes.value.find((cover) => cover.id === props.coverTypeId)
            selected.value = match ?? null
        }
    } catch (error) {
        console.error("Error fetching cover types:", error)
    }
}

onMounted(fetchCoverTypes)

</script>

<template>
    <Combobox v-model="selected" by="id" class="w-full">
        <ComboboxAnchor class="w-full">
            <div
                class="relative w-full flex items-center rounded-md border border-input bg-background px-3 text-sm ring-offset-background focus-within:outline-none focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2"
            >
                <ComboboxInput
                    required
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
                    :key="cover.id"
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
