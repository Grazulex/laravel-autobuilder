import { createApp } from 'vue'
import FlowEditor from './components/FlowEditor.vue'
import '../css/autobuilder.css'

// Import Vue Flow styles
import '@vue-flow/core/dist/style.css'
import '@vue-flow/core/dist/theme-default.css'

const app = createApp(FlowEditor, {
    flow: window.flowData,
    apiBase: window.apiBase || '/autobuilder/api',
    indexUrl: window.indexUrl || '/autobuilder',
})

app.mount('#autobuilder-app')
