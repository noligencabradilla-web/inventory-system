<?php
  $brand = 'Inventory System';
  $pageTitle = 'My Requests';
?>

<?php $__env->startSection('sidebar'); ?>
    <?php echo $__env->make('client.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<style>
    table{ width:100%; border-collapse: collapse; }
    th, td{ 
        border:1px solid #e2e8f0; 
        padding:10px; 
        text-align:left;
        transition: background-color 0.2s ease;
    }
    th{ 
        background:linear-gradient(135deg, #f8fafc, #f1f5f9);
        font-weight:700;
    }
    tr:hover td{
        background-color: rgba(37,99,235,.03);
    }

    .muted{ color:#64748b; font-size:12px; }
    .pill{
        display:inline-block;
        padding:4px 10px;
        border-radius:999px;
        font-size:12px;
        border:1px solid #e2e8f0;
        background:#f8fafc;
        color:#475569;
        font-weight:800;
        transition: all 0.3s ease;
    }
    .pill:hover{
        transform: scale(1.05);
    }
    .pill.pending{ 
        border-color:#bfdbfe; 
        background:#eff6ff; 
        color:#1d4ed8;
    }
    .pill.pending:hover{
        box-shadow: 0 4px 12px rgba(29,78,216,.2);
    }
    .pill.approved{ 
        border-color:#bbf7d0; 
        background:#ecfdf5; 
        color:#065f46;
    }
    .pill.approved:hover{
        box-shadow: 0 4px 12px rgba(6,95,70,.2);
    }
    .pill.rejected{ 
        border-color:#fecaca; 
        background:#fef2f2; 
        color:#991b1b;
    }
    .pill.rejected:hover{
        box-shadow: 0 4px 12px rgba(153,27,27,.2);
    }

    /* cancel button style (red ghost) */
    .btn-cancel{
        padding:9px 12px;
        border-radius:10px;
        border:1px solid #dc2626;
        background:#fff;
        color:#dc2626;
        cursor:pointer;
        font-weight:700;
        transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
    }
    .btn-cancel:hover{ 
        background:#b91c1c; 
        color:#fff;
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(220,38,38,.2);
    }
    .btn-cancel:active{
        transform: translateY(0);
    }

    .card{
        border:1px solid #e2e8f0;
        border-radius:14px;
        overflow:hidden;
        background:#fff;
        box-shadow:0 1px 2px rgba(15,23,42,.06);
        margin-bottom:14px;
        transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease, border-color 0.3s ease;
    }
    .card:hover{
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(15,23,42,.15);
        border-color: rgba(37,99,235,.2);
    }
    .card-head{
        display:flex;
        justify-content:space-between;
        gap:10px;
        padding:14px 16px;
        background:linear-gradient(135deg, rgba(37,99,235,.05), rgba(99,102,241,.02));
        border-bottom:1px solid #e2e8f0;
        cursor:pointer;
        align-items:flex-start;
        transition: background 0.3s ease, border-bottom-color 0.3s ease;
    }
    .card:hover .card-head{ 
        background:linear-gradient(135deg, rgba(37,99,235,.08), rgba(99,102,241,.04));
        border-bottom-color: rgba(37,99,235,.15);
    }
    .title{ font-size:18px; font-weight:900; color:#0f172a; }
    
    .card-body{
        display:none;
        padding:16px 16px;
        background:linear-gradient(135deg, #fafbfc 0%, rgba(99,102,241,.02) 100%);
        border-top:2px solid rgba(37,99,235,.08);
        animation: slideDown 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .card-body.open{ display:block; }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            max-height: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            max-height: 1000px;
            transform: translateY(0);
        }
    }
    
    .card-toggle{ color:var(--muted); font-size:12px; font-weight:600; transition: color 0.3s ease; }
    .card:hover .card-toggle{ color: var(--blue); }

    /* Mobile-only: compact sized cards and text */
    @media (max-width: 640px) {
        .title { font-size: 14px; font-weight: 800; }
        
        .card {
            margin-bottom: 10px;
            border-radius: 10px;
        }
        
        .card-head {
            padding: 10px 12px;
            flex-wrap: wrap;
            gap: 6px;
            align-items:center;
        }

        .card-head > div:first-child { flex: 1; }
        
        .card-head > div:last-child {
            text-align: right;
            font-size: 12px;
            white-space: normal;
        }

        .card-head > div:last-child div {
            font-size: 11px;
            margin-bottom: 2px;
        }

        .card-head > div:last-child div[style*="font-weight:900"] {
            font-size: 12px;
        }

        .card-body {
            padding: 10px 12px;
        }

        table { font-size: 12px; }
        th, td { padding: 8px; }

        .muted { font-size: 11px; }
        .pill { padding: 3px 8px; font-size: 11px; }

        .btn-cancel {
            padding: 6px 8px;
            font-size: 12px;
        }
    }
</style>



<?php $__empty_1 = true; $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php
        $status = $req->status; // pending / approved / ready_to_receive / rejected / released
        $code = $req->verification_code;
        $rid = 'req-'.$req->id;
        $requestTotal = $req->items->sum('requested_qty');
        $approvedTotal = $req->items->sum('approved_qty');
    ?>

    <div class="card">
        <div class="card-head" onclick="toggleReq('<?php echo e($rid); ?>')">
            <div style="flex:1;">
                <div class="title">
                    Request from <span style="color:#2563eb;"><?php echo e($req->office); ?></span>
                    <span class="muted">•</span>
                    <?php if($req->member): ?>
                        <span style="color:#059669;">Member: <?php echo e($req->member->name); ?></span>
                        <span class="muted">•</span>
                    <?php endif; ?>
                    <span class="muted"><?php echo e($req->created_at?->format('M d, Y')); ?></span>
                </div>
                <div style="margin-top:4px; font-size:12px; color:#475569;">
                    Total requested: <?php echo e($requestTotal); ?>

                    <?php if($status !== 'pending'): ?>
                        • Approved total: <?php echo e($approvedTotal); ?>

                    <?php endif; ?>
                </div>

                <div style="margin-top:6px;">
                    <span class="muted">Status:</span>
                    <?php if($status === 'pending'): ?>
                        <span class="pill pending">PENDING</span>
                    <?php elseif($status === 'approved'): ?>
                        <span class="pill approved">APPROVED</span>
                    <?php elseif($status === 'ready_to_receive'): ?>
                        <span class="pill approved">READY TO RECEIVE</span>
                    <?php elseif($status === 'cancelled'): ?>
                        <span class="pill rejected">CANCELLED</span>
                    <?php elseif($status === 'rejected'): ?>
                        <span class="pill rejected">REJECTED</span>
                    <?php else: ?>
                        <span class="pill"><?php echo e(strtoupper(str_replace('_',' ', $status))); ?></span>
                    <?php endif; ?>
                </div>

                <?php if($req->reason): ?>
                    <div style="margin-top:8px;">
                        <span class="muted">Reason:</span>
                        <div style="margin-top:4px; padding:6px 10px; background:linear-gradient(135deg, #f8fafc, #f1f5f9); border:1px solid #e2e8f0; border-radius:6px; color:#475569; font-size:12px; line-height:1.4;">
                            <?php echo e($req->reason); ?>

                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div style="text-align:right; white-space:nowrap;">
                <div style="font-weight:900; font-size:12px; margin-bottom:4px;">
                    Ref. No: <span style="color:#0f172a;">#<?php echo e($req->id); ?></span>
                </div>
                <?php if($code): ?>
                    <div style="font-weight:900; font-size:18px;">
                        Code: <span style="color:#0f172a;"><?php echo e($code); ?></span>
                    </div>
                    <div class="muted">Show to admin</div>
                <?php else: ?>
                    <div class="muted">Waiting code</div>
                <?php endif; ?>
                <div class="card-toggle" style="margin-top:4px;">Click to expand</div>
            </div>
        </div>

        <div id="<?php echo e($rid); ?>" class="card-body">
            <div style="overflow-x:auto; border-radius:16px; box-shadow:0 8px 25px rgba(59,130,246,0.15); background:linear-gradient(135deg, #eff6ff, #dbeafe);">
                <table style="width:100%; border-collapse:collapse;">
                    <tr style="background:linear-gradient(135deg, #3b82f6, #1d4ed8);">
                        <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#000000; font-size:12px;">Item</th>
                        <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#000000; font-size:12px;">Requested</th>
                        <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#000000; font-size:12px;">Approved</th>
                        <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#000000; font-size:12px;">Result</th>
                    </tr>

                    <?php $__empty_2 = true; $__currentLoopData = $req->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                        <?php
                            $requested = (int) $item->requested_qty;
                            $approved = (int)($item->approved_qty ?? 0);
                        ?>

                        <tr style="border-bottom:1px solid #e0e7ff; background:linear-gradient(135deg, #ffffff, #f8fafc);">
                            <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff;">
                                <div style="font-weight:700; color:#1e40af; font-size:14px;"><?php echo e($item->stock?->id_no ?? ''); ?></div>
                                <div style="color:#64748b; font-size:11px; margin-top:3px;"><?php echo e($item->stock?->description ?? 'N/A'); ?> • Unit: <?php echo e($item->stock?->unit ?? '—'); ?></div>
                                <?php if($item->rejection_reason && $item->status === 'rejected'): ?>
                                    <div style="margin-top:6px; padding:6px 8px; background:#fef2f2; border:1px solid #fecaca; border-radius:6px; color:#991b1b; font-size:11px; line-height:1.3;">
                                        <strong>Rejection Reason:</strong> <?php echo e($item->rejection_reason); ?>

                                    </div>
                                <?php endif; ?>
                            </td>

                            <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; color:#475569; font-weight:600;"><?php echo e($requested); ?></td>

                            
                            <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; color:#475569; font-weight:600;">
                                <?php if($status === 'pending'): ?>
                                    <span style="color:#64748b;">—</span>
                                <?php else: ?>
                                    <span style="color:#1e40af; font-weight:700;"><?php echo e($approved); ?></span>
                                <?php endif; ?>
                            </td>

                            
                            <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff;">
                                <?php if($status === 'pending'): ?>
                                    <span style="padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; border:1px solid #bfdbfe; background:#eff6ff; color:#1d4ed8;">PENDING</span>
                                <?php elseif($status === 'cancelled'): ?>
                                    <span style="padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; border:1px solid #fecaca; background:#fef2f2; color:#991b1b;">CANCELLED</span>
                                <?php else: ?>
                                    <?php if($approved > 0): ?>
                                        <span style="padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; border:1px solid #bbf7d0; background:#ecfdf5; color:#065f46;">APPROVED</span>
                                    <?php else: ?>
                                        <span style="padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; border:1px solid #fecaca; background:#fef2f2; color:#991b1b;">REJECTED</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                        <tr style="background:linear-gradient(135deg, #f8fafc, #f1f5f9);">
                            <td colspan="6" style="padding:20px 10px; text-align:center; color:#64748b; font-size:14px;">No request items found.</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <?php if($status === 'pending'): ?>
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:12px;">
                    <button type="button" class="btn-cancel" onclick="showCancelConfirm(<?php echo e($req->id); ?>)">
                        Cancel Request
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="muted">No requests found.</div>
<?php endif; ?>

<!-- Cancel Confirmation Modal -->
<div id="cancelConfirmModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:5000; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:14px; box-shadow:0 10px 40px rgba(0,0,0,.25); max-width:420px; width:90%; padding:24px;">
        <h3 style="margin:0 0 8px 0; font-size:18px; color:#0f172a; font-weight:800;">Cancel Request</h3>
        <p style="margin:0 0 20px 0; color:#475569; font-size:14px; line-height:1.5;">Cancel this pending request? This action cannot be undone.</p>
        <div style="display:flex; gap:10px; justify-content:flex-end;">
            <button type="button" class="modal-btn-cancel" onclick="closeCancelConfirm()">Keep Request</button>
            <button type="button" class="modal-btn-confirm" onclick="submitCancelRequest()">Cancel Request</button>
        </div>
    </div>
</div>

<style>
.modal-btn-cancel, .modal-btn-confirm {
    padding:10px 16px; 
    border-radius:10px; 
    border:none; 
    font-weight:700; 
    cursor:pointer; 
    font-size:14px;
    transition: all 0.3s ease;
}
.modal-btn-cancel {
    background:#e2e8f0; 
    color:#0f172a;
}
.modal-btn-cancel:hover {
    background:#cbd5e1;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,.1);
}
.modal-btn-cancel:active {
    transform: translateY(0);
}
.modal-btn-confirm {
    background:#dc2626; 
    color:#fff;
}
.modal-btn-confirm:hover {
    background:#b91c1c;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(220,38,38,.2);
}
.modal-btn-confirm:active {
    transform: translateY(0);
}
</style>

<script>
let pendingCancelRequestId = null;

function toggleReq(id){
    const el = document.getElementById(id);
    if(!el) return;
    el.classList.toggle('open');
}

function showCancelConfirm(requestId){
    pendingCancelRequestId = requestId;
    document.getElementById('cancelConfirmModal').style.display = 'flex';
}

function closeCancelConfirm(){
    document.getElementById('cancelConfirmModal').style.display = 'none';
    pendingCancelRequestId = null;
}

function submitCancelRequest(){
    if(!pendingCancelRequestId) return;
    
    // Use fetch POST instead of form submission for more reliable handling
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '<?php echo e(csrf_token()); ?>';
    const url = `<?php echo e(url('/client/requests')); ?>/${pendingCancelRequestId}/cancel`;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        body: JSON.stringify({})
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (response.ok) {
            // If successful, reload the page to see the update
            window.location.reload();
        } else {
            return response.json().then(data => {
                console.log('Error response:', data);
                alert(data.error || 'Failed to cancel request. Please try again.');
                closeCancelConfirm();
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while cancelling the request.');
        closeCancelConfirm();
    });
}

// Close modal on Escape key
document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeCancelConfirm();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/client/requests/index.blade.php ENDPATH**/ ?>