<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'bg-card text-card-foreground surface-shadow flex flex-col gap-6 rounded-[var(--radius-surface)] border py-6 shadow-sm',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
