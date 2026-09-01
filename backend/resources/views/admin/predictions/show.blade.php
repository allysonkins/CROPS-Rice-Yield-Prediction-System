<!-- Prediction Results (only Random Forest) -->
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card" style="background: var(--gray-50); border-radius: 10px; border: 1px solid var(--gray-200); border-top: 3px solid #4f46e5;">
            <div class="card-body">
                <h6><i class="bi bi-cpu" style="color: #4f46e5;"></i> Random Forest Prediction</h6>
                <div class="row g-3">
                    <div class="col-md-6 offset-md-3">
                        <div class="card p-3 text-center" style="border: 2px solid #0f4c2b; background: white; border-radius: 10px; box-shadow: var(--shadow-sm);">
                            <strong>Predicted Yield</strong>
                            <h3 class="mt-2" style="color: #0f4c2b;">
                                {{ $prediction ? number_format($prediction->predicted_yield_tons_ha, 2) : 'N/A' }} t/ha
                            </h3>
                        </div>
                    </div>
                </div>

                @if($prediction)
                    @php
                        $yield = $prediction->predicted_yield_tons_ha;
                        $status = $yield >= 4.5 ? 'High' : ($yield >= 3.5 ? 'Medium' : 'Low');
                        $color = $yield >= 4.5 ? 'success' : ($yield >= 3.5 ? 'warning' : 'danger');
                    @endphp
                    <div class="mt-3 text-center">
                        <span class="badge bg-{{ $color }}" style="font-size: 14px; padding: 6px 16px;">
                            <i class="bi bi-{{ $yield >= 4.5 ? 'check-circle' : ($yield >= 3.5 ? 'exclamation-triangle' : 'x-circle') }}"></i>
                            {{ $status }} Yield ({{ number_format($yield, 2) }} t/ha)
                        </span>
                    </div>
                @else
                    <div class="mt-3 text-center text-muted">
                        No prediction available for this farm record.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>