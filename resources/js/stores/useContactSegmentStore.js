import { defineStore } from "pinia";
import axios from "axios";

export const useContactSegmentStore = defineStore("contactSegments", {
    state: () => ({
        segments: [],
        loading: false,
        error: null,
    }),
    actions: {
        async fetchSegments() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get("/contact-segments");

                // API can return inline page; normalize to segments array
                this.segments = response.data.segments || response.data || [];
            } catch (error) {
                this.error = error.response?.data?.message || error.message;
            } finally {
                this.loading = false;
            }
        },

        async createSegment(payload) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.post("/contact-segments", payload);
                const createdSegment = response.data;
                if (createdSegment) {
                    this.segments.push(createdSegment);
                }
                return createdSegment;
            } catch (error) {
                this.error = error.response?.data?.message || error.message;
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteSegment(segmentId) {
            this.loading = true;
            this.error = null;

            try {
                await axios.delete(`/contact-segments/${segmentId}`);
                this.segments = this.segments.filter(
                    (segment) => segment.id !== segmentId,
                );
            } catch (error) {
                this.error = error.response?.data?.message || error.message;
                throw error;
            } finally {
                this.loading = false;
            }
        },
    },
});
