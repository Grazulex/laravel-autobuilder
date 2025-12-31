<script setup>
import { computed, inject } from 'vue'
import { getBezierPath, EdgeLabelRenderer } from '@vue-flow/core'

const deleteEdge = inject('deleteEdge', null)

const props = defineProps({
    id: { type: String, required: true },
    sourceX: { type: Number, required: true },
    sourceY: { type: Number, required: true },
    targetX: { type: Number, required: true },
    targetY: { type: Number, required: true },
    sourcePosition: { type: String, default: 'bottom' },
    targetPosition: { type: String, default: 'top' },
    sourceHandleId: { type: String, default: null },
    selected: { type: Boolean, default: false },
    markerEnd: { type: String, default: '' },
    style: { type: Object, default: () => ({}) },
})

function handleDelete() {
    if (deleteEdge) {
        deleteEdge(props.id)
    }
}

const path = computed(() => {
    const [edgePath, labelX, labelY] = getBezierPath({
        sourceX: props.sourceX,
        sourceY: props.sourceY,
        sourcePosition: props.sourcePosition,
        targetX: props.targetX,
        targetY: props.targetY,
        targetPosition: props.targetPosition,
    })
    return { edgePath, labelX, labelY }
})

const label = computed(() => {
    if (props.sourceHandleId === 'true') return 'True'
    if (props.sourceHandleId === 'false') return 'False'
    return null
})

const labelColor = computed(() => {
    if (props.sourceHandleId === 'true') return 'bg-green-500 text-white'
    if (props.sourceHandleId === 'false') return 'bg-red-500 text-white'
    return 'bg-gray-500 text-white'
})

const strokeColor = computed(() => {
    if (props.selected) return '#3b82f6'
    if (props.sourceHandleId === 'true') return '#22c55e'
    if (props.sourceHandleId === 'false') return '#ef4444'
    return '#9ca3af'
})
</script>

<template>
    <path
        :id="id"
        class="vue-flow__edge-path"
        :d="path.edgePath"
        :marker-end="markerEnd"
        :style="{
            stroke: strokeColor,
            strokeWidth: selected ? 3 : 2,
            ...style
        }"
    />

    <!-- Label -->
    <EdgeLabelRenderer>
        <div
            v-if="label"
            :style="{
                position: 'absolute',
                transform: `translate(-50%, -50%) translate(${path.labelX}px, ${path.labelY}px)`,
                pointerEvents: 'none',
            }"
            class="nodrag nopan"
        >
            <span
                :class="[
                    'px-2 py-0.5 text-xs font-medium rounded shadow-sm',
                    labelColor
                ]"
            >
                {{ label }}
            </span>
        </div>

        <!-- Delete button (shown when selected) -->
        <div
            v-if="selected"
            :style="{
                position: 'absolute',
                transform: `translate(-50%, -50%) translate(${path.labelX}px, ${path.labelY + (label ? 20 : 0)}px)`,
                pointerEvents: 'all',
            }"
            class="nodrag nopan"
        >
            <button
                @click.stop="handleDelete"
                class="p-1 bg-red-500 text-white rounded-full shadow hover:bg-red-600 transition-colors"
                title="Delete connection"
            >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </EdgeLabelRenderer>
</template>
