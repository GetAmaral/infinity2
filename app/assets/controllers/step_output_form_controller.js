import { Controller } from '@hotwired/stimulus';

/**
 * Step Output Form Controller
 *
 * Handles dynamic filtering of PipelineStage based on selected Step's TreeFlow's Pipeline
 */
export default class extends Controller {
    connect() {
        console.log('📝 Step Output Form Controller Connected');

        // Find the step and pipeline stage select elements
        this.stepSelect = this.element.querySelector('select[name*="[step]"]');
        this.pipelineStageSelect = this.element.querySelector('select[name*="[pipelineStage]"]');

        if (!this.stepSelect || !this.pipelineStageSelect) {
            console.log('Step or PipelineStage select not found');
            return;
        }

        // Listen to step changes
        this.element.addEventListener('change', (event) => {
            if (event.target === this.stepSelect) {
                this.onStepChange();
            }
        });
    }

    async onStepChange() {
        const stepId = this.stepSelect.value;

        if (!stepId) {
            // No step selected, allow all pipeline stages
            return;
        }

        try {
            // Fetch the Step to get its TreeFlow's Pipeline
            const response = await fetch(`/api/steps/${stepId}`, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/ld+json'
                }
            });

            if (!response.ok) {
                return;
            }

            const step = await response.json();
            const treeFlowId = step.treeFlow ? step.treeFlow.split('/').pop() : null;

            if (!treeFlowId) {
                return;
            }

            // Fetch the TreeFlow to get its Pipeline
            const treeFlowResponse = await fetch(`/api/tree_flows/${treeFlowId}`, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/ld+json'
                }
            });

            if (!treeFlowResponse.ok) {
                return;
            }

            const treeFlow = await treeFlowResponse.json();
            const pipelineId = treeFlow.pipeline ? treeFlow.pipeline.split('/').pop() : null;

            if (pipelineId) {
                // Update the pipeline stage selector with the pipeline filter
                this.filterPipelineStages(pipelineId);
            }
        } catch (error) {
            console.error('Error fetching step/treeflow data:', error);
        }
    }

    filterPipelineStages(pipelineId) {
        // Find the TomSelect instance for pipeline stage
        if (!this.pipelineStageSelect) {
            return;
        }

        const tomSelectInstance = this.pipelineStageSelect.tomselect;
        if (!tomSelectInstance) {
            return;
        }

        // Update the load function to include pipeline filter
        const originalLoad = tomSelectInstance.settings.load;

        tomSelectInstance.settings.load = (query, callback) => {
            if (!query.length) {
                return callback();
            }

            // Add pipeline filter to the search
            const apiUrl = tomSelectInstance.settings.loadUrl || '/pipeline_stage/api/search';
            fetch(`${apiUrl}?q=${encodeURIComponent(query)}&pipeline=${pipelineId}`, {
                credentials: 'same-origin'
            })
                .then(response => response.json())
                .then(json => {
                    // Transform the response
                    const items = Object.values(json)[0].map(item => ({
                        value: item.id,
                        text: item.name || item.title || item.label || item.id
                    }));
                    callback(items);
                })
                .catch(error => {
                    console.error('Error loading pipeline stages:', error);
                    callback();
                });
        };

        // Clear and reload
        tomSelectInstance.clear();
        tomSelectInstance.clearOptions();
    }
}
