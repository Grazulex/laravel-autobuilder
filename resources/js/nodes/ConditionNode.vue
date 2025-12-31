<script setup>
import { Handle, Position } from '@vue-flow/core'
import LucideIcon from '../components/LucideIcon.vue'

defineProps({
    data: { type: Object, required: true },
    selected: { type: Boolean, default: false },
})
</script>

<template>
    <div
        class="condition-node px-4 py-3 rounded-lg border-2 min-w-[180px]"
        :class="{
            'border-condition-500 bg-condition-50': true,
            'ring-2 ring-condition-500 ring-offset-2': selected,
        }"
    >
        <!-- Input Handle (top) -->
        <Handle
            type="target"
            :position="Position.Top"
            class="!bg-condition-500 !border-white !w-3 !h-3"
        />

        <!-- Header -->
        <div class="flex items-center gap-2 mb-2">
            <LucideIcon :name="data.icon" :size="20" class="text-condition-600" />
            <span class="font-medium text-condition-700 text-sm">{{ data.label }}</span>
        </div>

        <!-- Config Preview -->
        <div v-if="Object.keys(data.config || {}).length" class="text-xs text-condition-600 bg-condition-100 rounded px-2 py-1 mb-2">
            <template v-for="(value, key) in data.config" :key="key">
                <div class="truncate" v-if="value">
                    <span class="font-medium">{{ key }}:</span> {{ value }}
                </div>
            </template>
        </div>

        <!-- Branch Labels -->
        <div class="flex justify-between text-xs mt-2">
            <span class="text-green-600 font-medium">True</span>
            <span class="text-red-600 font-medium">False</span>
        </div>

        <!-- Output Handles (bottom) - True left, False right -->
        <Handle
            id="true"
            type="source"
            :position="Position.Bottom"
            :style="{ left: '25%' }"
            class="!bg-green-500 !border-white !w-3 !h-3"
        />
        <Handle
            id="false"
            type="source"
            :position="Position.Bottom"
            :style="{ left: '75%' }"
            class="!bg-red-500 !border-white !w-3 !h-3"
        />
    </div>
</template>

<style scoped>
.condition-node {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>
