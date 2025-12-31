<script setup>
import { ref, computed, onMounted, watch, provide } from 'vue'
import { VueFlow, useVueFlow } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import { MiniMap } from '@vue-flow/minimap'

import FlowToolbar from './FlowToolbar.vue'
import NodePalette from './NodePalette.vue'
import PropertiesPanel from './PropertiesPanel.vue'
import TriggerNode from '../nodes/TriggerNode.vue'
import ConditionNode from '../nodes/ConditionNode.vue'
import ActionNode from '../nodes/ActionNode.vue'
import GateNode from '../nodes/GateNode.vue'
import LabeledEdge from '../edges/LabeledEdge.vue'

const props = defineProps({
    flow: { type: Object, required: true },
    apiBase: { type: String, default: '/autobuilder/api' },
    indexUrl: { type: String, default: '/autobuilder' },
})

// Node types
const nodeTypes = {
    trigger: TriggerNode,
    condition: ConditionNode,
    action: ActionNode,
    gate: GateNode,
}

// Edge types
const edgeTypes = {
    labeled: LabeledEdge,
}

// Flow state
const nodes = ref(props.flow.nodes || [])
// Convert existing edges to use labeled type
const edges = ref((props.flow.edges || []).map(edge => ({
    ...edge,
    type: 'labeled'
})))
const flowData = ref({ ...props.flow })
const bricks = ref({ triggers: [], conditions: [], actions: [], gates: [] })
const selectedNode = ref(null)
const selectedEdge = ref(null)
const isSaving = ref(false)
const isTesting = ref(false)
const isToggling = ref(false)
const isValidating = ref(false)
const testResult = ref(null)
const validationResult = ref(null)
const showValidationPanel = ref(false)
const lastSaved = ref(null)
const showDeleteConfirm = ref(false)
const showHistory = ref(false)
const runs = ref([])
const isLoadingRuns = ref(false)
const expandedRunId = ref(null)

function toggleRunDetails(runId) {
    expandedRunId.value = expandedRunId.value === runId ? null : runId
}

// Vue Flow instance
const { onConnect, addEdges, onNodesChange, onEdgesChange, project, fitView } = useVueFlow()

// Handle connections
onConnect((connection) => {
    addEdges([{
        ...connection,
        type: 'labeled',
        animated: false,
    }])
})

// Handle edge selection
function onEdgeClick(event) {
    selectedEdge.value = event.edge
    selectedNode.value = null
}

// Delete edge
function deleteEdge(edgeId) {
    edges.value = edges.value.filter(e => e.id !== edgeId)
    selectedEdge.value = null
}

// Provide deleteEdge function to child components (edges)
provide('deleteEdge', deleteEdge)

// Handle node selection
function onNodeClick(event) {
    selectedNode.value = event.node
    selectedEdge.value = null
}

function onPaneClick() {
    selectedNode.value = null
    selectedEdge.value = null
}

// Drag and drop from palette
function onDragOver(event) {
    event.preventDefault()
    event.dataTransfer.dropEffect = 'move'
}

function onDrop(event) {
    event.preventDefault()

    const brickData = JSON.parse(event.dataTransfer.getData('application/json'))
    const position = project({ x: event.clientX - 250, y: event.clientY - 100 })

    const newNode = {
        id: `${brickData.type}-${Date.now()}`,
        type: brickData.type,
        position,
        data: {
            brick: brickData.class,
            label: brickData.name,
            icon: brickData.icon,
            config: {},
            fields: brickData.fields,
        },
    }

    nodes.value.push(newNode)
}

// Update node properties
function updateNodeConfig(nodeId, config) {
    const node = nodes.value.find(n => n.id === nodeId)
    if (node) {
        node.data.config = { ...node.data.config, ...config }
    }
}

// Delete selected node - show confirmation first
function requestDeleteNode() {
    if (!selectedNode.value) return
    showDeleteConfirm.value = true
}

function confirmDeleteNode() {
    if (!selectedNode.value) return

    const nodeId = selectedNode.value.id
    nodes.value = nodes.value.filter(n => n.id !== nodeId)
    edges.value = edges.value.filter(e => e.source !== nodeId && e.target !== nodeId)
    selectedNode.value = null
    showDeleteConfirm.value = false
}

