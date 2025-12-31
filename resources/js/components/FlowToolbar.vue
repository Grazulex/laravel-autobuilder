<script setup>
import { ref, computed, nextTick } from 'vue'

const props = defineProps({
    flow: { type: Object, required: true },
    indexUrl: { type: String, default: '../' },
    isSaving: { type: Boolean, default: false },
    isTesting: { type: Boolean, default: false },
    isToggling: { type: Boolean, default: false },
    isValidating: { type: Boolean, default: false },
    testResult: { type: Object, default: null },
    validationResult: { type: Object, default: null },
    lastSaved: { type: Number, default: null },
})

const emit = defineEmits(['save', 'test', 'toggle-active', 'toggle-sync', 'rename', 'update-description', 'history', 'validate', 'export'])

const showTestModal = ref(false)
const testPayload = ref('{}')
const showSavedFeedback = ref(false)

// Name editing
const isEditingName = ref(false)
const editedName = ref('')
const nameInput = ref(null)

// Description editing
const isEditingDescription = ref(false)
const editedDescription = ref('')
const descriptionInput = ref(null)

function startEditingName() {
    editedName.value = props.flow.name
    isEditingName.value = true
    nextTick(() => {
        nameInput.value?.focus()
        nameInput.value?.select()
    })
}

function saveName() {
    const newName = editedName.value.trim()
    if (newName && newName !== props.flow.name) {
        emit('rename', newName)
    }
    isEditingName.value = false
}

function cancelEditingName() {
    isEditingName.value = false
    editedName.value = props.flow.name
}

function handleNameKeydown(event) {
    if (event.key === 'Enter') {
        saveName()
    } else if (event.key === 'Escape') {
        cancelEditingName()
    }
}

function startEditingDescription() {
    editedDescription.value = props.flow.description || ''
    isEditingDescription.value = true
    nextTick(() => {
        descriptionInput.value?.focus()
        descriptionInput.value?.select()
    })
}

function saveDescription() {
    const newDescription = editedDescription.value.trim()
    if (newDescription !== (props.flow.description || '')) {
        emit('update-description', newDescription)
    }
    isEditingDescription.value = false
}

function cancelEditingDescription() {
    isEditingDescription.value = false
    editedDescription.value = props.flow.description || ''
}

function handleDescriptionKeydown(event) {
    if (event.key === 'Enter') {
        saveDescription()
    } else if (event.key === 'Escape') {
        cancelEditingDescription()
    }
}

// Watch for save completion
import { watch } from 'vue'
watch(() => props.lastSaved, (newVal) => {
    if (newVal) {
        showSavedFeedback.value = true
        setTimeout(() => {
            showSavedFeedback.value = false
        }, 2000)
    }
})

function openTestModal() {
    showTestModal.value = true
    // Keep previous payload for easy re-testing
}

function runTest() {
    try {
        const payload = JSON.parse(testPayload.value)
        emit('test', payload)
    } catch (e) {
        alert('Invalid JSON payload')
    }
}

const statusColor = computed(() => {
    if (!props.testResult) return ''
    return props.testResult.status === 'completed' ? 'text-green-600' : 'text-red-600'
})
</script>

