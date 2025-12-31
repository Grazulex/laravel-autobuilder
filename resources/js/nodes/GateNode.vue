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
        class="gate-node px-4 py-3 rounded-lg border-2 min-w-[160px]"
        :class="{
            'border-purple-500 bg-purple-50': true,
            'ring-2 ring-purple-500 ring-offset-2': selected,
        }"
    >
        <!-- Input Handle (top) - accepts multiple connections -->
        <Handle
            type="target"
            :position="Position.Top"
            class="!bg-purple-500 !border-white !w-4 !h-4"
        />

        <!-- Header -->
        <div class="flex items-center gap-2 mb-2">
            <LucideIcon :name="data.icon" :size="20" class="text-purple-600" />
            <span class="font-medium text-purple-700 text-sm">{{ data.label }}</span>
        </div>

        <!-- Gate Type Indicator -->
        <div class="text-xs text-purple-600 bg-purple-100 rounded px-2 py-1 mb-2 text-center font-mono">
            {{ data.label === 'AND Gate' ? 'ALL must pass' : 'ANY must pass' }}
        </div>

        <!-- Branch Labels -->
        <div class="flex justify-between text-xs mt-2">
            <span class="text-green-600 font-medium">Pass</span>
            <span class="text-red-600 font-medium">Fail</span>
        </div>

        <!-- Output Handles (bottom) - Pass left, Fail right -->
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
.gate-node {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>
