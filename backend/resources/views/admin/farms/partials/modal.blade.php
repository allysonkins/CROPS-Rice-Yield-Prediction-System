@if(auth()->user()->role === 'admin')
<div class="modal fade" id="farmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--green); color: white;">
                <h5 class="modal-title" id="farmModalLabel">
                    <i class="bi bi-geo-alt-fill"></i> 
                    <span id="modalTitle">Add Farm</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="farmModalBody">
                <div class="text-center py-4" id="modalLoading">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading form...</p>
                </div>
                <div id="modalContent" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>
@endif