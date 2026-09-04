@extends('layouts.app')

@section('header_title', 'Order Success')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="text-center mb-4">
                <i class="fa-solid fa-circle-check text-success" style="font-size: 5rem;"></i>
                <h3 class="fw-bold mt-3">Purchase Successful!</h3>
                <p class="text-muted">You have successfully purchased {{ $order->quantity }}x {{ $order->product_name }}.</p>
            </div>

            <div class="premium-card p-4">
                <h5 class="fw-bold mb-3 border-bottom border-secondary pb-2">Your Items (Accounts/Keys)</h5>
                
                @if(count($items) > 0)
                    <div class="alert alert-warning mb-4">
                        <i class="fa-solid fa-triangle-exclamation"></i> 
                        <strong>Important:</strong> Please copy your credentials immediately. Keep them safe.
                    </div>

                    <div class="d-flex flex-column gap-3">
                        @foreach($items as $index => $item)
                        @php
                            $textToDisplay = is_array($item) ? ($item['details'] ?? $item['content'] ?? json_encode($item)) : $item;
                            $filePath = is_array($item) ? ($item['file_path'] ?? null) : null;
                            $fileName = is_array($item) ? ($item['file_name'] ?? 'attachment') : null;
                        @endphp
                        <div class="p-3 rounded-4 shadow-sm" style="background: var(--bg-surface); border: 1px solid var(--border-color);">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-primary px-3 py-2 rounded-pill fw-bold" style="font-size: 0.85rem;">
                                    <i class="fa-solid fa-key me-1"></i> Account #{{ $index + 1 }}
                                </span>
                                <button type="button" class="btn btn-sm btn-primary-custom rounded-pill px-3 py-1 fw-bold" onclick="copyItem({{ $index }}, this)">
                                    <i class="fa-solid fa-copy me-1"></i> Copy All
                                </button>
                            </div>

                            <div class="position-relative">
                                <pre id="itemContent{{ $index }}" class="p-3 mb-0 rounded-3 font-monospace" style="background: #0f172a; color: #f8fafc; font-size: 0.95rem; line-height: 1.6; border: 1px solid #1e293b; white-space: pre-wrap; word-break: break-word; user-select: all;">{{ $textToDisplay }}</pre>
                            </div>

                            @if($filePath)
                            <div class="mt-3 pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2" style="border-color: var(--border-color) !important;">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-solid fa-paperclip text-primary fs-5"></i>
                                    <span class="small fw-bold">{{ $fileName }}</span>
                                </div>
                                <a href="{{ asset($filePath) }}" download="{{ $fileName }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-bold">
                                    <i class="fa-solid fa-download me-1"></i> Download File
                                </a>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <p class="text-muted">No specific account details were returned. If this is unexpected, please contact support.</p>
                    </div>
                @endif

                <div class="mt-4 text-center">
                    <a href="{{ route('logs.index') }}" class="btn btn-primary-custom px-4"><i class="fa-solid fa-arrow-left"></i> Back to Marketplace</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyItem(index, btn) {
    const el = document.getElementById('itemContent' + index);
    if (!el) return;
    const text = el.innerText || el.textContent;

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showCopiedEffect(btn);
        }).catch(() => {
            fallbackCopyText(text, btn, el);
        });
    } else {
        fallbackCopyText(text, btn, el);
    }
}

function fallbackCopyText(text, btn, el) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.left = "-999999px";
    textArea.style.top = "-999999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    let successful = false;
    try {
        successful = document.execCommand('copy');
    } catch (err) {
        console.error('execCommand copy error', err);
    }
    document.body.removeChild(textArea);

    if (successful) {
        showCopiedEffect(btn);
    } else {
        // Fallback: highlight the text on screen so the user can easily press Ctrl+C
        if (el) {
            const range = document.createRange();
            range.selectNodeContents(el);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
        }
        showCopiedEffect(btn, 'Selected! Press Ctrl+C');
    }
}

function showCopiedEffect(btn, customText) {
    const originalText = btn.innerHTML;
    btn.innerHTML = `<i class="fa-solid fa-check me-1"></i> ${customText || 'Copied!'}`;
    btn.classList.remove('btn-primary-custom');
    btn.classList.add('btn-success');
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-primary-custom');
    }, 2000);
}
</script>
@endpush
