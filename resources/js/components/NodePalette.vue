<script setup>
import { ref, computed } from 'vue'
import LucideIcon from './LucideIcon.vue'

const props = defineProps({
    bricks: {
        type: Object,
        default: () => ({ triggers: [], conditions: [], actions: [], gates: [] }),
    },
})

const searchQuery = ref('')
const expandedCategories = ref(['triggers', 'conditions', 'actions', 'gates'])

function toggleCategory(category) {
    const index = expandedCategories.value.indexOf(category)
    if (index === -1) {
        expandedCategories.value.push(category)
    } else {
        expandedCategories.value.splice(index, 1)
    }
}

function filterBricks(bricks) {
    if (!searchQuery.value) return bricks
    const query = searchQuery.value.toLowerCase()
    return bricks.filter(brick =>
        brick.name.toLowerCase().includes(query) ||
        brick.description?.toLowerCase().includes(query)
    )
}

const filteredTriggers = computed(() => filterBricks(props.bricks.triggers || []))
const filteredConditions = computed(() => filterBricks(props.bricks.conditions || []))
const filteredActions = computed(() => filterBricks(props.bricks.actions || []))
const filteredGates = computed(() => filterBricks(props.bricks.gates || []))

function onDragStart(event, brick, type) {
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('application/json', JSON.stringify({
        ...brick,
        type,
    }))
}

const categoryColors = {
    triggers: 'bg-trigger-500',
    conditions: 'bg-condition-500',
    actions: 'bg-action-500',
}

const categoryIcons = {
    triggers: 'M13 10V3L4 14h7v7l9-11h-7z',
    conditions: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    actions: 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
}
</script>

<template>
    <aside class="w-64 bg-white border-r flex flex-col">
        <!-- Search -->
        <div class="p-3 border-b">
            <div class="relative">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search bricks..."
                    class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>

        <!-- Brick Categories -->
        <div class="flex-1 overflow-y-auto">
            <!-- Triggers -->
            <div class="border-b">
                <button
                    @click="toggleCategory('triggers')"
                    class="w-full px-3 py-2 flex items-center justify-between hover:bg-gray-50"
                >
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-trigger-500"></div>
                        <span class="text-sm font-medium text-gray-700">Triggers</span>
                        <span class="text-xs text-gray-400">({{ filteredTriggers.length }})</span>
                    </div>
                    <svg
                        class="w-4 h-4 text-gray-400 transition-transform"
                        :class="{ 'rotate-180': expandedCategories.includes('triggers') }"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div v-show="expandedCategories.includes('triggers')" class="px-3 pb-3 space-y-1">
                    <div
                        v-for="brick in filteredTriggers"
                        :key="brick.class"
                        :draggable="true"
                        @dragstart="onDragStart($event, brick, 'trigger')"
                        class="palette-item trigger"
                    >
                        <LucideIcon :name="brick.icon" :size="18" class="text-trigger-600" />
                        <span class="text-sm text-gray-700 truncate">{{ brick.name }}</span>
                    </div>
                    <p v-if="filteredTriggers.length === 0" class="text-xs text-gray-400 py-2 text-center">
                        No triggers found
                    </p>
                </div>
            </div>

            <!-- Conditions -->
            <div class="border-b">
                <button
                    @click="toggleCategory('conditions')"
                    class="w-full px-3 py-2 flex items-center justify-between hover:bg-gray-50"
                >
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-condition-500"></div>
                        <span class="text-sm font-medium text-gray-700">Conditions</span>
                        <span class="text-xs text-gray-400">({{ filteredConditions.length }})</span>
                    </div>
                    <svg
                        class="w-4 h-4 text-gray-400 transition-transform"
                        :class="{ 'rotate-180': expandedCategories.includes('conditions') }"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div v-show="expandedCategories.includes('conditions')" class="px-3 pb-3 space-y-1">
                    <div
                        v-for="brick in filteredConditions"
                        :key="brick.class"
                        :draggable="true"
                        @dragstart="onDragStart($event, brick, 'condition')"
                        class="palette-item condition"
                    >
                        <LucideIcon :name="brick.icon" :size="18" class="text-condition-600" />
                        <span class="text-sm text-gray-700 truncate">{{ brick.name }}</span>
                    </div>
                    <p v-if="filteredConditions.length === 0" class="text-xs text-gray-400 py-2 text-center">
                        No conditions found
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="border-b">
                <button
                    @click="toggleCategory('actions')"
                    class="w-full px-3 py-2 flex items-center justify-between hover:bg-gray-50"
                >
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-action-500"></div>
                        <span class="text-sm font-medium text-gray-700">Actions</span>
                        <span class="text-xs text-gray-400">({{ filteredActions.length }})</span>
                    </div>
                    <svg
                        class="w-4 h-4 text-gray-400 transition-transform"
                        :class="{ 'rotate-180': expandedCategories.includes('actions') }"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div v-show="expandedCategories.includes('actions')" class="px-3 pb-3 space-y-1">
                    <div
                        v-for="brick in filteredActions"
                        :key="brick.class"
                        :draggable="true"
                        @dragstart="onDragStart($event, brick, 'action')"
                        class="palette-item action"
                    >
                        <LucideIcon :name="brick.icon" :size="18" class="text-action-600" />
                        <span class="text-sm text-gray-700 truncate">{{ brick.name }}</span>
                    </div>
                    <p v-if="filteredActions.length === 0" class="text-xs text-gray-400 py-2 text-center">
                        No actions found
                    </p>
                </div>
            </div>

            <!-- Gates -->
            <div>
                <button
                    @click="toggleCategory('gates')"
                    class="w-full px-3 py-2 flex items-center justify-between hover:bg-gray-50"
                >
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-purple-500"></div>
                        <span class="text-sm font-medium text-gray-700">Gates</span>
                        <span class="text-xs text-gray-400">({{ filteredGates.length }})</span>
                    </div>
                    <svg
                        class="w-4 h-4 text-gray-400 transition-transform"
                        :class="{ 'rotate-180': expandedCategories.includes('gates') }"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div v-show="expandedCategories.includes('gates')" class="px-3 pb-3 space-y-1">
                    <div
                        v-for="brick in filteredGates"
                        :key="brick.class"
                        :draggable="true"
                        @dragstart="onDragStart($event, brick, 'gate')"
                        class="palette-item gate"
                    >
                        <LucideIcon :name="brick.icon" :size="18" class="text-purple-600" />
                        <span class="text-sm text-gray-700 truncate">{{ brick.name }}</span>
                    </div>
                    <p v-if="filteredGates.length === 0" class="text-xs text-gray-400 py-2 text-center">
                        No gates found
                    </p>
                </div>
            </div>
        </div>

        <!-- Help Text -->
        <div class="p-3 border-t bg-gray-50">
            <p class="text-xs text-gray-500 text-center">
                Drag bricks to the canvas to build your flow
            </p>
        </div>
    </aside>
</template>