function cancelDelete() {
    showDeleteConfirm.value = false
}

// Save flow
async function saveFlow() {
    isSaving.value = true
    try {
        const response = await fetch(`${props.apiBase}/flows/${props.flow.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({
                nodes: nodes.value,
                edges: edges.value,
            }),
        })

        if (!response.ok) throw new Error('Failed to save flow')

        // Trigger saved feedback
        lastSaved.value = Date.now()
    } catch (error) {
        console.error('Failed to save flow:', error)
        alert('Failed to save flow: ' + error.message)
    } finally {
        isSaving.value = false
    }
}

// Test flow
async function testFlow(payload = {}) {
    isTesting.value = true
    testResult.value = null

    try {
        const response = await fetch(`${props.apiBase}/flows/${props.flow.id}/test`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({ payload }),
        })

        const result = await response.json()
        testResult.value = result.data || result
    } catch (error) {
        console.error('Failed to test flow:', error)
        testResult.value = { status: 'error', error: { message: error.message } }
    } finally {
        isTesting.value = false
    }
}

// Toggle flow active status
async function toggleActive() {
    isToggling.value = true
    const action = flowData.value.active ? 'deactivate' : 'activate'

    try {
        const response = await fetch(`${props.apiBase}/flows/${props.flow.id}/${action}`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        })

        if (!response.ok) throw new Error(`Failed to ${action} flow`)

        const result = await response.json()
        flowData.value.active = result.data.active
    } catch (error) {
        console.error(`Failed to ${action} flow:`, error)
        alert(`Failed to ${action} flow: ` + error.message)
    } finally {
        isToggling.value = false
    }
}

// Toggle flow sync mode
async function toggleSync() {
    try {
        const response = await fetch(`${props.apiBase}/flows/${props.flow.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({ sync: !flowData.value.sync }),
        })

        if (!response.ok) throw new Error('Failed to toggle sync mode')

        const result = await response.json()
        flowData.value.sync = result.data.sync
    } catch (error) {
        console.error('Failed to toggle sync mode:', error)
        alert('Failed to toggle sync mode: ' + error.message)
    }
}

// Validate flow
async function validateFlow() {
    isValidating.value = true
    validationResult.value = null

    try {
        const response = await fetch(`${props.apiBase}/flows/${props.flow.id}/validate`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        })

        if (!response.ok) throw new Error('Failed to validate flow')

        const result = await response.json()
        validationResult.value = result

        // Show validation panel if there are errors or warnings
        if (!result.valid || result.warnings?.length > 0) {
            showValidationPanel.value = true
        }
    } catch (error) {
        console.error('Failed to validate flow:', error)
        alert('Failed to validate flow: ' + error.message)
    } finally {
        isValidating.value = false
    }
}

// Focus on a node (used when clicking on validation errors)
function focusNode(nodeId) {
    const node = nodes.value.find(n => n.id === nodeId)
    if (node) {
        selectedNode.value = node
        // Could also pan to node position here
    }
}

// Export flow as JSON file
async function exportFlow() {
    try {
        const response = await fetch(`${props.apiBase}/flows/${props.flow.id}/export`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        })

        if (!response.ok) throw new Error('Failed to export flow')

        const result = await response.json()
        const data = result.data

        // Create blob and download
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `${flowData.value.name.replace(/[^a-z0-9]/gi, '-').toLowerCase()}-flow.json`
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        URL.revokeObjectURL(url)
    } catch (error) {
        console.error('Failed to export flow:', error)
        alert('Failed to export flow: ' + error.message)
    }
}

// Rename flow
async function renameFlow(newName) {
    try {
        const response = await fetch(`${props.apiBase}/flows/${props.flow.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({ name: newName }),
        })

        if (!response.ok) throw new Error('Failed to rename flow')

        flowData.value.name = newName
    } catch (error) {
        console.error('Failed to rename flow:', error)
        alert('Failed to rename flow: ' + error.message)
    }
}

// Update flow description
async function updateDescription(newDescription) {
    try {
        const response = await fetch(`${props.apiBase}/flows/${props.flow.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({ description: newDescription }),
        })

        if (!response.ok) throw new Error('Failed to update description')

        flowData.value.description = newDescription
    } catch (error) {
        console.error('Failed to update description:', error)
        alert('Failed to update description: ' + error.message)
    }
}

