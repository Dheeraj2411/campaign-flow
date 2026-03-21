<script setup>
import { ref, watch } from 'vue';
import { VueFlow, useVueFlow } from '@vue-flow/core';
import { Background } from '@vue-flow/background';
import { Controls } from '@vue-flow/controls';
import { MiniMap } from '@vue-flow/minimap';
import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import '@vue-flow/controls/dist/style.css';
import '@vue-flow/minimap/dist/style.css';

import TriggerNode from './nodes/TriggerNode.vue';
import ConditionNode from './nodes/ConditionNode.vue';
import ActionNode from './nodes/ActionNode.vue';
import DelayNode from './nodes/DelayNode.vue';

const props = defineProps({
    load: {
        type: Object,
        default: null
    }
});
const emit = defineEmits(['save', 'cancel']);

const { onPaneReady, addNodes, addEdges, onConnect, onNodeClick, toObject, setNodes, setEdges } = useVueFlow();

const selectedNode = ref(null);
const rightPanelOpen = ref(false);

const nodeTypes = {
    trigger: TriggerNode,
    condition: ConditionNode,
    action: ActionNode,
    delay: DelayNode,
};

onPaneReady(({ fitView }) => {
    if (props.load && props.load.nodes && props.load.nodes.length) {
        setNodes(props.load.nodes);
        setEdges(props.load.edges || []);
    } else {
        addNodes([{
            id: `node-${Date.now()}`,
            type: 'trigger',
            position: { x: 250, y: 50 },
            data: { label: 'Start Trigger', triggerType: '' }
        }]);
    }
    setTimeout(() => fitView(), 50);
});

onConnect((params) => {
    addEdges([params]);
});

onNodeClick(({ node }) => {
    selectedNode.value = node;
    rightPanelOpen.value = true;
});

const closePanel = () => {
    rightPanelOpen.value = false;
    selectedNode.value = null;
};

const handleDragStart = (event, type) => {
    if (event.dataTransfer) {
        event.dataTransfer.setData('application/vueflow', type);
        event.dataTransfer.effectAllowed = 'move';
    }
};

const vueFlowRef = ref(null);

const onDrop = (event) => {
    const type = event.dataTransfer?.getData('application/vueflow');
    if (!type) return;

    let position = { x: event.offsetX || event.clientX - 300, y: event.offsetY || event.clientY - 100 };
    const id = `node-${Date.now()}`;
    const newNode = {
        id,
        type,
        position,
        data: { label: `New ${type}`, ...getDefaultData(type) }
    };
    addNodes([newNode]);
};

const triggerOptions = [
    { value: 'message_received', label: 'Message Received' },
    { value: 'conversation_closed', label: 'Conversation Closed' },
    { value: 'tag_added', label: 'Tag Added' },
    { value: 'campaign_replied', label: 'Campaign Replied' },
];

const actionOptions = [
    { value: 'send_message', label: 'Send Message' },
    { value: 'send_template', label: 'Send Template' },
    { value: 'assign_conversation', label: 'Assign Conversation' },
    { value: 'change_status', label: 'Change Status' },
    { value: 'delay', label: 'Delay' },
    { value: 'webhook', label: 'Webhook' },
];

const onDragOver = (event) => {
    event.preventDefault();
    if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'move';
    }
};

const addNodeClick = (type) => {
    const id = `node-${Date.now()}`;
    const newNode = {
        id,
        type,
        position: { x: Math.random() * 200 + 400, y: Math.random() * 200 + 100 },
        data: { label: `New ${type}`, ...getDefaultData(type) }
    };
    addNodes([newNode]);
};

function getDefaultData(type) {
    if (type === 'trigger') return { triggerType: '' };
    if (type === 'condition') return { field: '', operator: 'is', value: '' };
    if (type === 'action') return { actionType: '', config: {} };
    if (type === 'delay') return { minutes: 5 };
    return {};
}

const saveFlow = () => {
    const obj = toObject();
    emit('save', { nodes: obj.nodes, edges: obj.edges });
};
</script>

