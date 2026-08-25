<?php
  $brand = 'Inventory System';
  $pageTitle = 'Stocks';
  $pageSubtitle = 'Manage all available stocks.';
?>

<?php $__env->startSection('sidebar'); ?>
    <?php echo $__env->make('partials.admin-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<style>
    .toolbar{ display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px; }
    .btn-link{
        display:inline-block;
        padding:10px 12px;
        border-radius:10px;
        border:1px solid var(--blue);
        background: var(--blue-soft);
        color: var(--blue);
        text-decoration:none;
        font-weight:700;
        transition: all 0.3s ease;
    }
    .btn-link:hover{ 
        background: rgba(37,99,235,.18);
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(37,99,235,.15);
    }
    .btn-link:active{
        transform: translateY(0);
    }

    .table-wrap{ overflow:auto; border:1px solid var(--line); border-radius:14px; }
    table{ width:100%; border-collapse:collapse; min-width: 980px; background:#fff; }
    th,td{ border:1px solid var(--line); padding:10px; text-align:left; }
    th{ background: rgba(37,99,235,.06); color: var(--text); font-weight:700; }
    td{ color: var(--text); }

    .muted{ color: var(--muted); }

    .pill{
        display:inline-block; padding:4px 10px; border-radius:999px;
        border:1px solid var(--line);
        background: rgba(37,99,235,.06);
        color: var(--blue);
        font-size:12px; font-weight:700;
    }
    .pill.orange{
        background: rgba(249,115,22,.10);
        color: var(--orange);
        border-color: rgba(249,115,22,.35);
    }
    .pill.green{
        background: rgba(16,163,82,.08);
        color: var(--success);
        border-color: rgba(16,163,82,.28);
    }
    .pill.red{
        background: rgba(239,68,68,.08);
        color: var(--danger);
        border-color: rgba(239,68,68,.28);
    }

    /* Mobile responsive styles */
    @media (max-width: 768px) {
        .toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .toolbar > div:first-child {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .toolbar > div:first-child input,
        .toolbar > div:first-child select {
            width: 100%;
            min-width: unset;
        }

        .toolbar .btn-link {
            width: 100%;
            text-align: center;
        }

        table {
            min-width: unset;
            font-size: 13px;
        }

        th, td {
            padding: 8px;
        }

        /* Hide less critical columns on mobile */
        th:nth-child(2),
        td:nth-child(2) {
            display: none;
        }

        .pill {
            font-size: 11px;
            padding: 3px 8px;
        }
    }

    @media (max-width: 480px) {
        th, td {
            padding: 6px;
            font-size: 12px;
        }

        .btn-link {
            padding: 8px 10px;
            font-size: 12px;
        }

        /* On very small screens, simplify the modal */
        #editStockModal > div {
            width: 90% !important;
            max-width: 100%;
            margin: 0 auto;
        }
    }
    
    /* Add New Stock button hover effects */
    .btn-link:hover{ 
        background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;
        color: #ffffff !important;
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 20px rgba(59,130,246,0.3) !important;
        border-color: rgba(59,130,246,0.5) !important;
    }
    .btn-link:hover::after{ left:100% !important; }
    .btn-link:active{
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 8px rgba(59,130,246,0.2) !important;
    }
    
    /* Stock modal button hover effects */
    .modal-btn-primary:hover{
        background: linear-gradient(135deg, #2563eb, #1e40af) !important;
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 20px rgba(59,130,246,0.3) !important;
        border-color: rgba(59,130,246,0.5) !important;
    }
    .modal-btn-primary:hover::after{ left:100% !important; }
    .modal-btn-primary:active{
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 8px rgba(59,130,246,0.2) !important;
    }
    
    .modal-btn-secondary:hover{
        background: linear-gradient(135deg, #f8fafc, #f1f5f9) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 16px rgba(59,130,246,0.15) !important;
        border-color: rgba(59,130,246,0.3) !important;
        color: #374151 !important;
    }
    .modal-btn-secondary:hover::after{ left:100% !important; }
    .modal-btn-secondary:active{
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 4px rgba(59,130,246,0.1) !important;
    }
</style>

<div class="toolbar">
    <div style="display:flex; gap:12px; align-items:center;">
        <h2 style="margin:0;">Stocks</h2>
        <?php
            $filterCategories = $allCategories->pluck('name')->unique()->values();
        ?>

        <input id="stocksSearch" type="search" placeholder="Search ID, description, category..." style="padding:8px 10px;border:1px solid var(--line);border-radius:8px;min-width:260px;">

        <select id="filterCategory" style="padding:8px 10px;border:1px solid var(--line);border-radius:8px;">
            <option value="">All categories</option>
            <?php $__currentLoopData = $filterCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e(strtolower($c)); ?>"><?php echo e($c); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>

        <select id="filterAvailability" style="padding:8px 10px;border:1px solid var(--line);border-radius:8px;">
            <option value="">All</option>
            <option value="in">In stock (green)</option>
            <option value="low">Low stock (orange / red)</option>
            <option value="out">Out of stock (red)</option>
        </select>
    </div>

    <a class="btn-link" href="#" onclick="openStockModal()">Add New Item</a>
</div>

<div style="overflow-x:auto; border-radius:16px; box-shadow:0 8px 25px rgba(59,130,246,0.15); background:linear-gradient(135deg, #eff6ff, #dbeafe);">
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="background:linear-gradient(135deg, #3b82f6, #1d4ed8);">
                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px; min-width:110px;">ID No</th>
                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px; min-width:220px;">Description</th>
                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px; min-width:200px;">Category</th>
                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px; min-width:80px;">Unit</th>
                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px; min-width:90px;">Stock</th>
                <th style="padding:12px 10px; text-align:left; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px; min-width:80px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $stocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $cat = $s->category?->name ?? $s->category_name ?? '';
                    $desc = $s->description ?? $s->name ?? '';
                    $stockVal = $s->stock ?? 0;
                ?>
                <tr style="border-bottom:1px solid #e0e7ff; background:linear-gradient(135deg, #ffffff, #f8fafc);" data-stock-id="<?php echo e($s->id); ?>" data-id="<?php echo e(strtolower($s->id_no ?? $s->id)); ?>" data-desc="<?php echo e(strtolower($desc)); ?>" data-cat="<?php echo e(strtolower($cat)); ?>" data-stock="<?php echo e($stockVal); ?>">
                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff;">
                        <div style="font-weight:700; color:#1e40af; font-size:14px;"><?php echo e($s->id_no ?? $s->id); ?></div>
                    </td>
                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff;">
                        <div style="color:#64748b; font-size:14px;"><?php echo e($desc ?: 'â'); ?></div>
                    </td>
                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; color:#64748b; font-size:14px;"><?php echo e($cat ?: 'Unknown'); ?></td>
                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; color:#475569; font-weight:600;"><?php echo e($s->unit ?? 'â'); ?></td>
                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff;">
                        <?php if($stockVal >= 50): ?>
                            <span style="padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; border:1px solid #bbf7d0; background:#ecfdf5; color:#059669;"><?php echo e($stockVal); ?> available</span>
                        <?php elseif($stockVal > 0 && $stockVal <= 49): ?>
                            <span style="padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; border:1px solid #fed7aa; background:#fff7ed; color:#ea580c;"><?php echo e($stockVal); ?> available</span>
                        <?php else: ?>
                            <span style="padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; border:1px solid #fecaca; background:#fef2f2; color:#dc2626;">Out of stock</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff;">
                        <button type="button" style="padding:8px 12px; border-radius:8px; border:1px solid #3b82f6; background:#3b82f6; color:#ffffff; font-weight:700; cursor:pointer; transition:all 0.3s ease;" onclick="openEditModal(<?php echo e($s->id); ?>)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr style="background:linear-gradient(135deg, #f8fafc, #f1f5f9);">
                    <td colspan="6" style="padding:20px 10px; text-align:center; color:#64748b; font-size:14px;">No stocks found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Edit Stock Modal -->
<div id="editStockModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); z-index:10000; align-items:center; justify-content:center; padding:20px;">
    <div style="background:linear-gradient(135deg, #ffffff, #f8fafc); border-radius:16px; padding:24px; width:520px; max-width:95%; box-shadow:0 20px 50px rgba(59,130,246,0.25); border:1px solid #e2e8f0; animation:modalSlideIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
        <!-- Modal Header -->
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid #e2e8f0;">
            <div style="width:40px; height:40px; background:linear-gradient(135deg, #3b82f6, #1d4ed8); border-radius:10px; display:flex; align-items:center; justify-content:center;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
            </div>
            <div style="flex:1;">
                <h3 style="margin:0; font-size:18px; font-weight:700; color:#1e293b;">Edit Stock</h3>
                <div style="color:#64748b; font-size:13px; margin-top:2px;">Update stock information</div>
            </div>
            <button type="button" onclick="closeEditModal()" style="width:32px; height:32px; border-radius:8px; border:1px solid #e2e8f0; background:#ffffff; color:#64748b; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.2s ease;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <div style="display:flex; flex-direction:column; gap:16px; margin-bottom:20px;">
            <div class="field">
                <label style="display:block; margin-bottom:6px; font-weight:600; color:#374151; font-size:14px;">Description</label>
                <input id="editDescriptionInput" placeholder="Enter stock description" style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; transition:all 0.3s ease; background:#ffffff;">
            </div>

            <div class="field">
                <label style="display:block; margin-bottom:6px; font-weight:600; color:#374151; font-size:14px;">Unit</label>
                <input id="editUnitInput" placeholder="e.g., pcs, kg, liters" style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; transition:all 0.3s ease; background:#ffffff;">
            </div>

            <div class="field">
                <label style="display:block; margin-bottom:6px; font-weight:600; color:#374151; font-size:14px;">Category</label>
                <select id="editCategorySelect" style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; transition:all 0.3s ease; background:#ffffff; cursor:pointer;">
                    <option value="">Select a category</option>
                    <?php $__currentLoopData = $allCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $catOpt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($catOpt->id); ?>"><?php echo e($catOpt->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div id="editFeedback" style="padding:8px 12px; border-radius:8px; font-size:13px; display:none;"></div>
        </div>

        <!-- Modal Footer -->
        <div style="display:flex; gap:12px; justify-content:flex-end;">
            <button type="button" onclick="closeEditModal()" style="padding:10px 20px; border-radius:10px; border:2px solid #e2e8f0; background:#ffffff; color:#64748b; font-weight:600; cursor:pointer; transition:all 0.3s ease; font-size:14px;">Cancel</button>
            <button type="button" onclick="saveEditStock()" style="padding:10px 20px; border-radius:10px; border:2px solid #3b82f6; background:linear-gradient(135deg, #3b82f6, #1d4ed8); color:#ffffff; font-weight:700; cursor:pointer; transition:all 0.3s ease; font-size:14px; box-shadow:0 4px 12px rgba(59,130,246,0.3);">Save Changes</button>
        </div>
    </div>
</div>

<!-- Add modal animation -->
<style>
@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

#editStockModal input:focus,
#editStockModal select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}

#editStockModal input:hover,
#editStockModal select:hover {
    border-color: #cbd5e1;
}

#editStockModal button:hover {
    transform: translateY(-1px);
}

#editStockModal button:active {
    transform: translateY(0);
}

#editFeedback.success {
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    color: #059669;
    border: 1px solid #bbf7d0;
    display: block;
}

#editFeedback.error {
    background: linear-gradient(135deg, #fef2f2, #fecaca);
    color: #dc2626;
    border: 1px solid #fecaca;
    display: block;
}
</style>

<script>
const CSRF_TOKEN = '<?php echo e(csrf_token()); ?>';
let _editStockId = null;

function openEditModal(stockId){
    _editStockId = stockId;
    const modal = document.getElementById('editStockModal');
    if (!modal) {
        console.error('editStockModal element not found on page');
        alert('Edit modal is not loaded — please refresh the page.');
        return;
    }

    // Populate fields from the row
    const row = document.querySelector(`tr[data-stock-id='${stockId}']`);
    if (row) {
        const cells = row.querySelectorAll('td');
        const desc = cells[1]?.textContent?.trim() || '';
        const category = cells[2]?.textContent?.trim() || '';
        const unit = cells[3]?.textContent?.trim() || '';

        document.getElementById('editDescriptionInput').value = desc;
        document.getElementById('editUnitInput').value = unit;

        // Select category
        const select = document.getElementById('editCategorySelect');
        select.value = '';
        if (category && category !== 'Unknown') {
            const wanted = category.toLowerCase();
            for (const opt of Array.from(select.options)) {
                if (opt.text.toLowerCase() === wanted) {
                    select.value = opt.value;
                    break;
                }
            }
        }

        console.log('Populated fields:', { desc, unit, category });
    } else {
        console.error('Row not found for stockId:', stockId);
    }

    modal.style.display = 'flex';
}

function closeEditModal(){
    _editStockId = null;
    document.getElementById('editStockModal').style.display = 'none';
}

async function saveEditStock(){
    if (!_editStockId) return;
    const payload = new FormData();
    payload.append('description', document.getElementById('editDescriptionInput').value.trim());
    payload.append('unit', document.getElementById('editUnitInput').value.trim());
    const categoryId = document.getElementById('editCategorySelect').value;
    if (categoryId) payload.append('category_id', categoryId);
    payload.append('_token', CSRF_TOKEN);
    payload.append('_method', 'PUT');

    try {
        const res = await fetch(`/admin/stocks/${_editStockId}/edit-modal`, {
            method: 'POST',
            body: payload,
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
        });
        const data = await res.json();
        if (res.ok && data.success) {
            // Update the row
            const row = document.querySelector(`tr[data-stock-id='${_editStockId}']`);
            if (row) {
                const cells = row.querySelectorAll('td');
                cells[1].textContent = data.stock.description || '—';
                cells[2].textContent = data.stock.category?.name || 'Unknown';
                cells[3].textContent = data.stock.unit || '—';
                row.dataset.desc = (data.stock.description || '').toLowerCase();
                row.dataset.cat = (data.stock.category?.name || '').toLowerCase();
            }
            closeEditModal();
        } else {
            document.getElementById('editFeedback').textContent = data.message || 'Unable to update stock.';
        }
    } catch (err) {
        document.getElementById('editFeedback').textContent = 'Request failed.';
    }
}

function number_format(number, decimals) {
    return parseFloat(number).toFixed(decimals);
}

const stocksSearch = document.getElementById('stocksSearch');
const filterCategory = document.getElementById('filterCategory');
const filterAvailability = document.getElementById('filterAvailability');

function filterStocks(){
    const q = stocksSearch.value.trim().toLowerCase();
    const cat = filterCategory.value;
    const avail = filterAvailability.value; // "in" or "out" or ""

    document.querySelectorAll('table tbody tr[data-id]').forEach(row => {
        const id = row.dataset.id || '';
        const desc = row.dataset.desc || '';
        const category = row.dataset.cat || '';
        const stock = parseInt(row.dataset.stock || '0', 10);

        let visible = true;

        if(q){
            visible = id.includes(q) || desc.includes(q) || category.includes(q);
        }

        if(visible && cat){
            visible = category === cat;
        }

        if(visible && avail){
            // Availability ranges match the colored badges:
            // green: >=50, low: 1-49, out: 0
            if(avail === 'in') visible = stock >= 50; // green (ample)
            if(avail === 'low') visible = stock > 0 && stock <= 49; // low stock (orange)
            if(avail === 'out') visible = stock <= 0; // out of stock
        }

        row.style.display = visible ? '' : 'none';
    });
}

stocksSearch && stocksSearch.addEventListener('input', filterStocks);
filterCategory && filterCategory.addEventListener('change', filterStocks);
filterAvailability && filterAvailability.addEventListener('change', filterStocks);
</script>

<!-- Add Stock Modal -->
<div id="stockModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,.5); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:linear-gradient(135deg, #ffffff, #f8fafc); border-radius:16px; padding:24px; width:520px; max-width:95%; box-shadow:0 18px 40px rgba(59,130,246,0.15); border:1px solid rgba(59,130,246,0.1);">
        <h3 style="margin:0 0 20px 0; font-size:18px; font-weight:800; color:#000000; text-align:center;">Add New Item</h3>
        
        <?php if($errors->any()): ?>
            <div style="color: var(--red); margin-bottom:16px; padding:12px; background: rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.3); border-radius:8px;">
                <ul style="margin:0; padding-left:20px;">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> <li style="margin:4px 0;"><?php echo e($error); ?></li> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?php echo e(route('stocks.store')); ?>" method="POST" id="stockForm">
            <?php echo csrf_field(); ?>
            
            <div style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:8px; color: #000000; font-weight:700; font-size:14px;">Category:</label>
                <select name="category_id" id="stock_category_id" required style="width:100%; padding:12px 14px; border:2px solid #dbeafe; border-radius:10px; font-size:14px; color:#000000; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(59,130,246,.05); outline:none;">
                    <option value="">-- Choose a category --</option>
                    <?php $__currentLoopData = $allCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($category->id); ?>" data-code="<?php echo e($category->code ?? ''); ?>"><?php echo e($category->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:8px; color: #000000; font-weight:700; font-size:14px;">ID No:</label>
                <input type="text" name="id_no" id="stock_id_no" value="<?php echo e(old('id_no')); ?>" required readonly style="width:100%; padding:12px 14px; border:2px solid #dbeafe; border-radius:10px; font-size:14px; color:#000000; background:#eff6ff; cursor:not-allowed; box-shadow:0 1px 3px rgba(59,130,246,.05);">
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:8px; color: #000000; font-weight:700; font-size:14px;">Description:</label>
                <input type="text" name="description" id="stock_description" value="<?php echo e(old('description')); ?>" required style="width:100%; padding:12px 14px; border:2px solid #dbeafe; border-radius:10px; font-size:14px; color:#000000; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(59,130,246,.05); outline:none;">
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:8px; color: #000000; font-weight:700; font-size:14px;">Unit:</label>
                <input type="text" name="unit" id="stock_unit" value="<?php echo e(old('unit')); ?>" required style="width:100%; padding:12px 14px; border:2px solid #dbeafe; border-radius:10px; font-size:14px; color:#000000; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(59,130,246,.05); outline:none;">
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:8px; color: #000000; font-weight:700; font-size:14px;">Stock:</label>
                <input type="number" name="stock" id="stock_stock" value="<?php echo e(old('stock', 0)); ?>" min="0" required style="width:100%; padding:12px 14px; border:2px solid #dbeafe; border-radius:10px; font-size:14px; color:#000000; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(59,130,246,.05); outline:none;">
            </div>

            <div style="margin-bottom:20px;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="hidden" id="stock_hidden" value="1" <?php echo e(old('hidden') ? 'checked' : ''); ?> style="width:18px; height:18px; cursor:pointer; accent-color:#3b82f6;">
                    <label for="stock_hidden" style="margin:0; font-weight:500; cursor:pointer; color: #000000;">Hidden (Admin Only)</label>
                </div>
            </div>
            
            <div style="display:flex; gap:12px;">
                <button type="submit" class="modal-btn-primary" style="flex:1; padding:12px 20px; border-radius:10px; border:2px solid #3b82f6; background:linear-gradient(135deg, #3b82f6, #1d4ed8); color:#ffffff; font-weight:700; transition:all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow:0 4px 12px rgba(59,130,246,0.2); position:relative; overflow:hidden; transform:translateY(0); pointer-events:auto;">
                    <span style="position:relative; z-index:1;">Add Stock</span>
                    <span style="position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg, transparent, rgba(255,255,255,0.2)); transition:left 0.3s ease;"></span>
                </button>
                <button type="button" onclick="closeStockModal()" class="modal-btn-secondary" style="flex:1; padding:12px 20px; border-radius:10px; border:2px solid #e2e8f0; background:#ffffff; color:#64748b; font-weight:600; transition:all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow:0 1px 3px rgba(15,23,42,.05); position:relative; overflow:hidden; transform:translateY(0); pointer-events:auto;">
                    <span style="position:relative; z-index:1;">Cancel</span>
                    <span style="position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg, transparent, rgba(59,130,246,0.1)); transition:left 0.3s ease;"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openStockModal() {
    document.getElementById('stockModal').style.display = 'flex';
    document.getElementById('stock_category_id').focus();
}

function closeStockModal() {
    document.getElementById('stockModal').style.display = 'none';
    document.getElementById('stockForm').reset();
    // Clear any existing error messages
    const existingErrors = document.querySelector('.error-message');
    if (existingErrors) {
        existingErrors.remove();
    }
}

// Close modal when clicking outside
document.getElementById('stockModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeStockModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('stockModal').style.display === 'flex') {
        closeStockModal();
    }
});

