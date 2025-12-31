<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AutoBuilder - Flows</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Automation Flows</h1>
                <p class="text-gray-500 mt-1">Build and manage your automation workflows</p>
            </div>
            <div class="flex gap-2">
                <button
                    onclick="openImportModal()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 flex items-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Import
                </button>
                <button
                    onclick="createFlow()"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 flex items-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Flow
                </button>
            </div>
        </div>

        <!-- Flows Grid -->
        <div id="flows-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Loading -->
            <div class="col-span-full flex items-center justify-center py-12">
                <svg class="animate-spin h-8 w-8 text-blue-600" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Create Flow Modal -->
    <div id="create-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold">Create New Flow</h2>
            </div>
            <form onsubmit="submitCreateFlow(event)">
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input
                            type="text"
                            id="flow-name"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="My Automation Flow"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea
                            id="flow-description"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Optional description..."
                        ></textarea>
                    </div>
                    <div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input
                                type="checkbox"
                                id="flow-sync"
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            >
                            <div>
                                <span class="block text-sm font-medium text-gray-700">Synchronous Execution</span>
                                <span class="block text-xs text-gray-500">Execute flow immediately instead of queuing</span>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="px-6 py-4 border-t bg-gray-50 flex justify-end gap-3">
                    <button
                        type="button"
                        onclick="closeModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
                    >
                        Create Flow
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import Flow Modal -->
    <div id="import-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold">Import Flow</h2>
            </div>
            <div class="px-6 py-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select JSON File</label>
                    <input
                        type="file"
                        id="import-file"
                        accept=".json"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
                <div id="import-preview" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Preview</label>
                    <div class="bg-gray-50 rounded-md p-3 text-sm">
                        <p><strong>Name:</strong> <span id="preview-name"></span></p>
                        <p><strong>Nodes:</strong> <span id="preview-nodes"></span></p>
                        <p><strong>Edges:</strong> <span id="preview-edges"></span></p>
                    </div>
                </div>
                <div id="import-error" class="hidden text-sm text-red-600"></div>
            </div>
            <div class="px-6 py-4 border-t bg-gray-50 flex justify-end gap-3">
                <button
                    type="button"
                    onclick="closeImportModal()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                >
                    Cancel
                </button>
                <button
                    id="import-submit-btn"
                    onclick="submitImport()"
                    disabled
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Import Flow
                </button>
            </div>
        </div>
    </div>

    <script>
        const baseUrl = '{{ url(config('autobuilder.routes.prefix', 'autobuilder')) }}';
        const apiBase = baseUrl + '/api';
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        async function loadFlows() {
            try {
                const response = await fetch(`${apiBase}/flows`);
                const result = await response.json();
                renderFlows(result.data);
            } catch (error) {
                console.error('Failed to load flows:', error);
                document.getElementById('flows-container').innerHTML = `
                    <div class="col-span-full text-center py-12 text-red-500">
                        Failed to load flows. Please try again.
                    </div>
                `;
            }
        }

        function renderFlows(flows) {
            const container = document.getElementById('flows-container');

            if (!flows || flows.length === 0) {
                container.innerHTML = `
                    <div class="col-span-full text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No flows</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new flow.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = flows.map(flow => `
                <div class="bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow">
                    <div class="p-5">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-medium text-gray-900">${escapeHtml(flow.name)}</h3>
                                <p class="text-sm text-gray-500 mt-1 line-clamp-2">${escapeHtml(flow.description || 'No description')}</p>
                            </div>
                            <div class="flex gap-1">
                                <span class="px-2 py-1 text-xs font-medium rounded-full ${flow.sync ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600'}">
                                    ${flow.sync ? 'Sync' : 'Async'}
                                </span>
                                <span class="px-2 py-1 text-xs font-medium rounded-full ${flow.active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'}">
                                    ${flow.active ? 'Active' : 'Draft'}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center gap-4 text-sm text-gray-500">
                            <span>${flow.nodes_count || 0} nodes</span>
                            <span>${flow.runs_count || 0} runs</span>
                        </div>
                    </div>

                    <div class="px-5 py-3 border-t bg-gray-50 flex justify-between items-center">
                        <span class="text-xs text-gray-400">
                            Updated ${formatDate(flow.updated_at)}
                        </span>
                        <div class="flex gap-2">
                            <button
                                onclick="toggleFlow('${flow.id}', ${flow.active})"
                                class="px-2 py-1 text-xs font-medium rounded ${flow.active ? 'text-orange-600 hover:bg-orange-50' : 'text-green-600 hover:bg-green-50'}"
                                title="${flow.active ? 'Deactivate' : 'Activate'}"
                            >
                                ${flow.active ? 'Deactivate' : 'Activate'}
                            </button>
                            <button
                                onclick="duplicateFlow('${flow.id}')"
                                class="p-1 text-gray-400 hover:text-blue-500 rounded"
                                title="Duplicate"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </button>
                            <button
                                onclick="deleteFlow('${flow.id}')"
                                class="p-1 text-gray-400 hover:text-red-500 rounded"
                                title="Delete"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                            <a
                                href="${baseUrl}/flows/${flow.id}"
                                class="px-3 py-1 text-sm font-medium text-blue-600 hover:bg-blue-50 rounded"
                            >
                                Edit
                            </a>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function createFlow() {
            document.getElementById('create-modal').classList.remove('hidden');
            document.getElementById('create-modal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('create-modal').classList.add('hidden');
            document.getElementById('create-modal').classList.remove('flex');
        }

        async function submitCreateFlow(event) {
            event.preventDefault();

            const name = document.getElementById('flow-name').value;
            const description = document.getElementById('flow-description').value;
            const sync = document.getElementById('flow-sync').checked;

            try {
                const response = await fetch(`${apiBase}/flows`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ name, description, sync }),
                });

                if (!response.ok) throw new Error('Failed to create flow');

                const result = await response.json();
                closeModal();

                // Redirect to editor
                window.location.href = `${baseUrl}/flows/${result.data.id}`;
            } catch (error) {
                console.error('Failed to create flow:', error);
                alert('Failed to create flow: ' + error.message);
            }
        }

        async function deleteFlow(id) {
            if (!confirm('Are you sure you want to delete this flow?')) return;

            try {
                const response = await fetch(`${apiBase}/flows/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                if (!response.ok) throw new Error('Failed to delete flow');

                loadFlows();
            } catch (error) {
                console.error('Failed to delete flow:', error);
                alert('Failed to delete flow: ' + error.message);
            }
        }

        async function toggleFlow(id, isActive) {
            const action = isActive ? 'deactivate' : 'activate';

            try {
                const response = await fetch(`${apiBase}/flows/${id}/${action}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                if (!response.ok) throw new Error(`Failed to ${action} flow`);

                loadFlows();
            } catch (error) {
                console.error(`Failed to ${action} flow:`, error);
                alert(`Failed to ${action} flow: ` + error.message);
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'just now';
            if (diffMins < 60) return `${diffMins}m ago`;
            if (diffHours < 24) return `${diffHours}h ago`;
            if (diffDays < 7) return `${diffDays}d ago`;
            return date.toLocaleDateString();
        }

        // Duplicate flow
        async function duplicateFlow(id) {
            try {
                const response = await fetch(`${apiBase}/flows/${id}/duplicate`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                if (!response.ok) throw new Error('Failed to duplicate flow');

                const result = await response.json();
                // Redirect to the new flow
                window.location.href = `${baseUrl}/flows/${result.data.id}`;
            } catch (error) {
                console.error('Failed to duplicate flow:', error);
                alert('Failed to duplicate flow: ' + error.message);
            }
        }

        // Import modal handling
        let importData = null;

        function openImportModal() {
            document.getElementById('import-modal').classList.remove('hidden');
            document.getElementById('import-modal').classList.add('flex');
            document.getElementById('import-file').value = '';
            document.getElementById('import-preview').classList.add('hidden');
            document.getElementById('import-error').classList.add('hidden');
            document.getElementById('import-submit-btn').disabled = true;
            importData = null;
        }

        function closeImportModal() {
            document.getElementById('import-modal').classList.add('hidden');
            document.getElementById('import-modal').classList.remove('flex');
            importData = null;
        }

        document.getElementById('import-file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = JSON.parse(e.target.result);

                    // Validate structure
                    if (!data.name || !data.nodes || !data.edges) {
                        throw new Error('Invalid flow format. Missing name, nodes, or edges.');
                    }

                    importData = data;

                    // Show preview
                    document.getElementById('preview-name').textContent = data.name;
                    document.getElementById('preview-nodes').textContent = data.nodes.length;
                    document.getElementById('preview-edges').textContent = data.edges.length;
                    document.getElementById('import-preview').classList.remove('hidden');
                    document.getElementById('import-error').classList.add('hidden');
                    document.getElementById('import-submit-btn').disabled = false;
                } catch (error) {
                    document.getElementById('import-error').textContent = error.message;
                    document.getElementById('import-error').classList.remove('hidden');
                    document.getElementById('import-preview').classList.add('hidden');
                    document.getElementById('import-submit-btn').disabled = true;
                    importData = null;
                }
            };
            reader.readAsText(file);
        });

        async function submitImport() {
            if (!importData) return;

            try {
                const response = await fetch(`${apiBase}/flows/import`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(importData),
                });

                if (!response.ok) throw new Error('Failed to import flow');

                const result = await response.json();
                closeImportModal();

                // Redirect to the imported flow
                window.location.href = `${baseUrl}/flows/${result.data.id}`;
            } catch (error) {
                console.error('Failed to import flow:', error);
                document.getElementById('import-error').textContent = error.message;
                document.getElementById('import-error').classList.remove('hidden');
            }
        }

        // Close modals on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal();
                closeImportModal();
            }
        });

        // Close modals on backdrop click
        document.getElementById('create-modal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) closeModal();
        });
        document.getElementById('import-modal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) closeImportModal();
        });

        // Load flows on page load
        loadFlows();
    </script>
</body>
</html>