// Find brick by class name
function findBrickByClass(brickClass) {
    const allBricks = [
        ...(bricks.value.triggers || []),
        ...(bricks.value.conditions || []),
        ...(bricks.value.actions || []),
        ...(bricks.value.gates || []),
    ]
    return allBricks.find(b => b.class === brickClass)
}

// Enrich nodes with fields from brick registry
function enrichNodesWithFields() {
    nodes.value = nodes.value.map(node => {
        if (!node.data.fields || node.data.fields.length === 0) {
            const brick = findBrickByClass(node.data.brick)
            if (brick && brick.fields) {
                return {
                    ...node,
                    data: {
                        ...node.data,
                        fields: brick.fields,
                    },
                }
            }
        }
        return node
    })
}

// Load bricks
async function loadBricks() {
    try {
        const response = await fetch(`${props.apiBase}/bricks`)
        const result = await response.json()
        bricks.value = result.data
        // Enrich existing nodes with fields from brick registry
        enrichNodesWithFields()
    } catch (error) {
        console.error('Failed to load bricks:', error)
    }
}

// Load run history
async function loadRuns() {
    isLoadingRuns.value = true
    try {
        const response = await fetch(`${props.apiBase}/flows/${props.flow.id}/runs`)
        const result = await response.json()
        runs.value = result.data || []
    } catch (error) {
        console.error('Failed to load runs:', error)
        runs.value = []
    } finally {
        isLoadingRuns.value = false
    }
}

function openHistory() {
    showHistory.value = true
    loadRuns()
}

function formatDate(dateString) {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleString()
}

function formatDuration(startedAt, completedAt) {
    if (!startedAt || !completedAt) return '-'
    const start = new Date(startedAt)
    const end = new Date(completedAt)
    const ms = end - start
    if (ms < 1000) return `${ms}ms`
    return `${(ms / 1000).toFixed(2)}s`
}