<template>
    <div class="h-[700px] flex border border-gray-200 rounded-xl overflow-hidden bg-white relative">
        <div class="w-64 bg-gray-50 border-r border-gray-200 p-4 flex flex-col z-10">
            <h3 class="font-bold text-gray-800 mb-4 uppercase tracking-wider text-xs">Nodes Toolbar</h3>
            <div class="space-y-3">
                <div class="p-3 bg-white border border-green-300 rounded shadow-sm flex items-center gap-3 cursor-grab hover:bg-green-50" draggable="true" @dragstart="handleDragStart($event, 'trigger')" @click="addNodeClick('trigger')">
                    <div class="w-8 h-8 rounded bg-green-100 text-green-600 flex items-center justify-center"><span class="material-symbols-outlined text-sm">bolt</span></div>
                    <span class="text-sm font-semibold text-gray-700">Trigger</span>
                </div>
                <div class="p-3 bg-white border border-yellow-300 rounded shadow-sm flex items-center gap-3 cursor-grab hover:bg-yellow-50" draggable="true" @dragstart="handleDragStart($event, 'condition')" @click="addNodeClick('condition')">
                    <div class="w-8 h-8 rounded bg-yellow-100 text-yellow-600 flex items-center justify-center"><span class="material-symbols-outlined text-sm">filter_alt</span></div>
                    <span class="text-sm font-semibold text-gray-700">Condition</span>
                </div>
                <div class="p-3 bg-white border border-blue-300 rounded shadow-sm flex items-center gap-3 cursor-grab hover:bg-blue-50" draggable="true" @dragstart="handleDragStart($event, 'action')" @click="addNodeClick('action')">
                    <div class="w-8 h-8 rounded bg-blue-100 text-blue-600 flex items-center justify-center"><span class="material-symbols-outlined text-sm">play_arrow</span></div>
                    <span class="text-sm font-semibold text-gray-700">Action</span>
                </div>
                <div class="p-3 bg-white border border-purple-300 rounded shadow-sm flex items-center gap-3 cursor-grab hover:bg-purple-50" draggable="true" @dragstart="handleDragStart($event, 'delay')" @click="addNodeClick('delay')">
                    <div class="w-8 h-8 rounded bg-purple-100 text-purple-600 flex items-center justify-center"><span class="material-symbols-outlined text-sm">schedule</span></div>
                    <span class="text-sm font-semibold text-gray-700">Delay</span>
                </div>
            </div>

            <div class="mt-auto space-y-2">
                 <button @click="emit('cancel')" class="w-full py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm font-semibold shadow-sm transition">Cancel</button>
                 <button @click="saveFlow" class="w-full py-2.5 flex items-center justify-center gap-2 bg-indigo-600 border border-indigo-700 text-white rounded-lg hover:bg-indigo-700 text-sm font-semibold shadow-sm transition">
                    <span class="material-symbols-outlined text-lg">save</span>
                    Save Workflow
                </button>
            </div>
        </div>

        <div class="flex-1 relative" ref="vueFlowRef" @drop="onDrop" @dragover="onDragOver">
            <VueFlow :node-types="nodeTypes" class="w-full h-full bg-slate-50">
                <Background pattern-color="#e5e7eb" :gap="20" />
                <Controls />
                <MiniMap />
            </VueFlow>
        </div>

        <div 
            class="absolute top-0 right-0 h-full w-80 bg-white border-l border-gray-200 shadow-xl z-20 transition-transform duration-300 transform flex flex-col"
            :class="rightPanelOpen && selectedNode ? 'translate-x-0' : 'translate-x-full'"
        >
            <div v-if="selectedNode" class="flex flex-col h-full">
                <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                    <h3 class="font-bold text-gray-800 capitalize">{{ selectedNode.type }} Setup</h3>
                    <button @click="closePanel" class="text-gray-400 hover:text-gray-600">
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                </div>
                <div class="p-6 flex-1 overflow-y-auto space-y-5">
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Node Label</label>
                        <input v-model="selectedNode.data.label" type="text" class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>

                    <!-- Configs based on node type -->
                    <template v-if="selectedNode.type === 'trigger'">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Trigger Type</label>
                            <select v-model="selectedNode.data.triggerType" class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select Trigger</option>
                                <option v-for="t in triggerOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                        </div>
                    </template>

                    <template v-if="selectedNode.type === 'condition'">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Field to Check</label>
                            <select v-model="selectedNode.data.field" class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select Field</option>
                                <option value="tag">Contact Tag</option>
                                <option value="status">Conversation Status</option>
                                <option value="platform">Platform</option>
                            </select>
                        </div>
                        <div v-if="selectedNode.data.field">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Operator</label>
                            <select v-model="selectedNode.data.operator" class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="is">Is / Equals</option>
                                <option value="is_not">Is Not / Does Not Equal</option>
                            </select>
                        </div>
                        <div v-if="selectedNode.data.field">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Value</label>
                            <input v-model="selectedNode.data.value" type="text" class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Value..." />
                        </div>
                    </template>

                    <template v-if="selectedNode.type === 'action'">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Action Type</label>
                            <select v-model="selectedNode.data.actionType" class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select Action</option>
                                <option v-for="a in actionOptions" :key="a.value" :value="a.value">{{ a.label }}</option>
                            </select>
                        </div>
                        
                        <div v-if="selectedNode.data.actionType === 'send_message'" class="mt-4">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Message Text</label>
                            <textarea v-model="selectedNode.data.config.message_text" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter message..."></textarea>
                        </div>
                        <div v-if="selectedNode.data.actionType === 'send_template'" class="mt-4">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Template Name</label>
                            <input v-model="selectedNode.data.config.template_name" type="text" class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="template_hello_world" />
                        </div>
                        <div v-if="selectedNode.data.actionType === 'webhook'" class="mt-4">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Webhook URL</label>
                            <input v-model="selectedNode.data.config.webhook_url" type="url" class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="https://" />
                        </div>
                        <div v-if="selectedNode.data.actionType === 'change_status'" class="mt-4">
                            <label class="block text-sm font-bold text-gray-700 mb-1">New Status</label>
                            <select v-model="selectedNode.data.config.status" class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="open">Open</option>
                                <option value="pending">Pending</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                    </template>

                    <template v-if="selectedNode.type === 'delay'">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Delay Amount (Minutes)</label>
                            <input v-model.number="selectedNode.data.minutes" type="number" min="1" class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                    </template>

                </div>
            </div>
        </div>
    </div>
</template>