// Handle form submission via AJAX
document.addEventListener('DOMContentLoaded', function() {
    const stockForm = document.getElementById('stockForm');
    
    stockForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Remove any existing error messages
        const existingErrors = document.querySelectorAll('.error-message');
        existingErrors.forEach(error => error.remove());
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const successDiv = document.createElement('div');
                successDiv.className = 'success-message';
                successDiv.style.cssText = 'color: #166534; margin-bottom:16px; padding:12px; background: rgba(34,197,94,0.1); border:1px solid rgba(34,197,94,0.3); border-radius:8px;';
                successDiv.textContent = data.success;
                
                // Insert after the h3 title
                const title = document.querySelector('#stockModal h3');
                title.parentNode.insertBefore(successDiv, title.nextSibling);
                
                // Close modal and reload after 1.5 seconds
                setTimeout(() => {
                    closeStockModal();
                    location.reload();
                }, 1500);
            } else if (data.errors) {
                // Show validation errors
                let errorHtml = '<div class="error-message" style="color: var(--red); margin-bottom:16px; padding:12px; background: rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-radius:8px;"><ul style="margin:0; padding-left:20px;">';
                Object.values(data.errors).flat().forEach(error => {
                    errorHtml += '<li style="margin:4px 0;">' + error + '</li>';
                });
                errorHtml += '</ul></div>';
                
                // Insert after the h3 title
                const title = document.querySelector('#stockModal h3');
                title.insertAdjacentHTML('afterend', errorHtml);
            }
        })
        .catch(error => {
            console.error('Error submitting form:', error);
            // Show generic error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.style.cssText = 'color: var(--red); margin-bottom:16px; padding:12px; background: rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-radius:8px;';
            errorDiv.textContent = 'An error occurred. Please try again.';
            
            const title = document.querySelector('#stockModal h3');
            title.parentNode.insertBefore(errorDiv, title.nextSibling);
        });
    });

    // Category change event for auto-generating ID
    const categorySelect = document.getElementById('stock_category_id');
    const idNoInput = document.getElementById('stock_id_no');

    categorySelect.addEventListener('change', async function() {
        const categoryId = this.value;
        
        if (!categoryId) {
            idNoInput.value = '';
            return;
        }

        try {
            const response = await fetch(`/admin/stocks/generate-id/${categoryId}`);
            const data = await response.json();
            
            if (response.ok && data.id_no) {
                idNoInput.value = data.id_no;
            } else {
                idNoInput.value = '';
                alert(data.error || 'Error generating ID');
            }
        } catch (error) {
            console.error('Error generating ID:', error);
            idNoInput.value = '';
        }
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\wamp64\www\inventory-system\resources\views/admin/stocks/index.blade.php ENDPATH**/ ?>