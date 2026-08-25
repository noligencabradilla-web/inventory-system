<?php
  $brand = 'Inventory System';
  $pageTitle = 'Transaction History';
?>

<?php $__env->startSection('sidebar'); ?>
    <?php echo $__env->make('client.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <style>
        .cards-grid{ display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px; margin-bottom:22px; }
        .card{ 
            border:1px solid var(--line); 
            border-radius:14px; 
            background:#ffffff; 
            box-shadow:0 10px 28px rgba(15,23,42,.06); 
            overflow:hidden;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .card:hover{
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(15,23,42,.15);
            border-color: rgba(37,99,235,.2);
        }
        .card-head{ 
            padding:14px 16px; 
            background:linear-gradient(135deg, rgba(37,99,235,.05), rgba(99,102,241,.02));
            border-bottom:1px solid var(--line); 
            display:flex; 
            justify-content:space-between; 
            gap:12px; 
            cursor:pointer;
            transition: all 0.3s ease;
        }
        .card:hover .card-head{ 
            background:linear-gradient(135deg, rgba(37,99,235,.08), rgba(99,102,241,.04));
            border-bottom-color: rgba(37,99,235,.15);
        }
        .card-body{ 
            padding:16px;
            background:linear-gradient(135deg, #fafbfc 0%, rgba(99,102,241,.02) 100%);
            transition: all 0.3s ease;
        }
        .card-body.hidden{ display:none; }
        .card-title{ font-weight:800; font-size:16px; }
        .card-sub{ color:var(--muted); font-size:13px; margin-top:4px; }
        
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
        }

        
        
        .muted{ color:var(--muted); }
        .list{ list-style: disc inside; color:var(--muted); }
        .list li{ margin-bottom:6px; }
    </style>

    <div style="margin-top:16px;">
        <h2 style="margin-bottom:14px;">Transaction History</h2>

        <!-- Filters Section -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-body" style="position:relative;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
                    <div>
                        <div class="card-title">Transaction Filters</div>
                        <div class="card-sub">Filter by type and member</div>
                    </div>

                    <form id="filterForm" method="GET" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-top:4px;">
                        <div style="display:flex; gap:8px; align-items:center;">
                            <label style="font-size:13px; font-weight:600; color:#374151;">Type:</label>
                            <div style="display:flex; gap:12px;">
                                <label style="display:flex; align-items:center; gap:4px; cursor:pointer; font-size:13px;">
                                    <input 
                                        type="radio" 
                                        name="type" 
                                        value="all" 
                                        <?php echo e(($type ?? 'all') === 'all' ? 'checked' : ''); ?>

                                        onchange="document.getElementById('filterForm').submit();"
                                    >
                                    <span>All</span>
                                </label>
                                <label style="display:flex; align-items:center; gap:4px; cursor:pointer; font-size:13px;">
                                    <input 
                                        type="radio" 
                                        name="type" 
                                        value="request" 
                                        <?php echo e(($type ?? 'all') === 'request' ? 'checked' : ''); ?>

                                        onchange="document.getElementById('filterForm').submit();"
                                    >
                                    <span>Request</span>
                                </label>
                                <label style="display:flex; align-items:center; gap:4px; cursor:pointer; font-size:13px;">
                                    <input 
                                        type="radio" 
                                        name="type" 
                                        value="deduction" 
                                        <?php echo e(($type ?? 'all') === 'deduction' ? 'checked' : ''); ?>

                                        onchange="document.getElementById('filterForm').submit();"
                                    >
                                    <span>Deduction</span>
                                </label>
                                <label style="display:flex; align-items:center; gap:4px; cursor:pointer; font-size:13px;">
                                    <input 
                                        type="radio" 
                                        name="type" 
                                        value="distribution" 
                                        <?php echo e(($type ?? 'all') === 'distribution' ? 'checked' : ''); ?>

                                        onchange="document.getElementById('filterForm').submit();"
                                    >
                                    <span>Distribution</span>
                                </label>
                                <label style="display:flex; align-items:center; gap:4px; cursor:pointer; font-size:13px;">
                                    <input 
                                        type="radio" 
                                        name="type" 
                                        value="direct" 
                                        <?php echo e(($type ?? 'all') === 'direct' ? 'checked' : ''); ?>

                                        onchange="document.getElementById('filterForm').submit();"
                                    >
                                    <span>Direct</span>
                                </label>
                            </div>
                        </div>

                        <select
                            id="filterMember"
                            name="member"
                            style="padding:8px 10px; border-radius:10px; border:1px solid var(--line); background:#fff; min-width:180px;"
                            onchange="document.getElementById('filterForm').submit();"
                        >
                            <option value="">All Members</option>
                            <?php $__currentLoopData = $clientMembers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($member->id); ?>" <?php echo e($member->id == ($memberId ?? '') ? 'selected' : ''); ?>><?php echo e($member->name); ?> (<?php echo e($member->email); ?>)</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </form>
                </div>
            </div>
        </div>

        <!-- Direct Deductions Section -->
        <?php if(isset($directDeductions) && $directDeductions->count() > 0): ?>
            <h3 style="margin-bottom:12px; color:#dc2626; font-size:16px;">Direct Inventory Deductions</h3>
            <?php $__empty_1 = true; $__currentLoopData = $directDeductions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deduction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="card" style="margin-bottom:14px; border-left:4px solid #dc2626;">
                    <div class="card-head" onclick="toggleDeduction('deduction-<?php echo e($deduction->id); ?>')">
                        <div>
                            <div class="card-title" style="color:#dc2626;">Deduction #<?php echo e($deduction->id); ?></div>
                            <div class="card-sub">Deducted on <?php echo e($deduction->created_at?->format('F j, Y, g:i A')); ?></div>
                            <div style="margin-top:8px; padding:6px 10px; background:linear-gradient(135deg, #fef2f2, #fecaca); border-radius:8px; border:1px solid #fca5a5; display: none;">
                                <div style="font-size:12px; color:#991b1b; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Item Deducted</div>
                                <div style="font-size:14px; font-weight:800; color:#7f1d1d; margin-top:2px;">
                                    <?php if($deduction->stockRequestItem): ?>
                                        <?php echo e($deduction->stockRequestItem->stock->description ?? 'Unknown Item'); ?>

                                    <?php else: ?>
                                        <?php echo e($deduction->reason ?? 'Direct Request Item'); ?>

                                    <?php endif; ?>
                                    (<?php echo e($deduction->deducted_qty); ?> units)
                                </div>
                                <?php if($deduction->member): ?>
                                    <div style="font-size:12px; color:#991b1b; margin-top:4px;">Assigned to: <?php echo e($deduction->member->name); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="text-align:right; min-width:160px;">
                            <div style="font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Type</div>
                            <div style="margin-top:4px;">
                                <span style="padding:4px 8px; border-radius:6px; background:#dc2626; color:#fff; font-size:12px; font-weight:700;">DEDUCTION</span>
                            </div>
                        </div>
                    </div>

                    <div id="deduction-<?php echo e($deduction->id); ?>" class="card-body hidden">
                        <div style="font-weight:700; margin-bottom:12px; color:var(--text); font-size:14px;">Deduction Details</div>
                        <div style="display:grid; gap:8px;">
                            <div style="padding:12px; background:linear-gradient(135deg, #fef2f2, #fecaca); border-radius:8px;">
                                <div style="font-weight:600; color:#7f1d1d; margin-bottom:4px;">
                                    <?php if($deduction->stockRequestItem): ?>
                                        <?php echo e($deduction->stockRequestItem->stock->description ?? 'Unknown Item'); ?>

                                    <?php else: ?>
                                        <?php echo e($deduction->reason ?? 'Direct Request Item'); ?>

                                    <?php endif; ?>
                                </div>
                                <div style="display:flex; gap:16px; font-size:12px; color:#991b1b;">
                                    <?php if($deduction->stockRequestItem): ?>
                                        <span><strong>ID:</strong> <?php echo e($deduction->stockRequestItem->stock->id_no ?? 'N/A'); ?></span>
                                        <span><strong>Unit:</strong> <?php echo e($deduction->stockRequestItem->stock->unit ?? 'N/A'); ?></span>
                                    <?php else: ?>
                                        <span><strong>Type:</strong> Direct Request Item</span>
                                    <?php endif; ?>
                                    <span><strong>Quantity:</strong> <?php echo e($deduction->deducted_qty); ?></span>
                                </div>
                                <?php if($deduction->member): ?>
                                    <div style="margin-top:8px; padding:6px 8px; background:#fff; border-radius:6px; border:1px solid #fca5a5;">
                                        <strong>Assigned Member:</strong> <?php echo e($deduction->member->name); ?> (<?php echo e($deduction->member->email); ?>)
                                    </div>
                                <?php endif; ?>
                                
                                <?php if($deduction->reason): ?>
                                    
                                <?php endif; ?>
                                <?php if($deduction->received_by): ?>
                                    <div style="margin-top:8px; padding:6px 8px; background:#fff; border-radius:6px; border:1px solid #10b981;">
                                        <strong>Received By:</strong> <?php echo e($deduction->received_by); ?>

                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <?php endif; ?>
            
            <hr style="margin:24px 0; border:none; border-top:1px solid #e2e8f0;">
        <?php endif; ?>

        <!-- Member Distribution Section -->
        <?php if((isset($memberDistributions) && $memberDistributions->count() > 0) || (isset($distributionDirectRequests) && $distributionDirectRequests->count() > 0)): ?>
            <h3 style="margin-bottom:12px; color:#2563eb; font-size:16px;">Member Distributions</h3>

            <?php $__currentLoopData = $memberDistributions ?? collect(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $distribution): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="card" style="margin-bottom:14px; border-left:4px solid #2563eb;">
                    <div class="card-head" onclick="toggleDistribution('distribution-<?php echo e($distribution->id); ?>')">
                        <div>
                            <div class="card-title" style="color:#2563eb;">Distribution #<?php echo e($distribution->id); ?></div>
                            <div class="card-sub">Distributed on <?php echo e($distribution->created_at?->format('F j, Y, g:i A')); ?></div>
                            <div style="margin-top:8px; padding:6px 10px; background:linear-gradient(135deg, #eff6ff, #dbeafe); border-radius:8px; border:1px solid #93c5fd; display: none;">
                                <div style="font-size:12px; color:#1d4ed8; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Item Distributed</div>
                                <div style="font-size:14px; font-weight:800; color:#1e40af; margin-top:2px;">
                                    <?php echo e($distribution->stockRequestItem?->stock?->description ?? 'Unknown Item'); ?>

                                    (<?php echo e($distribution->distributed_qty); ?> units)
                                </div>
                                <?php if($distribution->member): ?>
                                    <div style="font-size:12px; color:#1d4ed8; margin-top:4px;">Member: <?php echo e($distribution->member->name); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="text-align:right; min-width:160px;">
                            <div style="font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Type</div>
                            <div style="margin-top:4px;">
                                <span style="padding:4px 8px; border-radius:6px; background:#2563eb; color:#fff; font-size:12px; font-weight:700;">DISTRIBUTION</span>
                            </div>
                        </div>
                    </div>

                    <div id="distribution-<?php echo e($distribution->id); ?>" class="card-body hidden">
                        <div style="font-weight:700; margin-bottom:12px; color:var(--text); font-size:14px;">Distribution Details</div>
                        <div style="display:grid; gap:8px;">
                            <div style="padding:12px; background:linear-gradient(135deg, #eff6ff, #dbeafe); border-radius:8px;">
                                <div style="font-weight:600; color:#1e40af; margin-bottom:4px;"><?php echo e($distribution->stockRequestItem?->stock?->description ?? 'Unknown Item'); ?></div>
                                <div style="display:flex; gap:16px; font-size:12px; color:#1d4ed8;">
                                    <span><strong>ID:</strong> <?php echo e($distribution->stockRequestItem?->stock?->id_no ?? 'N/A'); ?></span>
                                    <span><strong>Unit:</strong> <?php echo e($distribution->stockRequestItem?->stock?->unit ?? 'N/A'); ?></span>
                                    <span><strong>Quantity:</strong> <?php echo e($distribution->distributed_qty); ?></span>
                                </div>
                                <?php if($distribution->member): ?>
                                    <div style="margin-top:8px; padding:6px 8px; background:#fff; border-radius:6px; border:1px solid #93c5fd;">
                                        <strong>Member:</strong> <?php echo e($distribution->member->name); ?> (<?php echo e($distribution->member->email); ?>)
                                    </div>
                                <?php endif; ?>
                                <?php if($distribution->used_qty): ?>
                                    <div style="margin-top:8px; padding:6px 8px; background:#fff; border-radius:6px; border:1px solid #93c5fd;">
                                        <strong>Used Qty:</strong> <?php echo e($distribution->used_qty); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php $__currentLoopData = $distributionDirectRequests ?? collect(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $distribution): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="card" style="margin-bottom:14px; border-left:4px solid #2563eb;">
                    <div class="card-head" onclick="toggleDistribution('distribution-direct-<?php echo e($distribution->id); ?>')">
                        <div>
                            <div class="card-title" style="color:#2563eb;">Distribution #<?php echo e($distribution->id); ?></div>
                            <div class="card-sub">Distributed on <?php echo e($distribution->created_at?->format('F j, Y, g:i A')); ?></div>
                            <div style="margin-top:8px; padding:6px 10px; background:linear-gradient(135deg, #eff6ff, #dbeafe); border-radius:8px; border:1px solid #93c5fd; display: none;">
                                <div style="font-size:12px; color:#1d4ed8; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Direct Request Item</div>
                                <div style="font-size:14px; font-weight:800; color:#1e40af; margin-top:2px;">
                                    <?php echo e($distribution->reason ?? 'Direct Request Item'); ?>

                                    (<?php echo e($distribution->deducted_qty); ?> units)
                                </div>
                                <?php if($distribution->member): ?>
                                    <div style="font-size:12px; color:#1d4ed8; margin-top:4px;">Member: <?php echo e($distribution->member->name); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="text-align:right; min-width:160px;">
                            <div style="font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Type</div>
                            <div style="margin-top:4px;">
                                <span style="padding:4px 8px; border-radius:6px; background:#2563eb; color:#fff; font-size:12px; font-weight:700;">DISTRIBUTION</span>
                            </div>
                        </div>
                    </div>

                    <div id="distribution-direct-<?php echo e($distribution->id); ?>" class="card-body hidden">
                        <div style="font-weight:700; margin-bottom:12px; color:var(--text); font-size:14px;">Distribution Details</div>
                        <div style="display:grid; gap:8px;">
                            <div style="padding:12px; background:linear-gradient(135deg, #eff6ff, #dbeafe); border-radius:8px;">
                                <div style="font-weight:600; color:#1e40af; margin-bottom:4px;"><?php echo e($distribution->reason ?? 'Direct Request Item'); ?></div>
                                <div style="display:flex; gap:16px; font-size:12px; color:#1d4ed8;">
                                    <span><strong>Quantity:</strong> <?php echo e($distribution->deducted_qty); ?></span>
                                </div>
                                <?php if($distribution->member): ?>
                                    <div style="margin-top:8px; padding:6px 8px; background:#fff; border-radius:6px; border:1px solid #93c5fd;">
                                        <strong>Member:</strong> <?php echo e($distribution->member->name); ?> (<?php echo e($distribution->member->email); ?>)
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <hr style="margin:24px 0; border:none; border-top:1px solid #e2e8f0;">
        <?php endif; ?>

        <!-- Urgent Outbound Section -->
        <?php if(isset($urgentOutbounds) && $urgentOutbounds->count() > 0): ?>
            <h3 style="margin-bottom:12px; color:#dc2626; font-size:16px;">Urgent Outbound Requests</h3>
            <?php $__empty_1 = true; $__currentLoopData = $urgentOutbounds; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $outbound): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="card" style="margin-bottom:14px; border-left:4px solid #dc2626;">
                    <div class="card-head" onclick="toggleUrgentOutbound('urgent-<?php echo e($outbound->id); ?>')">
                        <div>
                            <div class="card-title" style="color:#dc2626;">Urgent Outbound #<?php echo e($outbound->id); ?></div>
                            <div class="card-sub">Created on <?php echo e($outbound->created_at?->format('F j, Y, g:i A')); ?></div>
                            <div style="margin-top:8px; padding:6px 10px; background:linear-gradient(135deg, #fef2f2, #fecaca); border-radius:8px; border:1px solid #fca5a5;">
                                <div style="font-size:12px; color:#991b1b; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Recipient</div>
                                <div style="font-size:14px; font-weight:800; color:#7f1d1d; margin-top:2px;"><?php echo e($outbound->urgent_recipient_name ?? 'Unknown'); ?></div>
                                <?php if($outbound->urgent_recipient_office): ?>
                                    <div style="font-size:12px; color:#991b1b; margin-top:4px;">Office: <?php echo e($outbound->urgent_recipient_office); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if($outbound->reason): ?>
                                
                            <?php endif; ?>
                            <div style="margin-top:8px;">
                                <span style="padding:4px 8px; border-radius:6px; background:#dc2626; color:#fff; font-size:11px; font-weight:700;">URGENT</span>
                            </div>
                        </div>

                        <div style="text-align:right; min-width:160px;">
                            <div style="font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Type</div>
                            <div style="margin-top:4px;">
                                <span style="padding:4px 8px; border-radius:6px; background:#dc2626; color:#fff; font-size:12px; font-weight:700;">URGENT</span>
                            </div>
                        </div>
                    </div>

                    <div id="urgent-<?php echo e($outbound->id); ?>" class="card-body hidden">
                        <div style="font-weight:700; margin-bottom:12px; color:var(--text); font-size:14px;">Urgent Outbound Details</div>
                        <div style="display:grid; gap:8px;">
                            <div style="padding:12px; background:linear-gradient(135deg, #fef2f2, #fecaca); border-radius:8px;">
                                <div style="font-weight:600; color:#7f1d1d; margin-bottom:4px;"><?php echo e($outbound->stock?->description ?? 'Unknown Item'); ?></div>
                                <div style="display:flex; gap:16px; font-size:12px; color:#991b1b;">
                                    <span><strong>ID:</strong> <?php echo e($outbound->stock?->id_no ?? 'N/A'); ?></span>
                                    <span><strong>Unit:</strong> <?php echo e($outbound->stock?->unit ?? 'N/A'); ?></span>
                                    <span><strong>Quantity:</strong> <?php echo e($outbound->total); ?></span>
                                </div>
                                <?php if($outbound->received_by): ?>
                                    <div style="margin-top:8px; padding:6px 8px; background:#fff; border-radius:6px; border:1px solid #fca5a5;">
                                        <strong>Received By:</strong> <?php echo e($outbound->received_by); ?>

                                    </div>
                                <?php endif; ?>
                                <div style="margin-top:8px; padding:6px 8px; background:#fff; border-radius:6px; border:1px solid #fca5a5;">
                                    <strong>Recipient:</strong> <?php echo e($outbound->urgent_recipient_name); ?> <?php if($outbound->urgent_recipient_office): ?>(<?php echo e($outbound->urgent_recipient_office); ?>)<?php endif; ?>
                                </div>
                                
                                <?php if($outbound->reason): ?>
                                    
                                <?php endif; ?>
                                
                                
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <?php endif; ?>
            
            <hr style="margin:24px 0; border:none; border-top:1px solid #e2e8f0;">
        <?php endif; ?>

        <!-- Direct Requests Section -->
        <?php if(isset($directRequests) && $directRequests->count() > 0): ?>
            <h3 style="margin-bottom:12px; color:#10b981; font-size:16px;">Direct Requests</h3>
            <?php $__empty_1 = true; $__currentLoopData = $directRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $request): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="card" style="margin-bottom:14px; border-left:4px solid #10b981;">
                    <div class="card-head" onclick="toggleDirectRequest('direct-<?php echo e($request->id); ?>')">
                        <div>
                            <div class="card-title" style="color:#10b981;">Direct Request #<?php echo e($request->id); ?></div>
                            <div class="card-sub">Created on <?php echo e($request->created_at?->format('F j, Y, g:i A')); ?></div>
                            <div style="margin-top:8px; padding:6px 10px; background:linear-gradient(135deg, #d1fae5, #a7f3d0); border-radius:8px; border:1px solid #6ee7b7;">
                                <div style="font-size:12px; color:#047857; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;"><?php if($request->member): ?> Member <?php else: ?> Client <?php endif; ?></div>
                                <div style="font-size:14px; font-weight:800; color:#065f46; margin-top:2px;"><?php echo e($request->member?->name ?? $request->client?->name ?? 'Unknown'); ?></div>
                                <?php if($request->member): ?>
                                    <div style="font-size:12px; color:#047857; margin-top:4px;"><?php echo e($request->member?->email); ?></div>
                                <?php else: ?>
                                    <div style="font-size:12px; color:#047857; margin-top:4px;">Office: <?php echo e($request->office ?? 'Not specified'); ?></div>
                                <?php endif; ?>
                            </div>
                            <div style="margin-top:8px;">
                                <span style="padding:4px 8px; border-radius:6px; background:#10b981; color:#fff; font-size:11px; font-weight:700;">Direct request</span>
                            </div>
                        </div>

                        <div style="text-align:right; min-width:160px;">
                            <div style="font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Type</div>
                            <div style="margin-top:4px;">
                                <span style="padding:4px 8px; border-radius:6px; background:#10b981; color:#fff; font-size:12px; font-weight:700;">DIRECT</span>
                            </div>
                        </div>
                    </div>

                    <div id="direct-<?php echo e($request->id); ?>" class="card-body hidden">
                        <div style="font-weight:700; margin-bottom:12px; color:var(--text); font-size:14px;">Direct Request Details</div>
                        <div style="display:grid; gap:8px;">
                            <div style="padding:12px; background:linear-gradient(135deg, #d1fae5, #a7f3d0); border-radius:8px;">
                                <div style="font-weight:600; color:#065f46; margin-bottom:4px;"><?php echo e($request->stock?->description ?? 'Unknown Item'); ?></div>
                                <div style="display:flex; gap:16px; font-size:12px; color:#047857;">
                                    <span><strong>ID:</strong> <?php echo e($request->stock?->id_no ?? 'N/A'); ?></span>
                                    <span><strong>Unit:</strong> <?php echo e($request->stock?->unit ?? 'N/A'); ?></span>
                                    <span><strong>Quantity:</strong> <?php echo e($request->total); ?></span>
                                </div>
                                <?php if($request->received_by): ?>
                                    <div style="margin-top:8px; padding:6px 8px; background:#fff; border-radius:6px; border:1px solid #6ee7b7;">
                                        <strong>Received By:</strong> <?php echo e($request->received_by); ?>

                                    </div>
                                <?php endif; ?>
                                <div style="margin-top:8px; padding:6px 8px; background:#fff; border-radius:6px; border:1px solid #6ee7b7;">
                                    <strong>Requested by:</strong> <?php echo e($request->member?->name ?? $request->client?->name); ?> <?php if($request->member?->email): ?>(<?php echo e($request->member->email); ?>)<?php elseif($request->office): ?>(<?php echo e($request->office); ?>)<?php endif; ?>
                                </div>
                                
                                
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <?php endif; ?>
            
            <hr style="margin:24px 0; border:none; border-top:1px solid #e2e8f0;">
        <?php endif; ?>

        <!-- Stock Requests Section -->
        <?php if(($type ?? 'all') === 'all' || ($type ?? 'all') === 'request'): ?>
            <h3 style="margin-bottom:12px; color:#3b82f6; font-size:16px;">Stock Requests</h3>
            <?php $__empty_1 = true; $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="card status-<?php echo e($req->status); ?>" style="margin-bottom:14px;">
                    <div class="card-head" onclick="toggleReq('req-<?php echo e($req->id); ?>')">
                    <div>
                        <div class="card-title">Request #<?php echo e($req->id); ?> from <?php echo e($req->office ?? 'Unknown Office'); ?></div>
                        <div class="card-sub">Submitted on <?php echo e($req->created_at?->format('F j, Y, g:i A')); ?></div>
                        <?php
                            $totalQuantity = 0;
                            foreach($req->items as $item) {
                                $totalQuantity += $item->approved_qty;
                            }
                        ?>
                        <div style="margin-top:8px; padding:6px 10px; background:linear-gradient(135deg, #f0f9ff, #e0f2fe); border-radius:8px; border:1px solid #bae6fd;">
                            <div style="font-size:12px; color:#0369a1; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Total Quantity</div>
                            <div style="font-size:16px; font-weight:800; color:#0c4a6e; margin-top:2px;"><?php echo e($totalQuantity); ?></div>
                        </div>
                    </div>

                    <div style="text-align:right; min-width:160px;">
                        <div style="font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Current Status</div>
                        <div style="margin-top:4px;">
                            <span class="pill <?php echo e($req->status); ?>"><?php echo e(ucfirst(str_replace('_', ' ', $req->status))); ?></span>
                        </div>
                        <?php if($req->verification_code): ?>
                            <div style="font-size:11px; color:var(--muted); margin-top:6px; font-weight:500;">Verification: <span style="font-weight:700; color:var(--text);"><?php echo e($req->verification_code); ?></span></div>
                        <?php endif; ?>
                        <?php if($req->member): ?>
                            <div style="font-size:11px; color:var(--muted); margin-top:6px; font-weight:500;">Requested By: <span style="font-weight:700; color:var(--text);"><?php echo e($req->member->name); ?> (<?php echo e($req->member->email); ?>)</span></div>
                        <?php endif; ?>
                        <?php if($req->status === 'released'): ?>
                            
                            <?php if($req->received_by): ?>
                                <div style="font-size:11px; color:var(--muted); margin-top:6px; font-weight:500;">Received By: <span style="font-weight:700; color:var(--text);"><?php echo e($req->received_by); ?></span></div>
                            <?php else: ?>
                                <div style="font-size:11px; color:red; margin-top:6px; font-weight:500;">DEBUG: received_by is NULL for Request #<?php echo e($req->id); ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="req-<?php echo e($req->id); ?>" class="card-body hidden">
                    <div style="font-weight:700; margin-bottom:12px; color:var(--text); font-size:14px;">Requested Items</div>
                    <div style="display:grid; gap:8px;">
                        <?php $__currentLoopData = $req->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div style="padding:12px; background:linear-gradient(135deg, #f8fafc, #f1f5f9); border-radius:8px; border-left:3px solid var(--blue);">
                                <div style="font-weight:600; color:var(--text); margin-bottom:4px;"><?php echo e($item->stock?->description ?? 'Unknown Item'); ?></div>
                                <div style="display:flex; gap:16px; font-size:12px; color:var(--muted);">
                                    <span><strong>Unit:</strong> <?php echo e($item->stock?->unit ?? 'N/A'); ?></span>
                                                                        <span><strong>Requested:</strong> <?php echo e($item->requested_qty); ?></span>
                                    <span><strong>Approved:</strong> <?php echo e($item->approved_qty); ?></span>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="card">
                <div class="card-body">
                    <div style="text-align:center; padding:20px;">
                        <div style="font-size:24px; margin-bottom:12px; color:var(--muted);">ð</div>
                        <div class="muted" style="font-size:14px;">No transaction history available. Submit a request to see your transactions here.</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        function toggleReq(id){
            const el = document.getElementById(id);
            if(!el) return;
            el.classList.toggle('hidden');
        }

        function toggleDeduction(id){
            const el = document.getElementById(id);
            if(!el) return;
            el.classList.toggle('hidden');
        }

        function toggleUrgentOutbound(id){
            const el = document.getElementById(id);
            if(!el) return;
            el.classList.toggle('hidden');
        }
        
        function toggleDirectRequest(id){
            const el = document.getElementById(id);
            if(!el) return;
            el.classList.toggle('hidden');
        }

        function toggleDistribution(id){
            const el = document.getElementById(id);
            if(!el) return;
            el.classList.toggle('hidden');
        }

        // Auto-update filters without needing a button.
        (function(){
            const form = document.getElementById('filterForm');

            if (!form) return;

            const submit = () => {
                const params = new URLSearchParams();
                
                // Get selected radio button
                const typeRadio = document.querySelector('input[name="type"]:checked');
                const type = typeRadio ? typeRadio.value : 'all';

                // Get selected member
                const memberSelect = document.getElementById('filterMember');
                const member = memberSelect ? memberSelect.value : '';

                if (type && type !== 'all') params.set('type', type);
                if (member) params.set('member', member);

                const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                window.location.href = url;
            };
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\wamp64\www\inventory-system\resources\views/client/summary.blade.php ENDPATH**/ ?>