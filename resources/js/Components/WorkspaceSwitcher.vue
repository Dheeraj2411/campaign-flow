<script setup>
import { ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const page = usePage();
const workspaces = ref(page.props.auth.available_workspaces);
const activeWorkspace = ref(page.props.auth.workspace);

const switchWorkspace = (workspaceId) => {
    router.post(route('workspaces.switch', workspaceId), {}, {
        preserveState: false,
        onSuccess: () => {
            // Reload page to refresh all data under new tenant scope
            window.location.reload();
        }
    });
};
</script>

<template>
    <div class="relative ml-3" v-if="activeWorkspace">
        <Dropdown align="right" width="48">
            <template #trigger>
                <span class="inline-flex rounded-md">
                    <button
                        type="button"
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                    >
                        <span class="material-symbols-outlined mr-2 text-admin-primary">workspaces</span>
                        {{ activeWorkspace.name }}

                        <svg
                            class="ml-2 -mr-0.5 h-4 w-4"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                </span>
            </template>

            <template #content>
                <div class="block px-4 py-2 text-xs text-gray-400">
                    Switch Workspace
                </div>

                <button
                    v-for="workspace in workspaces"
                    :key="workspace.id"
                    @click="switchWorkspace(workspace.id)"
                    class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
                    :class="{ 'bg-gray-50 font-semibold': workspace.id === activeWorkspace.id }"
                >
                    {{ workspace.name }}
                </button>

                <div class="border-t border-gray-100"></div>

                <DropdownLink :href="route('settings')">
                    Workspace Settings
                </DropdownLink>
            </template>
        </Dropdown>
    </div>
</template>

<style scoped>
.material-symbols-outlined {
    font-size: 18px;
}
</style>
