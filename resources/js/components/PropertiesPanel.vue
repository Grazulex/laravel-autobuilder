<script setup>
import { computed, watch, ref } from 'vue'
import LucideIcon from './LucideIcon.vue'

const props = defineProps({
    node: { type: Object, default: null },
})

const emit = defineEmits(['update', 'delete'])

const localConfig = ref({})

// Sync local config with node
watch(
    () => props.node,
    (node) => {
        if (node) {
            localConfig.value = { ...node.data.config }
        } else {
            localConfig.value = {}
        }
    },
    { immediate: true, deep: true }
)

// Emit updates on change
function updateField(fieldName, value) {
    localConfig.value[fieldName] = value
    emit('update', props.node.id, { [fieldName]: value })
}

// Get field component type
function getFieldType(field) {
    const typeMap = {
        text: 'text',
        textarea: 'textarea',
        number: 'number',
        boolean: 'checkbox',
        select: 'select',
        email: 'email',
        url: 'url',
        json: 'json',
        code: 'code',
        date: 'date',
        datetime: 'datetime-local',
        time: 'time',
        'model-select': 'model-select',
        'timezone-select': 'timezone-select',
        'log-channel-select': 'log-channel-select',
        'key-value': 'key-value',
    }
    return typeMap[field.type] || 'text'
}

// Get options for special select types
function getSelectOptions(field) {
    if (field.type === 'model-select' && field.models) {
        return Object.entries(field.models).map(([value, label]) => ({ value, label }))
    }
    if (field.type === 'timezone-select' && field.common) {
        return Object.entries(field.common).map(([value, label]) => ({ value, label }))
    }
    if (field.type === 'log-channel-select' && field.channels) {
        return Object.entries(field.channels).map(([value, label]) => ({ value, label }))
    }
    return field.options || []
}

const nodeTypeColors = {
    trigger: 'bg-trigger-500',
    condition: 'bg-condition-500',
    action: 'bg-action-500',
}
</script>