// Keyboard shortcuts
function handleKeydown(event) {
    if (event.key === 'Delete' || event.key === 'Backspace') {
        if (!['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)) {
            if (selectedEdge.value) {
                deleteEdge(selectedEdge.value.id)
            } else if (selectedNode.value) {
                requestDeleteNode()
            }
        }
    }
    if (event.key === 's' && (event.ctrlKey || event.metaKey)) {
        event.preventDefault()
        saveFlow()
    }
    if (event.key === 'Escape') {
        showDeleteConfirm.value = false
        showHistory.value = false
        selectedEdge.value = null
    }
}

onMounted(() => {
    loadBricks()
    document.addEventListener('keydown', handleKeydown)

    // Fit view after a short delay
    setTimeout(() => fitView({ padding: 0.2 }), 100)
})
</script>

<template>
    <div class="h-screen flex flex-col bg-gray-100">
        <!-- Toolbar -->
        <FlowToolbar
            :flow="flowData"
            :index-url="indexUrl"
            :is-saving="isSaving"
            :is-testing="isTesting"
            :is-toggling="isToggling"
            :is-validating="isValidating"
            :test-result="testResult"
            :validation-result="validationResult"
            :last-saved="lastSaved"
            @save="saveFlow"
            @test="testFlow"
            @validate="validateFlow"
            @export="exportFlow"
            @toggle-active="toggleActive"
            @toggle-sync="toggleSync"
            @rename="renameFlow"
            @update-description="updateDescription"
            @history="openHistory"
        />

        <!-- Main Editor -->
        <div class="flex-1 flex overflow-hidden">
            <!-- Node Palette -->
            <NodePalette :bricks="bricks" />

            <!-- Flow Canvas -->
            <div
                class="flex-1 relative"
                @dragover="onDragOver"
                @drop="onDrop"
            >
                <VueFlow
                    v-model:nodes="nodes"
                    v-model:edges="edges"
                    :node-types="nodeTypes"
                    :edge-types="edgeTypes"
                    :default-edge-options="{ type: 'labeled', animated: false }"
                    fit-view-on-init
                    @node-click="onNodeClick"
                    @edge-click="onEdgeClick"
                    @pane-click="onPaneClick"
                >
                    <Background pattern-color="#e5e7eb" :gap="20" />
                    <Controls position="bottom-left" />
                    <MiniMap position="bottom-right" />
                </VueFlow>
            </div>

            <!-- Properties Panel -->
            <PropertiesPanel
                :node="selectedNode"
                @update="updateNodeConfig"
                @delete="requestDeleteNode"
            />
        </div>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="showDeleteConfirm"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
            @click.self="cancelDelete"
        >
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-900">Delete Node</h2>
                </div>
                <div class="px-6 py-4">
                    <p class="text-gray-600">
                        Are you sure you want to delete
                        <span class="font-medium text-gray-900">{{ selectedNode?.data?.label }}</span>?
                    </p>
                    <p class="text-sm text-gray-500 mt-2">This action cannot be undone.</p>
                </div>
                <div class="px-6 py-4 border-t bg-gray-50 flex justify-end gap-3">
                    <button
                        @click="cancelDelete"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmDeleteNode"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <!-- History Modal -->
        <div
            v-if="showHistory"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
            @click.self="showHistory = false"
        >
            <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl mx-4 max-h-[80vh] flex flex-col">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Execution History</h2>
                    <button
                        @click="showHistory = false"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex-1 overflow-auto">
                    <div v-if="isLoadingRuns" class="p-8 text-center text-gray-500">
                        <svg class="animate-spin h-8 w-8 mx-auto mb-2" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Loading...
                    </div>
                    <div v-else-if="runs.length === 0" class="p-8 text-center text-gray-500">
                        No executions yet. Run a test to see history.
                    </div>
                    <table v-else class="w-full">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="w-8 px-2"></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Started</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Run ID</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template v-for="run in runs" :key="run.id">
                                <tr
                                    @click="toggleRunDetails(run.id)"
                                    class="hover:bg-gray-50 cursor-pointer"
                                >
                                    <td class="px-2 py-3 text-gray-400">
                                        <svg
                                            class="w-4 h-4 transition-transform"
                                            :class="{ 'rotate-90': expandedRunId === run.id }"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            :class="[
                                                'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                                                run.status === 'completed' ? 'bg-green-100 text-green-800' :
                                                run.status === 'failed' ? 'bg-red-100 text-red-800' :
                                                run.status === 'paused' ? 'bg-yellow-100 text-yellow-800' :
                                                'bg-gray-100 text-gray-800'
                                            ]"
                                        >
                                            {{ run.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(run.started_at) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ run.duration_ms ? run.duration_ms + 'ms' : formatDuration(run.started_at, run.completed_at) }}</td>
                                    <td class="px-4 py-3 text-xs font-mono text-gray-500">{{ run.id?.substring(0, 8) }}...</td>
                                </tr>
                                <!-- Expanded Details -->
                                <tr v-if="expandedRunId === run.id">
                                    <td colspan="5" class="bg-gray-50 px-4 py-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <!-- Payload -->
                                            <div>
                                                <h4 class="text-xs font-medium text-gray-500 uppercase mb-2">Payload</h4>
                                                <pre class="bg-white border rounded p-2 text-xs overflow-auto max-h-32">{{ JSON.stringify(run.payload, null, 2) || '{}' }}</pre>
                                            </div>
                                            <!-- Variables -->
                                            <div>
                                                <h4 class="text-xs font-medium text-gray-500 uppercase mb-2">Variables</h4>
                                                <pre class="bg-white border rounded p-2 text-xs overflow-auto max-h-32">{{ JSON.stringify(run.variables, null, 2) || '{}' }}</pre>
                                            </div>
                                            <!-- Error (if failed) -->
                                            <div v-if="run.status === 'failed' && run.error" class="md:col-span-2">
                                                <h4 class="text-xs font-medium text-red-500 uppercase mb-2">Error</h4>
                                                <pre class="bg-red-50 border border-red-200 rounded p-2 text-xs text-red-700 overflow-auto max-h-32">{{ run.error }}</pre>
                                            </div>
                                            <!-- Logs -->
                                            <div class="md:col-span-2">
                                                <h4 class="text-xs font-medium text-gray-500 uppercase mb-2">Execution Logs ({{ run.logs?.length || 0 }})</h4>
                                                <div class="bg-white border rounded max-h-48 overflow-auto">
                                                    <div v-if="!run.logs || run.logs.length === 0" class="p-2 text-xs text-gray-400 text-center">
                                                        No logs
                                                    </div>
                                                    <div v-else class="divide-y divide-gray-100">
                                                        <div
                                                            v-for="(log, idx) in run.logs"
                                                            :key="idx"
                                                            class="px-2 py-1 text-xs flex gap-2"
                                                        >
                                                            <span
                                                                :class="[
                                                                    'font-medium uppercase w-16 shrink-0',
                                                                    log.level === 'error' ? 'text-red-600' :
                                                                    log.level === 'warning' ? 'text-yellow-600' :
                                                                    'text-blue-600'
                                                                ]"
                                                            >{{ log.level }}</span>
                                                            <span class="text-gray-600 flex-1">{{ log.message }}</span>
                                                            <span class="text-gray-400 shrink-0">{{ new Date(log.timestamp).toLocaleTimeString() }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t bg-gray-50 flex justify-between items-center">
                    <button
                        @click="loadRuns"
                        :disabled="isLoadingRuns"
                        class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-800 disabled:opacity-50"
                    >
                        <svg class="w-4 h-4 inline mr-1" :class="{ 'animate-spin': isLoadingRuns }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                    <button
                        @click="showHistory = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- Validation Panel Modal -->
        <div
            v-if="showValidationPanel && validationResult"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
            @click.self="showValidationPanel = false"
        >
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[80vh] flex flex-col">
                <div class="px-6 py-4 border-b flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <svg
                            v-if="validationResult.valid"
                            class="w-5 h-5 text-green-500"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg
                            v-else
                            class="w-5 h-5 text-red-500"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Validation Results
                    </h2>
                    <button
                        @click="showValidationPanel = false"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-auto px-6 py-4">
                    <!-- Summary -->
                    <div
                        :class="[
                            'p-4 rounded-lg mb-4',
                            validationResult.valid ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'
                        ]"
                    >
                        <p :class="validationResult.valid ? 'text-green-800' : 'text-red-800'">
                            <span v-if="validationResult.valid && validationResult.summary?.warning_count === 0">
                                Flow is valid and ready to activate.
                            </span>
                            <span v-else-if="validationResult.valid">
                                Flow is valid with {{ validationResult.summary?.warning_count }} warning(s).
                            </span>
                            <span v-else>
                                Flow has {{ validationResult.summary?.error_count }} error(s) and {{ validationResult.summary?.warning_count }} warning(s).
                            </span>
                        </p>
                    </div>

                    <!-- Errors -->
                    <div v-if="validationResult.errors?.length > 0" class="mb-4">
                        <h3 class="text-sm font-medium text-red-700 mb-2 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Errors ({{ validationResult.errors.length }})
                        </h3>
                        <div class="space-y-2">
                            <div
                                v-for="(error, idx) in validationResult.errors"
                                :key="'error-' + idx"
                                class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm"
                            >
                                <p class="text-red-800 font-medium">{{ error.message }}</p>
                                <button
                                    v-if="error.node_id"
                                    @click="focusNode(error.node_id); showValidationPanel = false"
                                    class="mt-1 text-xs text-red-600 hover:text-red-800 underline"
                                >
                                    Go to node
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Warnings -->
                    <div v-if="validationResult.warnings?.length > 0">
                        <h3 class="text-sm font-medium text-yellow-700 mb-2 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Warnings ({{ validationResult.warnings.length }})
                        </h3>
                        <div class="space-y-2">
                            <div
                                v-for="(warning, idx) in validationResult.warnings"
                                :key="'warning-' + idx"
                                class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm"
                            >
                                <p class="text-yellow-800">{{ warning.message }}</p>
                                <button
                                    v-if="warning.node_id"
                                    @click="focusNode(warning.node_id); showValidationPanel = false"
                                    class="mt-1 text-xs text-yellow-600 hover:text-yellow-800 underline"
                                >
                                    Go to node
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- No issues -->
                    <div
                        v-if="validationResult.valid && validationResult.errors?.length === 0 && validationResult.warnings?.length === 0"
                        class="text-center py-8 text-gray-500"
                    >
                        <svg class="w-12 h-12 mx-auto mb-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p>No issues found. Your flow is ready!</p>
                    </div>
                </div>

                <div class="px-6 py-4 border-t bg-gray-50 flex justify-end">
                    <button
                        @click="showValidationPanel = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
