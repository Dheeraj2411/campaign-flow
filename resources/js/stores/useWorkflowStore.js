import { defineStore } from "pinia";
import axios from "axios";

export const useWorkflowStore = defineStore("workflows", {
    state: () => ({
        workflows: [],
        loading: false,
        error: null,
    }),
    actions: {
        async fetchWorkflows() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get("/workflows");
                this.workflows = response.data.workflows || response.data || [];
            } catch (error) {
                this.error = error.response?.data?.message || error.message;
            } finally {
                this.loading = false;
            }
        },

        async saveWorkflow(payload) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.post("/workflows", payload);
                this.workflows.push(response.data);
                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || error.message;
                throw error;
            } finally {
                this.loading = false;
            }
        },
    },
});
