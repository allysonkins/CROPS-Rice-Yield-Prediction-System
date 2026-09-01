<!-- ============================================================ -->
<!-- FARM RECORDS MODAL -->
<!-- ============================================================ -->
<div class="modal fade" id="farmRecordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--green); color: white;">
                <h5 class="modal-title" id="farmRecordModalLabel">
                    <i class="bi bi-clipboard-data-fill"></i> 
                    <span id="modalTitle">Add Farm Record</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="farmRecordModalBody">
                <!-- Loading spinner -->
                <div class="text-center py-4" id="modalLoading">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading form...</p>
                </div>
                <!-- Form content will be loaded here -->
                <div id="modalContent" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>