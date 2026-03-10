<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
    flowId: { type: String, required: true },
    initialTags: { type: Array, default: () => [] },
    apiBase: { type: String, default: '/autobuilder/api' },
})

const flowTags = ref([...props.initialTags])
const allTags = ref([])
const inputValue = ref('')
const isOpen = ref(false)
const isLoading = ref(false)
const inputRef = ref(null)

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content

const suggestions = computed(() => {
    const q = inputValue.value.toLowerCase().trim()
    const attachedIds = new Set(flowTags.value.map(t => t.id))

    return allTags.value
        .filter(t => !attachedIds.has(t.id) && (q === '' || t.name.toLowerCase().includes(q)))
        .slice(0, 8)
})

const canCreate = computed(() => {
    const q = inputValue.value.trim()
    if (!q) return false
    return !allTags.value.some(t => t.name.toLowerCase() === q.toLowerCase())
})

async function loadAllTags() {
    try {
        const res = await fetch(`${props.apiBase}/tags`)
        const data = await res.json()
        allTags.value = data.data || []
    } catch (e) {
        console.error('Failed to load tags:', e)
    }
}

async function attachTag(tag) {
    isLoading.value = true
    try {
        await fetch(`${props.apiBase}/flows/${props.flowId}/tags/${tag.id}`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        })
        if (!flowTags.value.find(t => t.id === tag.id)) {
            flowTags.value.push(tag)
        }
    } catch (e) {
        console.error('Failed to attach tag:', e)
    } finally {
        isLoading.value = false
        inputValue.value = ''
        isOpen.value = false
    }
}

async function detachTag(tag) {
    try {
        await fetch(`${props.apiBase}/flows/${props.flowId}/tags/${tag.id}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        })
        flowTags.value = flowTags.value.filter(t => t.id !== tag.id)
    } catch (e) {
        console.error('Failed to detach tag:', e)
    }
}

async function createAndAttach() {
    const name = inputValue.value.trim()
    if (!name) return

    isLoading.value = true
    try {
        const res = await fetch(`${props.apiBase}/tags`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ name }),
        })

        if (!res.ok) throw new Error('Failed to create tag')

        const data = await res.json()
        const newTag = data.data
        allTags.value.push(newTag)
        await attachTag(newTag)
    } catch (e) {
        console.error('Failed to create tag:', e)
    } finally {
        isLoading.value = false
    }
}

function handleInputKeydown(e) {
    if (e.key === 'Enter') {
        e.preventDefault()
        if (suggestions.value.length > 0) {
            attachTag(suggestions.value[0])
        } else if (canCreate.value) {
            createAndAttach()
        }
    } else if (e.key === 'Escape') {
        isOpen.value = false
        inputValue.value = ''
    }
}

function handleClickOutside(e) {
    if (!e.target.closest('.tag-manager')) {
        isOpen.value = false
    }
}

onMounted(() => {
    loadAllTags()
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
    <div class="tag-manager flex items-center gap-1 flex-wrap">
        <!-- Existing tags as pills -->
        <span
            v-for="tag in flowTags"
            :key="tag.id"
            class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-100 text-indigo-800 text-xs font-medium rounded-full"
        >
            {{ tag.name }}
            <button
                @click.stop="detachTag(tag)"
                class="text-indigo-500 hover:text-indigo-800 ml-0.5 leading-none"
                title="Remove tag"
            >×</button>
        </span>

        <!-- Input -->
        <div class="relative">
            <input
                ref="inputRef"
                v-model="inputValue"
                type="text"
                placeholder="Add tag..."
                class="text-xs px-2 py-0.5 border border-dashed border-gray-300 rounded-full focus:outline-none focus:border-indigo-400 focus:bg-white bg-transparent w-20 focus:w-32 transition-all"
                @focus="isOpen = true"
                @input="isOpen = true"
                @keydown="handleInputKeydown"
            />

            <!-- Dropdown -->
            <div
                v-if="isOpen && (suggestions.length > 0 || canCreate)"
                class="absolute top-full left-0 mt-1 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-50 py-1"
            >
                <button
                    v-for="tag in suggestions"
                    :key="tag.id"
                    @mousedown.prevent="attachTag(tag)"
                    class="w-full text-left px-3 py-1.5 text-xs text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 flex items-center gap-2"
                >
                    <span class="w-2 h-2 rounded-full bg-indigo-300 shrink-0"></span>
                    {{ tag.name }}
                </button>

                <div v-if="suggestions.length > 0 && canCreate" class="border-t border-gray-100 my-1"></div>

                <button
                    v-if="canCreate"
                    @mousedown.prevent="createAndAttach"
                    class="w-full text-left px-3 py-1.5 text-xs text-indigo-600 hover:bg-indigo-50 flex items-center gap-2"
                >
                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create "{{ inputValue.trim() }}"
                </button>
            </div>
        </div>
    </div>
</template>
