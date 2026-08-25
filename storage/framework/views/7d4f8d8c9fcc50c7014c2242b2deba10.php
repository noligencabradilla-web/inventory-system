<?php
  $brand = 'Inventory System';
  $pageTitle = 'My Inventory';
?>

<?php $__env->startSection('sidebar'); ?>
    <?php echo $__env->make('client.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div style="margin-top:16px;">
        <!-- Search Bar -->
        <div style="margin-bottom:20px;">
            <div style="position:relative;">
                <input type="text" id="inventorySearch" placeholder="Search items by ID, description, or unit..." 
                       style="width:100%; padding:12px 16px 12px 44px; border:2px solid #e2e8f0; border-radius:12px; font-size:14px; background:#ffffff; transition:all 0.3s ease;"
                       oninput="searchInventory(this.value)">
                <span style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#64748b; font-size:16px;">ð</span>
            </div>
            <div id="searchResults" style="margin-top:8px; font-size:12px; color:#64748b;"></div>
        </div>

        <?php if($approvedInventory->count() > 0): ?>
            <div style="margin-top:20px; overflow-x:auto; border-radius:16px; box-shadow:0 8px 25px rgba(59,130,246,0.15); background:linear-gradient(135deg, #eff6ff, #dbeafe);">
                <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:linear-gradient(135deg, #3b82f6, #1d4ed8);">
                        <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Item</th>
                        <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Received</th>
                        <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">My Inventory</th>
                        <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#374151; font-size:12px; background:#f8fafc;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $approvedInventory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $myInventory = isset($item->my_inventory) ? $item->my_inventory : max(0, ($item->approved_qty ?? 0) - ($item->distributed_qty ?? 0));
                        ?>
                        <tr style="border-bottom:1px solid #e0e7ff; background:linear-gradient(135deg, #ffffff, #f8fafc);">
                            <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff;">
                                <div style="font-weight:700; color:#1e40af; font-size:14px;"><?php echo e($item->stock->id_no); ?></div>
                                <div style="color:#64748b; font-size:11px; margin-top:3px;"><?php echo e($item->stock->description ?? $item->stock->name ?? 'Item'); ?></div>
                                <?php if(isset($item->type) && $item->type === 'urgent'): ?>
                                    <div style="margin-top:4px; padding:2px 6px; background:#dc2626; color:#fff; font-size:10px; font-weight:700; border-radius:4px; display:inline-block;">URGENT REQUEST</div>
                                <?php endif; ?>
                            </td>
                            <td style="padding:14px 10px; text-align:center; border-bottom:1px solid #e0e7ff; color:#1e40af; font-weight:700;"><?php echo e($item->approved_qty ?? 0); ?></td>
                            <td style="padding:14px 10px; text-align:center; border-bottom:1px solid #e0e7ff; color:#059669; font-weight:700; background:linear-gradient(135deg, #ecfdf5, #d1fae5);"><?php echo e($myInventory); ?></td>
                            <td style="padding:14px 10px; text-align:center; border-bottom:1px solid #e0e7ff;">
                                <?php if(isset($item->type) && $item->type === 'urgent'): ?>
                                    <span style="color:#64748b; font-size:13px;">Direct distribution</span>
                                <?php elseif($myInventory > 0): ?>
                                    <button 
                                        type="button" 
                                        onclick="openDeductModal('<?php echo e($item->id); ?>', '<?php echo e($item->stock->id_no); ?>', '<?php echo e($item->stock->description ?? $item->stock->name ?? 'Item'); ?>', <?php echo e($myInventory); ?>)"
                                        style="padding:8px 16px; border:1px solid #dc2626; background:#dc2626; color:#fff; border-radius:8px; font-weight:700; cursor:pointer; transition:all 0.3s ease; font-size:13px;"
                                    >
                                        Deduct
                                    </button>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-size:13px;">No stock</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div style="text-align:center; padding:48px; color:#64748b; background:linear-gradient(135deg, #f0f9ff, #e0f2fe); border-radius:16px; border:2px dashed #3b82f6;">
                <div style="font-size:48px; margin-bottom:16px; color:#3b82f6;">📦</div>
                <div style="font-size:18px; font-weight:600; color:#1e40af;">No approved items are available yet.</div>
            </div>
        <?php endif; ?>
    </div>

    <?php
        $inventoryData = $approvedInventory->map(function($item) {
            return [
                'id_no' => $item->stock->id_no,
                'description' => $item->stock->description ?? $item->stock->name ?? 'Item',
                'unit' => $item->stock->unit,
                'approved_qty' => $item->approved_qty,
                'my_inventory' => $item->my_inventory,
                'type' => $item->type ?? 'inventory'
            ];
        });
    ?>
    <script>
        // Store inventory data for search
        const inventoryData = <?php echo json_encode($inventoryData); ?>;

        function searchInventory(searchTerm) {
            const searchResults = document.getElementById('searchResults');
            const tableRows = document.querySelectorAll('tbody tr');
            
            if (!searchTerm.trim()) {
                // Show all items
                tableRows.forEach(row => row.style.display = '');
                searchResults.textContent = '';
                return;
            }

            const term = searchTerm.toLowerCase();
            let visibleCount = 0;

            tableRows.forEach(row => {
                const idNo = row.querySelector('td:first-child div:first-child')?.textContent || '';
                const description = row.querySelector('td:first-child div:last-child')?.textContent || '';
                
                const matches = idNo.toLowerCase().includes(term) ||
                               description.toLowerCase().includes(term);
                
                if (matches) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Update results count
            searchResults.textContent = `Found ${visibleCount} item${visibleCount !== 1 ? 's' : ''}`;
        }

        function openDeductModal(itemId, idNo, description, availableQty) {
            document.getElementById('deductItemId').value = itemId;
            document.getElementById('deductIdNo').textContent = idNo;
            document.getElementById('deductDescription').textContent = description;
            document.getElementById('deductAvailableQty').textContent = availableQty;
            document.getElementById('deductQty').max = availableQty;
            document.getElementById('deductQty').value = 1;
            document.getElementById('deductModal').style.display = 'flex';
        }

        function closeDeductModal() {
            document.getElementById('deductModal').style.display = 'none';
        }
    </script>

    <!-- Client Deduction Modal -->
    <div id="deductModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:5000; align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:14px; box-shadow:0 10px 40px rgba(0,0,0,.25); max-width:500px; width:90%; padding:24px;">
            <h3 style="margin:0 0 8px 0; font-size:18px; color:#0f172a; font-weight:800;">Deduct Item</h3>
            <p style="margin:0 0 20px 0; color:#475569; font-size:14px; line-height:1.5;">Deduct inventory and assign to members who used this item.</p>
            
            <form method="POST" action="<?php echo e(route('client.inventory.deduct')); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" id="deductItemId" name="stock_request_item_id">
                
                <div style="margin-bottom:16px;">
                    <label style="display:block; margin-bottom:4px; color:#0f172a; font-weight:600; font-size:13px;">Item Details</label>
                    <div style="padding:10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
                        <div style="font-weight:700; color:#1e40af; font-size:14px;" id="deductIdNo"></div>
                        <div style="color:#64748b; font-size:12px; margin-top:2px;" id="deductDescription"></div>
                        <div style="color:#059669; font-size:12px; margin-top:4px;">Available: <span id="deductAvailableQty"></span></div>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label for="deductQty" style="display:block; margin-bottom:4px; color:#0f172a; font-weight:600; font-size:13px;">Quantity to Deduct *</label>
                    <input type="number" id="deductQty" name="deducted_qty" min="1" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; background:#fff;">
                </div>

                <div style="margin-bottom:16px;">
                    <label for="deductMember" style="display:block; margin-bottom:4px; color:#0f172a; font-weight:600; font-size:13px;">Member(s) Who Used This Item *</label>
                    <select id="deductMember" name="member_id" required style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; background:#fff;">
                        <option value="">-- Select Member --</option>
                        <?php if(auth()->user()->role === 'client'): ?>
                            <?php
                                $clientMembers = \App\Models\ClientMember::where('client_id', auth()->id())->get();
                            ?>
                            <?php $__currentLoopData = $clientMembers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($member->id); ?>"><?php echo e($member->name); ?> (<?php echo e($member->email); ?>)</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div style="margin-bottom:16px;">
                    <label for="deductReason" style="display:block; margin-bottom:4px; color:#0f172a; font-weight:600; font-size:13px;">Reason for Deduction (Optional)</label>
                    <textarea id="deductReason" name="reason" placeholder="Explain why this item was deducted..." style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; background:#fff; min-height:60px; resize:vertical;"></textarea>
                </div>
                
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" onclick="closeDeductModal()" style="padding:10px 16px; border-radius:8px; border:none; background:#e2e8f0; color:#0f172a; font-weight:700; cursor:pointer;">Cancel</button>
                    <button type="submit" style="padding:10px 16px; border-radius:8px; border:none; background:#dc2626; color:#fff; font-weight:700; cursor:pointer;">Deduct Item</button>
                </div>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\wamp64\www\inventory-system\resources\views/client/inventory.blade.php ENDPATH**/ ?>