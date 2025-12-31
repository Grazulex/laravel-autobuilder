<script setup>
import { computed } from 'vue'
import * as icons from 'lucide-vue-next'

const props = defineProps({
    name: { type: String, required: true },
    size: { type: [Number, String], default: 20 },
    strokeWidth: { type: [Number, String], default: 2 },
    class: { type: String, default: '' },
})

// Convert kebab-case to PascalCase for icon lookup
function toPascalCase(str) {
    return str
        .split('-')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join('')
}

const iconComponent = computed(() => {
    const pascalName = toPascalCase(props.name)
    return icons[pascalName] || icons['HelpCircle']
})
</script>

<template>
    <component
        :is="iconComponent"
        :size="size"
        :stroke-width="strokeWidth"
        :class="props.class"
    />
</template>
