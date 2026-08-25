<?php
    $brand = 'Inventory System';
    $pageTitle = 'My Allocations';

    $categoryOptions = $allocations
        ->map(fn ($allocation) => $allocation->stock?->category?->name ?? 'Uncategorized')
        ->unique()
        ->sort()
        ->values();
?>

<?php $__env->startSection('sidebar'); ?>
    <?php echo $__env->make('client.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<style>
    .allocation-header {
        margin-bottom: 18px;
    }

    .allocation-title {
        margin: 0;
        color: #0f172a;
        font-size: 26px;
        font-weight: 800;
    }

    .allocation-subtitle {
        margin-top: 4px;
        color: #64748b;
        font-size: 14px;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 18px;
    }

    .summary-card {
        padding: 18px;
        border: 1px solid #bfdbfe;
        border-radius: 16px;
        background: rgba(255, 255, 255, .92);
        box-shadow: 0 8px 20px rgba(15, 23, 42, .05);
    }

    .summary-label {
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .summary-value {
        margin-top: 10px;
        color: #0f172a;
        font-size: 28px;
        font-weight: 900;
    }

    .filter-card {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 12px;
        align-items: end;
        padding: 16px;
        margin-bottom: 18px;
        border: 1px solid #dbeafe;
        border-radius: 16px;
        background: rgba(255, 255, 255, .94);
        box-shadow: 0 8px 20px rgba(15, 23, 42, .04);
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .filter-label {
        color: #475569;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .filter-input,
    .filter-select {
        width: 100%;
        padding: 11px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        background: #fff;
        color: #0f172a;
        font-size: 14px;
        outline: none;
    }

    .filter-input:focus,
    .filter-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
    }

    .clear-btn {
        padding: 11px 16px;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        background: #fff;
        color: #0f172a;
        font-weight: 800;
        cursor: pointer;
    }

    .clear-btn:hover {
        background: #f8fafc;
    }

    .allocation-card {
        border: 1px solid #dbeafe;
        border-radius: 18px;
        overflow: hidden;
        background: rgba(255, 255, 255, .94);
        box-shadow: 0 12px 30px rgba(15, 23, 42, .07);
    }

    .allocation-table {
        width: 100%;
        border-collapse: collapse;
    }

    .allocation-table th {
        padding: 14px 12px;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: #fff;
        font-size: 13px;
        text-align: left;
        white-space: nowrap;
    }

    .allocation-table td {
        padding: 14px 12px;
        border-bottom: 1px solid #e0e7ff;
        color: #334155;
        font-size: 14px;
        vertical-align: middle;
    }

    .allocation-table tr:last-child td {
        border-bottom: none;
    }

    .allocation-table tbody tr:hover {
        background: rgba(239, 246, 255, .85);
    }

    .stock-id {
        color: #1d4ed8;
        font-weight: 900;
    }

    .pill {
        display: inline-flex;
        align-items: center;
        padding: 5px 11px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
    }

    .pill-green {
        border: 1px solid #bbf7d0;
        background: #ecfdf5;
        color: #059669;
    }

    .pill-orange {
        border: 1px solid #fed7aa;
        background: #fff7ed;
        color: #ea580c;
    }

    .empty-state {
        padding: 50px 20px;
        color: #64748b;
        text-align: center;
        font-size: 14px;
        font-weight: 700;
    }

    .hidden-row {
        display: none;
    }

    @media (max-width: 1100px) {
        .filter-card {
            grid-template-columns: 1fr 1fr;
        }

        .summary-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 760px) {
        .filter-card {
            grid-template-columns: 1fr;
        }

        .allocation-card {
            overflow-x: auto;
        }

        .allocation-table {
            min-width: 900px;
        }
    }
</style>

<div class="allocation-header">
    <h2 class="allocation-title">My Allocations</h2>

    <div class="allocation-subtitle">
        Office/Unit: <strong><?php echo e($officeName); ?></strong>
    </div>

    <div class="allocation-subtitle">
        These are the items your unit may request.
    </div>
</div>

<div class="filter-card">
    <div class="filter-group">
        <label class="filter-label" for="allocationSearch">Search Item</label>
        <input
            type="text"
            id="allocationSearch"
            class="filter-input"
            placeholder="Search by stock ID or description..."
        >
    </div>

    <div class="filter-group">
        <label class="filter-label" for="categoryFilter">Category</label>
        <select id="categoryFilter" class="filter-select">
            <option value="">All Categories</option>
            <?php $__currentLoopData = $categoryOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($category); ?>"><?php echo e($category); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>

    <div class="filter-group">
        <label class="filter-label" for="statusFilter">Status</label>
        <select id="statusFilter" class="filter-select">
            <option value="">All Status</option>
            <option value="available">Available</option>
            <option value="fully_used">Fully Used</option>
        </select>
    </div>

    <button type="button" class="clear-btn" onclick="clearAllocationFilters()">Clear</button>
</div>

<div class="allocation-card">
    <?php if($allocations->isEmpty()): ?>
        <div class="empty-state">
            No allocation available.
        </div>
    <?php else: ?>
        <table class="allocation-table">
            <thead>
                <tr>
                    <th>Stock ID</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Total Allocated</th>
                    <th>Requested / Released</th>
                    <th>Remaining Allocation</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody id="allocationTableBody">
                <?php $__currentLoopData = $allocations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $allocation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $stock = $allocation->stock;
                        $category = $stock?->category?->name;

                        $allocated = (int) $allocation->total_allocated;
                        $requested = (int) $allocation->requested_quantity;
                        $remaining = (int) $allocation->remaining_allocation;

                        if ($remaining > 0) {
                            $statusKey = 'available';
                            $statusLabel = 'Available';
                            $statusClass = 'pill-green';
                        } elseif ($allocated > 0) {
                            $statusKey = 'fully_used';
                            $statusLabel = 'Fully Used';
                            $statusClass = 'pill-orange';
                        }
                    ?>

                    <tr
                        class="allocation-row"
                        data-search="<?php echo e(strtolower(($stock?->id_no ?? '') . ' ' . ($stock?->description ?? ''))); ?>"
                        data-category="<?php echo e($category); ?>"
                        data-status="<?php echo e($statusKey); ?>"
                        data-allocated="<?php echo e($allocated); ?>"
                        data-requested="<?php echo e($requested); ?>"
                        data-remaining="<?php echo e($remaining); ?>"
                    >
                        <td>
                            <span class="stock-id"><?php echo e($stock?->id_no ?? '—'); ?></span>
                        </td>

                        <td>
                            <strong><?php echo e($stock?->description ?? 'Unknown item'); ?></strong>
                        </td>

                        <td><?php echo e($category); ?></td>

                        <td><?php echo e($stock?->unit ?? '—'); ?></td>

                        <td><?php echo e(number_format($allocated)); ?></td>

                        <td><?php echo e(number_format($requested)); ?></td>

                        <td>
                            <strong><?php echo e(number_format($remaining)); ?></strong>
                        </td>

                        <td>
                            <span class="pill <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>

        <div id="allocationEmptyFilter" class="empty-state" style="display:none;">
            No data matched your search or filter.
        </div>
    <?php endif; ?>
</div>

<script>
    const allocationSearch = document.getElementById('allocationSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');

    const summaryAllocated = document.getElementById('summaryAllocated');
    const summaryRequested = document.getElementById('summaryRequested');
    const summaryRemaining = document.getElementById('summaryRemaining');
    const allocationEmptyFilter = document.getElementById('allocationEmptyFilter');

    function formatNumber(value) {
        return Number(value || 0).toLocaleString();
    }

    function getAllocationRows() {
        return Array.from(document.querySelectorAll('.allocation-row'));
    }

    function applyAllocationFilters() {
        const searchValue = (allocationSearch?.value || '').toLowerCase().trim();
        const categoryValue = categoryFilter?.value || '';
        const statusValue = statusFilter?.value || '';

        let visibleCount = 0;
        let allocatedTotal = 0;
        let requestedTotal = 0;
        let remainingTotal = 0;

        getAllocationRows().forEach((row) => {
            const rowSearch = row.dataset.search || '';
            const rowCategory = row.dataset.category || '';
            const rowStatus = row.dataset.status || '';

            const matchesSearch = !searchValue || rowSearch.includes(searchValue);
            const matchesCategory = !categoryValue || rowCategory === categoryValue;
            const matchesStatus = !statusValue || rowStatus === statusValue;

            const shouldShow = matchesSearch && matchesCategory && matchesStatus;

            row.classList.toggle('hidden-row', !shouldShow);

            if (shouldShow) {
                visibleCount++;
                allocatedTotal += Number(row.dataset.allocated || 0);
                requestedTotal += Number(row.dataset.requested || 0);
                remainingTotal += Number(row.dataset.remaining || 0);
            }
        });

        summaryAllocated.textContent = formatNumber(allocatedTotal);
        summaryRequested.textContent = formatNumber(requestedTotal);
        summaryRemaining.textContent = formatNumber(remainingTotal);

        if (allocationEmptyFilter) {
            allocationEmptyFilter.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    function clearAllocationFilters() {
        allocationSearch.value = '';
        categoryFilter.value = '';
        statusFilter.value = '';
        applyAllocationFilters();
    }

    allocationSearch?.addEventListener('input', applyAllocationFilters);
    categoryFilter?.addEventListener('change', applyAllocationFilters);
    statusFilter?.addEventListener('change', applyAllocationFilters);

    applyAllocationFilters();
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\wamp64\www\inventory-system\resources\views/client/allocations/index.blade.php ENDPATH**/ ?>