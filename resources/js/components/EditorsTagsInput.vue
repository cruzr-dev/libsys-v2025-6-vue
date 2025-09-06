<script setup lang="ts">
import { TagsInput, TagsInputInput, TagsInputItem, TagsInputItemDelete, TagsInputItemText } from "@/components/ui/tags-input"
import { computed } from "vue"

const props = defineProps<{
    modelValue: string[]
}>()

const emit = defineEmits<{
    (e: "update:modelValue", value: string[]): void
}>()

// Use computed for direct binding - no local state needed
const localValue = computed({
    get: () => props.modelValue,
    set: (value: string[]) => emit("update:modelValue", value)
})
</script>

<template>
    <TagsInput v-model="localValue" delimiter=";">
        <TagsInputItem class="bg-accent" v-for="item in localValue" :key="item" :value="item">
            <TagsInputItemText />
            <TagsInputItemDelete />
        </TagsInputItem>

        <TagsInputInput placeholder="Editors..." />
    </TagsInput>
</template>