<template>
    <header class="bg-white shadow-sm border-b px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a
                :href="indexUrl"
                class="text-gray-500 hover:text-gray-700"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>

            <div class="flex flex-col">
                <!-- Name -->
                <div class="flex items-center">
                    <input
                        v-if="isEditingName"
                        ref="nameInput"
                        v-model="editedName"
                        type="text"
                        class="text-lg font-semibold text-gray-900 bg-white border border-blue-500 rounded px-2 py-0.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        @blur="saveName"
                        @keydown="handleNameKeydown"
                    />
                    <h1
                        v-else
                        @click="startEditingName"
                        class="text-lg font-semibold text-gray-900 cursor-pointer hover:text-blue-600 hover:underline"
                        title="Click to rename"
                    >{{ flow.name }}</h1>
                    <button
                        v-if="!isEditingName"
                        @click="startEditingName"
                        class="ml-1 p-0.5 text-gray-400 hover:text-gray-600 rounded hover:bg-gray-100"
                        title="Rename flow"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </button>
                </div>

                <!-- Description -->
                <div class="flex items-center mt-0.5">
                    <input
                        v-if="isEditingDescription"
                        ref="descriptionInput"
                        v-model="editedDescription"
                        type="text"
                        class="text-xs text-gray-500 bg-white border border-blue-500 rounded px-1.5 py-0.5 focus:outline-none focus:ring-2 focus:ring-blue-500 min-w-[200px]"
                        placeholder="Add a description..."
                        @blur="saveDescription"
                        @keydown="handleDescriptionKeydown"
                    />
                    <span
                        v-else
                        @click="startEditingDescription"
                        :class="[
                            'text-xs cursor-pointer hover:underline',
                            flow.description ? 'text-gray-500 hover:text-gray-700' : 'text-gray-400 italic hover:text-gray-500'
                        ]"
                        title="Click to edit description"
                    >{{ flow.description || 'Add description...' }}</span>
                    <button
                        v-if="!isEditingDescription"
                        @click="startEditingDescription"
                        class="ml-1 p-0.5 text-gray-300 hover:text-gray-500 rounded hover:bg-gray-100"
                        title="Edit description"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </button>
                </div>
            </div>

            <button
                @click="$emit('toggle-active')"
                :disabled="isToggling"
                :class="[
                    'px-2 py-0.5 text-xs font-medium rounded-full transition-colors cursor-pointer',
                    flow.active
                        ? 'bg-green-100 text-green-800 hover:bg-green-200'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                ]"
                :title="flow.active ? 'Click to deactivate' : 'Click to activate'"
            >
                <span v-if="isToggling" class="flex items-center gap-1">
                    <svg class="animate-spin h-3 w-3" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                </span>
                <span v-else>{{ flow.active ? 'Active' : 'Draft' }}</span>
            </button>

            <button
                @click="$emit('toggle-sync')"
                :class="[
                    'px-2 py-0.5 text-xs font-medium rounded-full transition-colors cursor-pointer',
                    flow.sync
                        ? 'bg-blue-100 text-blue-800 hover:bg-blue-200'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                ]"
                :title="flow.sync ? 'Click to switch to async (queued)' : 'Click to switch to sync (immediate)'"
            >
                {{ flow.sync ? 'Sync' : 'Async' }}
            </button>
        </div>

        <div class="flex items-center gap-3">
            <!-- Validation Status Indicator -->
            <span
                v-if="validationResult"
                :class="[
                    'text-sm font-medium flex items-center gap-1',
                    validationResult.valid ? 'text-green-600' : 'text-red-600'
                ]"
            >
                <svg v-if="validationResult.valid" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span v-if="validationResult.valid && validationResult.summary?.warning_count > 0">
                    {{ validationResult.summary.warning_count }} warning(s)
                </span>
                <span v-else-if="!validationResult.valid">
                    {{ validationResult.summary?.error_count || 0 }} error(s)
                </span>
                <span v-else>Valid</span>
            </span>

            <!-- Test Result Indicator -->
            <span v-if="testResult" :class="statusColor" class="text-sm font-medium">
                {{ testResult.status === 'completed' ? 'Test passed' : 'Test failed' }}
            </span>

            <!-- History Button -->
            <button
                @click="$emit('history')"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                title="View execution history"
            >
                <span class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    History
                </span>
            </button>

            <!-- Export Button -->
            <button
                @click="$emit('export')"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                title="Export flow as JSON"
            >
                <span class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export
                </span>
            </button>

            <!-- Validate Button -->
            <button
                @click="$emit('validate')"
                :disabled="isValidating"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
                title="Validate flow configuration"
            >
                <span v-if="isValidating" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    Validating...
                </span>
                <span v-else class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Validate
                </span>
            </button>

            <!-- Test Button -->
            <button
                @click="openTestModal"
                :disabled="isTesting"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
            >
                <span v-if="isTesting" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    Testing...
                </span>
                <span v-else>Test</span>
            </button>

            <!-- Save Button -->
            <button
                @click="$emit('save')"
                :disabled="isSaving"
                :class="[
                    'px-4 py-2 text-sm font-medium rounded-md disabled:opacity-50 transition-colors',
                    showSavedFeedback
                        ? 'bg-green-600 text-white'
                        : 'bg-blue-600 text-white hover:bg-blue-700'
                ]"
            >
                <span v-if="isSaving" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    Saving...
                </span>
                <span v-else-if="showSavedFeedback" class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Saved!
                </span>
                <span v-else>Save</span>
            </button>
        </div>

        <!-- Test Modal -->
        <div
            v-if="showTestModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
            @click.self="showTestModal = false"
        >
            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold">Test Flow</h2>
                </div>

                <div class="px-6 py-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Test Payload (JSON)
                    </label>
                    <textarea
                        v-model="testPayload"
                        rows="6"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md font-mono text-sm"
                        placeholder='{"key": "value"}'
                    ></textarea>
                </div>

                <div class="px-6 py-4 border-t bg-gray-50 flex justify-end gap-3">
                    <button
                        @click="showTestModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        @click="runTest()"
                        :disabled="isTesting"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50"
                    >
                        <span v-if="isTesting" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Running...
                        </span>
                        <span v-else>Run Test</span>
                    </button>
                </div>

                <!-- Test Result -->
                <div v-if="testResult" class="px-6 py-4 border-t">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Result</h3>
                    <pre class="bg-gray-100 p-3 rounded text-xs overflow-auto max-h-48">{{ JSON.stringify(testResult, null, 2) }}</pre>
                </div>
            </div>
        </div>
    </header>
</template>
