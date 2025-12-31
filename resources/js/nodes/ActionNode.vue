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
        class="action-node px-4 py-3 rounded-lg border-2 min-w-[180px]"
        :class="{
            'border-action-500 bg-action-50': true,
            'ring-2 ring-action-500 ring-offset-2': selected,
        }"
    >
        <!-- Input Handle (top) -->
        <Handle
            type="target"
            :position="Position.Top"
            class="!bg-action-500 !border-white !w-3 !h-3"
        />

        <!-- Header -->
        <div class="flex items-center gap-2 mb-2">
            <LucideIcon :name="data.icon" :size="20" class="text-action-600" />
            <span class="font-medium text-action-700 text-sm">{{ data.label }}</span>
        </div>

        <!-- Config Preview -->
        <div v-if="Object.keys(data.config || {}).length" class="text-xs text-action-600 bg-action-100 rounded px-2 py-1">
            <template v-for="(value, key) in data.config" :key="key">
                <div class="truncate" v-if="value">
                    <span class="font-medium">{{ key }}:</span>
                    <template v-if="typeof value === 'object'">
                        {{ JSON.stringify(value).substring(0, 30) }}...
                    </template>
                    <template v-else>
                        {{ String(value).substring(0, 30) }}{{ String(value).length > 30 ? '...' : '' }}
                    </template>
                </div>
            </template>
        </div>

        <!-- Output Handle (bottom) -->
        <Handle
            type="source"
            :position="Position.Bottom"
            class="!bg-action-500 !border-white !w-3 !h-3"
        />
    </div>
</template>

<style scoped>
.action-node {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>
