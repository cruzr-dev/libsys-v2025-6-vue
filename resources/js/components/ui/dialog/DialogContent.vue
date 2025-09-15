<script setup lang="ts">
import { cn } from '@/lib/utils'
import { X } from 'lucide-vue-next'
import {
  DialogClose,
  DialogContent,
  type DialogContentEmits,
  type DialogContentProps,
  DialogPortal,
  useForwardPropsEmits,
} from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'
import DialogOverlay from './DialogOverlay.vue'

const props = defineProps<DialogContentProps & { class?: HTMLAttributes['class'] }>()
const emits = defineEmits<DialogContentEmits>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

const forwarded = useForwardPropsEmits(delegatedProps, emits)

// Detect if consumer provided their own max-w-* utility so we don't enforce the default sm:max-w-lg.
const hasCustomMaxWidth = computed(() => {
    if (!props.class) return false
    if (typeof props.class === 'string') return /(^|\s)max-w-/.test(props.class)
    // Basic handling for array/object forms (best-effort)
    if (Array.isArray(props.class)) return props.class.some(c => typeof c === 'string' && /(^|\s)max-w-/.test(c))
    if (typeof props.class === 'object') return Object.keys(props.class).some(k => props.class[k] && /(^|\s)max-w-/.test(k))
    return false
})
</script>

<template>
  <DialogPortal>
    <DialogOverlay />
    <DialogContent
      data-slot="dialog-content"
      v-bind="forwarded"
      :class="
        cn(
          // Base styling & positioning
          'bg-background data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 fixed top-[50%] left-[50%] z-50 grid w-full translate-x-[-50%] translate-y-[-50%] gap-4 rounded-lg border p-6 shadow-lg duration-200',
          // Provide a sensible default width only if consumer did not supply their own max-w-* class
          !hasCustomMaxWidth ? 'max-w-[calc(100%-2rem)] sm:max-w-lg' : '',
          props.class,
        )
      "
    >
        <slot />

      <DialogClose
        class="ring-offset-background focus:ring-ring data-[state=open]:bg-accent data-[state=open]:text-muted-foreground absolute top-4 right-4 rounded-xs opacity-70 transition-opacity hover:opacity-100 focus:ring-2 focus:ring-offset-2 focus:outline-hidden disabled:pointer-events-none [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4"
      >
        <X />
        <span class="sr-only">Close</span>
      </DialogClose>
    </DialogContent>
  </DialogPortal>
</template>