<template>
    <aside
        class="w-80 bg-white border-l flex flex-col transition-all duration-200"
        :class="{ 'w-0 overflow-hidden': !node }"
    >
        <template v-if="node">
            <!-- Header -->
            <div class="px-4 py-3 border-b flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div
                        class="w-2 h-2 rounded-full"
                        :class="nodeTypeColors[node.type]"
                    ></div>
                    <span class="font-medium text-gray-900">{{ node.data.label }}</span>
                </div>
                <button
                    @click="$emit('delete')"
                    class="p-1 text-gray-400 hover:text-red-500 rounded"
                    title="Delete node"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>

            <!-- Brick Info -->
            <div class="px-4 py-3 border-b bg-gray-50">
                <div class="flex items-center gap-2 mb-1">
                    <LucideIcon :name="node.data.icon" :size="24" class="text-gray-600" />
                    <span class="text-sm text-gray-500 capitalize">{{ node.type }}</span>
                </div>
                <p class="text-xs text-gray-400 font-mono truncate" :title="node.data.brick">
                    {{ node.data.brick }}
                </p>
            </div>

            <!-- Fields -->
            <div class="flex-1 overflow-y-auto p-4">
                <div v-if="!node.data.fields?.length" class="text-sm text-gray-500 text-center py-4">
                    This node has no configurable fields
                </div>

                <div
                    v-for="field in node.data.fields"
                    :key="field.name"
                    class="properties-field"
                >
                    <label :for="`field-${field.name}`" class="flex items-center justify-between">
                        <span>{{ field.label || field.name }}</span>
                        <span v-if="field.required" class="text-red-500 text-xs">*</span>
                    </label>

                    <!-- Text Input -->
                    <input
                        v-if="['text', 'email', 'url', 'number', 'date', 'datetime-local', 'time'].includes(getFieldType(field))"
                        :id="`field-${field.name}`"
                        :type="getFieldType(field)"
                        :value="localConfig[field.name] ?? field.default"
                        :placeholder="field.placeholder || field.description"
                        :required="field.required"
                        @input="updateField(field.name, $event.target.value)"
                    >

                    <!-- Textarea -->
                    <textarea
                        v-else-if="getFieldType(field) === 'textarea'"
                        :id="`field-${field.name}`"
                        :value="localConfig[field.name] ?? field.default"
                        :placeholder="field.placeholder || field.description"
                        :required="field.required"
                        rows="3"
                        @input="updateField(field.name, $event.target.value)"
                    ></textarea>

                    <!-- JSON/Code Editor -->
                    <textarea
                        v-else-if="['json', 'code'].includes(getFieldType(field))"
                        :id="`field-${field.name}`"
                        :value="typeof localConfig[field.name] === 'object' ? JSON.stringify(localConfig[field.name], null, 2) : (localConfig[field.name] ?? field.default)"
                        :placeholder="field.placeholder || field.description"
                        :required="field.required"
                        rows="4"
                        class="font-mono text-sm"
                        @input="updateField(field.name, $event.target.value)"
                    ></textarea>

                    <!-- Checkbox -->
                    <div
                        v-else-if="getFieldType(field) === 'checkbox'"
                        class="flex items-center gap-2 mt-1"
                    >
                        <input
                            :id="`field-${field.name}`"
                            type="checkbox"
                            :checked="localConfig[field.name] ?? field.default"
                            @change="updateField(field.name, $event.target.checked)"
                        >
                        <span class="text-sm text-gray-600">{{ field.description }}</span>
                    </div>

                    <!-- Select -->
                    <select
                        v-else-if="getFieldType(field) === 'select'"
                        :id="`field-${field.name}`"
                        :value="localConfig[field.name] ?? field.default"
                        :required="field.required"
                        @change="updateField(field.name, $event.target.value)"
                    >
                        <option value="" disabled>Select...</option>
                        <option
                            v-for="option in field.options"
                            :key="option.value ?? option"
                            :value="option.value ?? option"
                        >
                            {{ option.label ?? option }}
                        </option>
                    </select>

                    <!-- Model Select -->
                    <select
                        v-else-if="getFieldType(field) === 'model-select'"
                        :id="`field-${field.name}`"
                        :value="localConfig[field.name] ?? field.default"
                        :required="field.required"
                        @change="updateField(field.name, $event.target.value)"
                    >
                        <option value="">Select a model...</option>
                        <option
                            v-for="option in getSelectOptions(field)"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>

                    <!-- Timezone Select -->
                    <select
                        v-else-if="getFieldType(field) === 'timezone-select'"
                        :id="`field-${field.name}`"
                        :value="localConfig[field.name] ?? field.default"
                        :required="field.required"
                        @change="updateField(field.name, $event.target.value)"
                    >
                        <option value="">Select timezone...</option>
                        <option
                            v-for="option in getSelectOptions(field)"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>

                    <!-- Log Channel Select -->
                    <select
                        v-else-if="getFieldType(field) === 'log-channel-select'"
                        :id="`field-${field.name}`"
                        :value="localConfig[field.name] ?? field.default"
                        :required="field.required"
                        @change="updateField(field.name, $event.target.value)"
                    >
                        <option
                            v-for="option in getSelectOptions(field)"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>

                    <!-- Description -->
                    <p
                        v-if="field.description && getFieldType(field) !== 'checkbox'"
                        class="text-xs text-gray-500 mt-1"
                    >
                        {{ field.description }}
                    </p>
                </div>
            </div>

            <!-- Node ID -->
            <div class="px-4 py-2 border-t bg-gray-50">
                <p class="text-xs text-gray-400">
                    Node ID: <code class="font-mono">{{ node.id }}</code>
                </p>
            </div>
        </template>

        <!-- Empty State -->
        <div v-else class="flex-1 flex items-center justify-center p-4">
            <p class="text-sm text-gray-400 text-center">
                Select a node to view its properties
            </p>
        </div>
    </aside>
</template>
