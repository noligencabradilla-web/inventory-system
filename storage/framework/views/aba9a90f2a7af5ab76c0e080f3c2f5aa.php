<?php
    $brand = 'Inventory System';
    $pageTitle = 'Inbound';
?>

<?php $__env->startSection('sidebar'); ?>
    <?php echo $__env->make('partials.admin-sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <!-- mobile settings toggle (displayed only on narrow screens) -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h2 style="margin:0;">Inbound Items</h2>
        <button id="mobileToolbarToggle" class="mobile-toolbar-toggle"
            style="background:none; border:none; font-size:28px; cursor:pointer; padding:0; line-height:0;">
            ⚙️
        </button>
    </div>

    <style>
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .toolbar-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        /* Button system for the toolbar */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 10px;
            border: 1px solid transparent;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            background: transparent;
            color: var(--text);
            transition: all 0.3s ease;
        }

        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(180deg, var(--blue), #2b6fd6);
            color: #fff;
            border-color: rgba(37, 99, 235, .9);
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(37, 99, 235, .25);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-outline {
            background: transparent;
            color: var(--blue);
            border: 1px solid rgba(37, 99, 235, .25);
            transition: all 0.3s ease;
        }

        .btn-outline:hover {
            background: rgba(37, 99, 235, .08);
            border-color: rgba(37, 99, 235, .5);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(37, 99, 235, .1);
        }

        .btn-outline:active {
            transform: translateY(0);
        }

        .btn-success {
            background: var(--green-soft);
            color: var(--green);
            border: 1px solid var(--green);
        }

        .btn-success:not(:disabled) {
            background: linear-gradient(180deg, #22c55e, #16a34a);
            color: #fff;
            border: 1px solid #16a34a;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .btn-success:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(34, 197, 94, 0.4);
        }

        .btn-success:not(:disabled):active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .file-input {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .file-meta {
            display: flex;
            gap: 8px;
            align-items: center;
            color: var(--muted);
            font-size: 13px;
            min-width: 220px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .clear-file {
            background: transparent;
            border: 0;
            color: var(--muted);
            font-weight: 700;
            cursor: pointer;
            padding: 0 6px;
            border-radius: 6px;
        }

        /* Drag & drop + spinner */
        .dropzone {
            border: 2px dashed var(--line);
            border-radius: 10px;
            padding: 8px 12px;
            min-width: 320px;
            display: flex;
            align-items: center;
            gap: 12px;
            background: transparent;
            transition: background .12s, border-color .12s, box-shadow .12s;
        }

        .dropzone.dragover {
            background: rgba(37, 99, 235, .03);
            border-color: rgba(37, 99, 235, .45);
            box-shadow: 0 6px 18px rgba(2, 6, 23, .06);
        }

        .dropzone .drop-instructions {
            display: flex;
            gap: 8px;
            align-items: center;
            flex: 1;
        }

        .spinner,
        .small-spinner {
            border: 3px solid rgba(0, 0, 0, 0.08);
            border-top-color: var(--blue);
            border-radius: 50%;
            width: 28px;
            height: 28px;
            animation: spin .8s linear infinite;
        }

        .small-spinner {
            width: 14px;
            height: 14px;
            border-width: 2px;
            display: inline-block;
            vertical-align: middle;
            margin-left: 8px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* keep legacy .btn-link for backward compatibility */
        .btn-link {
            display: inline-block;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--blue);
            background: var(--blue-soft);
            color: var(--blue);
            text-decoration: none;
            font-weight: 700;
        }

        .btn-link:hover {
            background: rgba(37, 99, 235, .18);
        }

        .table-wrap {
            overflow: auto;
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 860px;
            background: #fff;
        }

        th,
        td {
            border: 1px solid var(--line);
            padding: 10px;
            text-align: left;
        }

        th {
            background: rgba(37, 99, 235, .06);
            color: var(--text);
            font-weight: 700;
        }

        td {
            color: var(--text);
        }

        .muted {
            color: var(--muted);
        }

        /* mobile-specific adjustments */
        @media (max-width: 768px) {
            #inboundDropZone {
                border: none !important;
                padding: 0 !important;
                background: transparent !important;
            }

            /* stack toolbar items vertically for narrow screens */
            .toolbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .toolbar-actions {
                flex-direction: column;
                gap: 12px;
                width: 100%;
            }

            .toolbar-actions form {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                align-items: center;
            }

            .file-input {
                width: 100%;
            }

            .dropzone {
                min-width: auto;
                width: 100%;
            }

            .dropzone .drop-instructions {
                flex: 1 1 100%;
            }

            #inboundImportBtn {
                width: 100%;
            }
        }

        /* show/hide toolbar on mobile */
        .mobile-toolbar-toggle {
            display: none;
        }

        @media (max-width: 768px) {
            .mobile-toolbar-toggle {
                display: inline-block !important;
            }

            .toolbar-actions {
                display: none;
            }

            .toolbar-actions.show {
                display: flex;
            }
        }

        /* mobile options modal styles */
        #mobileOptionsModal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .45);
            z-index: 12000;
            align-items: center;
            justify-content: center;
            visibility: hidden;
            opacity: 0;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        #mobileOptionsModal.show {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        #mobileOptionsModal .modal-content {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 18px 60px rgba(2, 6, 23, .35);
            max-width: 90vw;
            width: 90vw;
            padding: 20px;
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
        }

        #mobileOptionsModal .close-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            background: #eee;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            font-size: 18px;
            line-height: 0;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        #mobileOptionsModal .close-btn:hover {
            background: #ddd;
        }

        /* vertical layout for modal toolbar actions */
        #mobileOptionsModal .toolbar-actions {
            flex-direction: column !important;
            gap: 12px !important;
            width: 100% !important;
        }

        #mobileOptionsModal .toolbar-actions .btn {
            width: 100%;
            justify-content: center;
        }

        #mobileOptionsModal .toolbar-actions form {
            width: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 12px;
        }

        /* hide file input control inside mobile modal – we keep logic but not UI element */
        #mobileOptionsModal input[type="file"] {
            display: none !important;
        }

        #mobileOptionsModal .toolbar-actions .file-input {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        #mobileOptionsModal .toolbar-actions .dropzone {
            min-width: auto !important;
            width: 100% !important;
            border: none !important;
            padding: 0 !important;
            background: transparent !important;
            min-height: auto;
            gap: 0;
            display: flex;
            flex-direction: column;
        }

        #mobileOptionsModal .toolbar-actions .drop-instructions {
            flex-direction: column;
            gap: 8px;
            width: 100%;
            display: flex;
        }

        #mobileOptionsModal .toolbar-actions .drop-instructions .btn {
            margin: 0;
            width: 100%;
        }

        #mobileOptionsModal .toolbar-actions .file-meta {
            min-width: auto;
            width: 100%;
            white-space: normal;
            word-break: break-word;
            padding: 8px;
            background: #f5f5f5;
            border-radius: 6px;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #mobileOptionsModal .toolbar-actions #inboundImportBtn {
            width: 100%;
            margin-top: 8px;
        }

        @media (max-width: 768px) {

            /* ensure toolbar actions hidden under modal approach */
            .toolbar-actions {
                display: none !important;
            }
        }

        /* Add inbound button hover effects */
        .btn-add-inbound:hover {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;
            color: #ffffff !important;
            transform: translateY(-3px) !important;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3) !important;
            border-color: rgba(59, 130, 246, 0.5) !important;
        }

        .btn-add-inbound:hover::after {
            left: 100% !important;
        }

        .btn-add-inbound:active {
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.2) !important;
        }

        /* Modal button hover effects */
        .modal-btn-primary:hover {
            background: linear-gradient(135deg, #2563eb, #1e40af) !important;
            transform: translateY(-3px) !important;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3) !important;
            border-color: rgba(59, 130, 246, 0.5) !important;
        }

        .modal-btn-primary:hover::after {
            left: 100% !important;
        }

        .modal-btn-primary:active {
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.2) !important;
        }

        .modal-btn-secondary:hover {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.15) !important;
            border-color: rgba(59, 130, 246, 0.3) !important;
            color: #374151 !important;
        }

        .modal-btn-secondary:hover::after {
            left: 100% !important;
        }

        .modal-btn-secondary:active {
            transform: translateY(-1px) !important;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1) !important;
        }

        .allocation-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            margin-bottom: 10px;
            transition: 0.2s ease;
        }

        .allocation-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            transform: translateY(-1px);
        }

        .allocation-office {
            font-size: 13px;
            font-weight: 600;
            color: #1f2937;
        }

        .allocation-qty {
            background: #dbeafe;
            color: #1d4ed8;
            font-weight: 700;
            font-size: 16px;
            padding: 4px 10px;
            border-radius: 999px;
        }

        .inbound-row {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .inbound-row:hover {
            background: #eff6ff !important;
        }

        .inbound-row.active {
            /* background: #dbeafe !important; */
            background: linear-gradient(135deg, #dbeafe, #bfdbfe) !important;
            box-shadow: inset 4px 0 0 #2563eb;
        }

        .inbound-row.active td {
            border-color: #93c5fd !important;
        }
    </style>

    

    <div class="toolbar" style="position:relative;">
        <div class="toolbar-actions">
            <button type="button" onclick="openInboundModal()" class="btn btn-outline btn-add-inbound">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                    <path d="M12 5v14M5 12h14"></path>
                </svg>
                Add Inbound
            </button>
            <a class="btn btn-primary" href="<?php echo e(route('inbound.template')); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7,10 12,15 17,10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Template
            </a>
            <form action="<?php echo e(route('inbound.import')); ?>" method="POST" enctype="multipart/form-data"
                style="display:flex; gap:8px; align-items:center; margin:0;">
                <?php echo csrf_field(); ?>
                <div class="file-input">
                    <div id="inboundDropZone" class="dropzone" role="button" tabindex="0"
                        aria-label="Drop file here or click to select">
                        <div class="drop-instructions">
                            <button type="button" id="inboundChooseBtn" class="btn btn-outline">Choose file</button>
                            <div class="file-meta"><span id="inboundFileName" class="muted">No file selected</span><button
                                    id="inboundClearBtn" type="button" class="clear-file"
                                    title="Clear selection">×</button></div>
                        </div>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" hidden required>
                    </div>
                </div>
                <button id="inboundImportBtn" type="submit" class="btn btn-success" disabled>
                    <span id="inboundImportLabel">Import</span>
                    <span id="inboundImportSpinner" class="small-spinner" style="display:none;" aria-hidden="true"></span>
                </button>
            </form>
        </div>
    </div>

    <!-- mobile options modal -->
    <div id="mobileOptionsModal">
        <div class="modal-content">
            <button id="mobileOptionsClose" class="close-btn">&times;</button>
            <div id="mobileOptionsContainer"></div>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div
            style="margin:12px 0; padding:12px; background:rgba(34,197,94,0.08); border:1px solid rgba(34,197,94,0.18); color:var(--green); border-radius:8px;">
            <?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div
            style="margin:12px 0; padding:12px; background:rgba(239,68,68,0.06); border:1px solid rgba(239,68,68,0.12); color:var(--red); border-radius:8px;">
            <?php echo e(session('error')); ?></div>
    <?php endif; ?>

    <script>
        (function() {
            const input = document.querySelector('form input[type="file"]');
            const dropZone = document.getElementById('inboundDropZone');
            const chooseBtn = document.getElementById('inboundChooseBtn');
            const clearBtn = document.getElementById('inboundClearBtn');
            const fileNameSpan = document.getElementById('inboundFileName');
            const importBtn = document.getElementById('inboundImportBtn');
            const importLabel = document.getElementById('inboundImportLabel');
            const importSpinner = document.getElementById('inboundImportSpinner');
            const maxSize = 10 * 1024 * 1024; // 10MB

            function setFile(file) {
                if (!file) {
                    input.value = '';
                    // update all file name spans (original + modal clone)
                    document.querySelectorAll('#inboundFileName').forEach(span => {
                        span.textContent = 'No file selected';
                        span.classList.add('muted');
                    });
                    // update all import buttons (original + modal clone)
                    document.querySelectorAll('#inboundImportBtn').forEach(btn => {
                        btn.disabled = true;
                    });
                    return;
                }
                // update all file name spans (original + modal clone)
                const fileText = `${file.name} (${Math.round(file.size/1024)} KB)`;
                document.querySelectorAll('#inboundFileName').forEach(span => {
                    span.textContent = fileText;
                    span.classList.remove('muted');
                });
                // update all import buttons (original + modal clone)
                document.querySelectorAll('#inboundImportBtn').forEach(btn => {
                    btn.disabled = false;
                });
            }

            chooseBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                input.click();
            });
            dropZone.addEventListener('click', (e) => {
                if (e.target === dropZone || dropZone.contains(e.target) && e.target !== chooseBtn && !e.target
                    .closest('.clear-file')) input.click();
            });

            input.addEventListener('change', () => {
                const f = input.files && input.files[0] ? input.files[0] : null;
                // keep modal input in sync, though form submission always uses original
                const clonedInput = document.querySelector('#mobileOptionsModal form input[type="file"]');
                if (clonedInput && input.files.length > 0) {
                    const dt = new DataTransfer();
                    for (let file of input.files) {
                        dt.items.add(file);
                    }
                    clonedInput.files = dt.files;
                }
                setFile(f);
            });

            clearBtn.addEventListener('click', () => setFile(null));

            // Drag & drop handlers
            ['dragenter', 'dragover'].forEach(ev => dropZone.addEventListener(ev, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.add('dragover');
            }));
            ['dragleave', 'drop', 'dragend'].forEach(ev => dropZone.addEventListener(ev, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('dragover');
            }));

            dropZone.addEventListener('drop', (e) => {
                const dt = e.dataTransfer || e.originalEvent?.dataTransfer;
                if (dt && dt.files && dt.files.length) {
                    input.files = dt.files;
                }
            });

            // mobile options modal logic
            const mobileToggle = document.getElementById('mobileToolbarToggle');
            const toolbarActions = document.querySelector('.toolbar-actions');
            const optionsModal = document.getElementById('mobileOptionsModal');
            const optionsContainer = document.getElementById('mobileOptionsContainer');
            const optionsClose = document.getElementById('mobileOptionsClose');

            console.log('Modal elements:', {
                mobileToggle,
                toolbarActions,
                optionsModal,
                optionsClose
            });

            // Open modal on gear icon click
            if (mobileToggle) {
                mobileToggle.addEventListener('click', function(e) {
                    console.log('Gear icon clicked!');
                    e.preventDefault();
                    e.stopPropagation();
                    if (toolbarActions && optionsContainer) {
                        optionsContainer.innerHTML = toolbarActions.innerHTML;

                        // configure cloned file input: we'll keep it hidden but retain its attributes
                        // this preserves any underlying HTML form behavior, while the actual logic
                        // still uses the original input element for file data.
                        const clonedInput = optionsContainer.querySelector('input[type="file"]');
                        if (clonedInput) {
                            clonedInput.style.display = 'none';
                        }

                        // re-bind file picker handlers to cloned elements in modal
                        const clonedChooseBtn = optionsContainer.querySelector('#inboundChooseBtn');
                        const clonedClearBtn = optionsContainer.querySelector('#inboundClearBtn');
                        const clonedDropZone = optionsContainer.querySelector('#inboundDropZone');

                        if (clonedChooseBtn) {
                            clonedChooseBtn.addEventListener('click', (e) => {
                                e.stopPropagation();
                                input.click();
                            });
                        }
                        if (clonedClearBtn) {
                            clonedClearBtn.addEventListener('click', () => setFile(null));
                        }
                        if (clonedDropZone) {
                            clonedDropZone.addEventListener('click', (e) => {
                                if (e.target === clonedDropZone || clonedDropZone.contains(e.target) &&
                                    e.target !== clonedChooseBtn && !e.target.closest('.clear-file'))
                                    input.click();
                            });
                        }

                        // re-bind form submit to cloned form
                        const clonedForm = optionsContainer.querySelector('form');
                        if (clonedForm) {
                            clonedForm.addEventListener('submit', (e) => {
                                if (!input.files || !input.files.length) {
                                    e.preventDefault();
                                    alert('Please choose a file to import.');
                                    return;
                                }
                                if (input.files[0].size > maxSize) {
                                    e.preventDefault();
                                    alert('File is too large. Max size is 10 MB.');
                                    return;
                                }

                                // show spinner / prevent double-submit
                                document.querySelectorAll('#inboundImportBtn').forEach(btn => btn
                                    .disabled = true);
                                document.querySelectorAll('#inboundImportLabel').forEach(label => label
                                    .textContent = 'Importing...');
                                document.querySelectorAll('#inboundImportSpinner').forEach(spinner =>
                                    spinner.style.display = 'inline-block');
                            });
                        }
                    }
                    if (optionsModal) {
                        optionsModal.classList.add('show');
                        console.log('Modal class added, modal should be visible now');
                    }
                });
            }

            // Close modal on close button click
            if (optionsClose) {
                optionsClose.addEventListener('click', function() {
                    console.log('Close button clicked');
                    if (optionsModal) optionsModal.classList.remove('show');
                    // reset form state when closing
                    document.querySelectorAll('#inboundImportBtn').forEach(btn => btn.disabled = true);
                    document.querySelectorAll('#inboundImportLabel').forEach(label => label.textContent =
                        'Import');
                    document.querySelectorAll('#inboundImportSpinner').forEach(spinner => spinner.style
                        .display = 'none');
                });
            }

            // Close modal on backdrop click
            if (optionsModal) {
                optionsModal.addEventListener('click', function(e) {
                    if (e.target === optionsModal) {
                        console.log('Backdrop clicked');
                        optionsModal.classList.remove('show');
                    }
                });
            }

            const form = input.closest('form');
            form.addEventListener('submit', (e) => {
                if (!input.files || !input.files.length) {
                    e.preventDefault();
                    alert('Please choose a file to import.');
                    return;
                }
                if (input.files[0].size > maxSize) {
                    e.preventDefault();
                    alert('File is too large. Max size is 10 MB.');
                    return;
                }

                // show spinner / prevent double-submit
                importBtn.disabled = true;
                importLabel.textContent = 'Importing...';
                importSpinner.style.display = 'inline-block';
            });

            // accessibility: Enter / Space opens file picker when drop zone focused
            dropZone.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    input.click();
                }
            });
        })();
    </script>

    <div
        style="display:flex; flex-wrap:wrap; gap:12px; justify-content:space-between; align-items:flex-end; margin-bottom:20px;">
        <form method="GET" action="<?php echo e(route('inbound.index')); ?>"
            style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
            <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                <div style="display:flex; flex-direction:column; min-width:160px;">
                    <label for="date_from" style="font-size:12px; font-weight:600; color:#334155; margin-bottom:4px;">From
                        Date</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo e(request('date_from')); ?>"
                        style="padding:10px 12px; border:1px solid #d1d5db; border-radius:10px; background:#ffffff; min-width:160px;">
                </div>
                <div style="display:flex; flex-direction:column; min-width:160px;">
                    <label for="date_to" style="font-size:12px; font-weight:600; color:#334155; margin-bottom:4px;">To
                        Date</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo e(request('date_to')); ?>"
                        style="padding:10px 12px; border:1px solid #d1d5db; border-radius:10px; background:#ffffff; min-width:160px;">
                </div>
            </div>

            <button type="submit" class="btn-submit"
                style="padding:10px 16px; font-size:13px; background:#3b82f6; color:#ffffff; border:none; border-radius:10px; cursor:pointer;">Filter</button>
            <a href="<?php echo e(route('admin.inbound.report.pdf')); ?>?<?php echo e(http_build_query(request()->only(['date_from', 'date_to']))); ?>"
                class="btn-submit"
                style="padding:10px 16px; font-size:13px; background:#10b981; color:#ffffff; border:none; border-radius:10px; text-decoration:none; display:inline-flex; align-items:center;">Download
                PDF</a>
        </form>
    </div>

    <!-- Search Bar -->
    <div style="margin-bottom:20px;">
        <div style="position:relative; max-width:400px;">
            <input type="text" id="inboundSearchInput"
                placeholder="Search by Stock ID, Description, Unit, Quantity, or Category..."
                style="width:100%; padding:12px 14px 12px 45px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05); outline:none;"
                value="<?php echo e(request('q')); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round"
                style="position:absolute; left:14px; top:50%; transform:translateY(-50%); pointer-events:none;">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-6.98-6.98a2 2 0 0 1-2.82 0-5.64a2 2 0 0 1 2.82 0 5.64z"></path>
            </svg>
        </div>
    </div>

    <div
        style="overflow-x:auto; border-radius:16px; box-shadow:0 8px 25px rgba(59,130,246,0.15); background:linear-gradient(135deg, #eff6ff, #dbeafe);">
        <table style="width:100%; border-collapse:collapse; font-family:Inter, system-ui, sans-serif;">
            <thead>
                <tr style="background:linear-gradient(135deg, #3b82f6, #1d4ed8);">
                    <th
                        style="padding:12px 10px; text-align:left; border:1px solid #1e40af; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px; min-width:140px;">
                        Stock ID</th>
                    <th
                        style="padding:12px 10px; text-align:left; border:1px solid #1e40af; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px; min-width:240px;">
                        Description</th>
                    
                    <th
                        style="padding:12px 10px; text-align:left; border:1px solid #1e40af; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px; min-width:90px;">
                        Quantity - Unit</th>
                    <th
                        style="padding:12px 10px; text-align:left; border:1px solid #1e40af; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px; min-width:160px;">
                        Allocated</th>
                    <th
                        style="padding:12px 10px; text-align:left; border:1px solid #1e40af; border-bottom:2px solid #1e40af; font-weight:700; color:#ffffff; font-size:12px; min-width:140px;">
                        Date</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $inbounds; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="inbound-row" onclick="selectRow(this); openAllocationModal(<?php echo e($row->id); ?>)"
                        style="border-bottom:1px solid #e0e7ff; background:#ffffff;">
                        <td style="padding:14px 10px; border:1px solid #e0e7ff;">
                            <div style="font-weight:700; color:#1e40af; font-size:14px;"><?php echo e($row->id_no ?? '—'); ?></div>
                        </td>
                        <td style="padding:14px 10px; border:1px solid #e0e7ff;">
                            <div style="color:#334155; font-size:14px;"><?php echo e($row->description ?? '—'); ?></div>
                        </td>
                        
                        <td style="padding:14px 10px; border:1px solid #e0e7ff; color:#475569; font-weight:600;">
                            <?php echo e($row->total ?? 0); ?> - <?php echo e($row->unit ?? 'pcs'); ?></td>
                        <td style="padding:14px 10px; border:1px solid #e0e7ff; color:#475569; font-size:14px;">
                            <?php if($row->allocated > 0): ?>
                            <span style="padding:14px 10px; color:#475569; font-weight:800;">
                                <?php echo e($row->allocated ?? 0); ?>

                                </span>
                                <button type="button" onclick="openAllocationModal(<?php echo e($row->id); ?>)"
                                    class="btn btn-outline btn-add-inbound">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2
                                        width="16" height="16"
                                        stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    
                                </button>
                            <?php else: ?>
                                <span style="padding:14px 10px; color:#475569; font-weight:800;">
                                    No Allocation
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:14px 10px; border:1px solid #e0e7ff; color:#475569; font-size:14px;">
                            <?php echo e(\Carbon\Carbon::parse($row->created_at)->format('M d, Y')); ?></td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr style="background:linear-gradient(135deg, #f8fafc, #f1f5f9);">
                        <td colspan="6" style="padding:20px 10px; text-align:center; color:#64748b; font-size:14px;">No
                            inbound records yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    </div> <!-- close mobile toggle wrapper -->

    <!-- Add Inbound Modal -->
    <div id="inboundModal"
        style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,.5); z-index:9999; justify-content:center; align-items:center;">
        <div
            style="background:#ffffff; border-radius:16px; padding:24px; width:520px; max-width:95%; box-shadow:0 18px 40px rgba(2,6,23,.2);">
            <h3 style="margin:0 0 20px 0; font-size:18px; font-weight:800; color:#1e293b;">Add Inbound Item</h3>

            <?php if($errors->any()): ?>
                <div
                    style="color: var(--red); margin-bottom:16px; padding:12px; background: rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.3); border-radius:8px;">
                    <ul style="margin:0; padding-left:20px;">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li style="margin:4px 0;"><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="<?php echo e(route('inbound.store')); ?>" method="POST" id="inboundForm">
                <?php echo csrf_field(); ?>

                <div style="margin-bottom:16px;">
                    <label
                        style="display:block; margin-bottom:8px; color: #374151; font-weight:700; font-size:14px;">Search
                        Stock (by Description):</label>
                    <div style="position:relative;">
                        <input type="text" id="stock_search" placeholder="Type to search existing stocks..."
                            autocomplete="off"
                            style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05);">
                        <ul id="suggestions_list"
                            style="position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid var(--line); border-top:none; border-radius:0 0 8px 8px; list-style:none; margin:0; padding:0; max-height:300px; overflow-y:auto; z-index:1000; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display:none;">
                        </ul>
                    </div>
                    <input type="hidden" id="stock_id" name="stock_id" required>
                    <small id="stock_info"
                        style="color:var(--muted); margin-top:8px; display:block; font-size:12px;"></small>
                </div>

                <div style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; color: #374151; font-weight:700; font-size:14px;">Total
                        Quantity:</label>
                    <input type="number" name="total" id="total" value="1" min="1" required
                        step="1"
                        style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05);">
                </div>
                <div style="margin-bottom:20px;" id="officeAllocations">
                    <div class="text-xl font-bold">
                        <h3 style="margin:0 0 20px 0; font-size:14px; font-weight:800; color:#1e293b;">
                            Office Allocation (optional)
                        </h3>
                        <button type="button" onclick="addOfficeRow()" class="btn btn-outline btn-add-inbound">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                                <path d="M12 5v14M5 12h14"></path>
                            </svg>
                            Add Office
                        </button>
                    </div>
                    <label style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Office
                        (optional)</label>
                    
                    
                    <div class="office-row" style="display:flex;gap:10px;margin-bottom:10px;">
                        
                    </div>
                </div>

                <div style="display:flex; gap:12px;">
                    <button type="submit" class="modal-btn-primary"
                        style="flex:1; padding:12px 20px; border-radius:10px; border:2px solid #3b82f6; background:linear-gradient(135deg, #3b82f6, #1d4ed8); color:#ffffff; font-weight:700; transition:all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow:0 4px 12px rgba(59,130,246,0.2); position:relative; overflow:hidden; transform:translateY(0); pointer-events:auto;">
                        <span style="position:relative; z-index:1;">Add Inbound</span>
                        <span
                            style="position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg, transparent, rgba(255,255,255,0.2)); transition:left 0.3s ease;"></span>
                    </button>
                    <button type="button" onclick="closeInboundModal()" class="modal-btn-secondary"
                        style="flex:1; padding:12px 20px; border-radius:10px; border:2px solid #e2e8f0; background:#ffffff; color:#64748b; font-weight:600; transition:all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow:0 1px 3px rgba(15,23,42,.05); position:relative; overflow:hidden; transform:translateY(0); pointer-events:auto;">
                        <span style="position:relative; z-index:1;">Cancel</span>
                        <span
                            style="position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg, transparent, rgba(59,130,246,0.1)); transition:left 0.3s ease;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div id="viewAllocationModal"
        style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,.5); z-index:9999; justify-content:center; align-items:center;">
        <div
            style="background:#ffffff; border-radius:16px; padding:24px; width:520px; max-width:95%; box-shadow:0 18px 40px rgba(2,6,23,.2);">
            <h3 style="margin:0 0 20px 0; font-size:18px; font-weight:800; color:#1e293b;">View Inbound Allocation/s</h3>
            <div id="allocationData"></div>
            <button type="button" onclick="closeAllocationModal()" class="modal-btn-secondary"
                style="flex:1; padding:12px 20px; border-radius:10px; border:2px solid #e2e8f0; background:#ffffff; color:#64748b; font-weight:600; transition:all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow:0 1px 3px rgba(15,23,42,.05); position:relative; overflow:hidden; transform:translateY(0); pointer-events:auto;">
                <span style="position:relative; z-index:1;">Cancel</span>
                <span
                    style="position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg, transparent, rgba(59,130,246,0.1)); transition:left 0.3s ease;"></span>
            </button>
        </div>
    </div>

    <script>
        /**@argument
         * ADA
         */

        function selectRow(row) {
            document.querySelectorAll('.inbound-row').forEach(r => {
                r.classList.remove('active');
            });

            row.classList.add('active');
            // const isActive = row.classList.contains('active');

            // document.querySelectorAll('.inbound-row').forEach(r => {
            //     r.classList.remove('active');
            // });

            // if (!isActive) {
            //     row.classList.add('active');
            // }
        }


        function openAllocationModal(inbound_id) {
            document.getElementById('viewAllocationModal').style.display = 'flex';
            fetch(`/api/inbound/${inbound_id}/allocations`)
                .then(response => response.json())
                .then(data => {
                    let html = '';

                    data?.allocations.forEach(item => {
                        html += `
                        <div class="allocation-item">
               <div class="allocation-office">
                    <p class="text-sm font-semibold text-gray-800">
                        ${item.office_name}
                    </p>
                </div>
                <div class="allocation-qty">
                                         <span>${item.allocation}</span>
                                    </div>

            </div>
            `;
                    });

                    document.getElementById('allocationData').innerHTML = html;
                }).catch(error => {
                    console.error('Error fetching allocation data:', error);
                });

        }

        function closeAllocationModal() {
            document.getElementById('viewAllocationModal').style.display = 'none';
        }

        let officeIndex = 1;

        function addOfficeRow() {
            const container = document.getElementById('officeAllocations');

            const row = document.createElement('div');
            row.className = 'office-row';
            row.style.cssText = 'display:flex;gap:10px;margin-bottom:10px;';

            row.innerHTML = `
        <select name="office_allocation[${officeIndex}][office_id]"
            style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05); text-transform: capitalize;">
            <option value="">Select Office</option>

            <?php $__currentLoopData = $spOffices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $office): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($office->id); ?>">
                    <?php echo e($office->office); ?>

                </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
<div>
    <input
        type="number"
        name="office_allocation[${officeIndex}][quantity]"
        min="1"
        placeholder="Qty"
        style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05);">
                </div>
            <div>
                <button type="button" onclick="removeOfficeRow(this)" class="modal-btn-secondary"
                                       style="flex:1; padding:12px 20px; border-radius:10px; border:2px solid #e2e8f0; background:#ffffff; color:#64748b; font-weight:600; transition:all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow:0 1px 3px rgba(15,23,42,.05); position:relative; overflow:hidden; transform:translateY(0); pointer-events:auto;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                               stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                                               <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                           </svg>
                                   </button>
                </div>

    `;

            container.appendChild(row);
            officeIndex++;
        }

        function removeOfficeRow(button) {
            button.closest('.office-row').remove();
        }

        /** @abstract
         * noli - OJT
         */
        // Pass stocks data to JavaScript
        const stocksData = <?php echo json_encode($stocks, 15, 512) ?>;

        function openInboundModal() {
            document.getElementById('inboundModal').style.display = 'flex';
        }

        function closeInboundModal() {
            document.getElementById('inboundModal').style.display = 'none';
        }

        // Close modal when clicking outside
        // document.getElementById('inboundModal').addEventListener('click', function(e) {
        //     if (e.target === this) {
        //         closeInboundModal();
        //     }
        // });

        // Autocomplete functionality for inbound modal
        (function() {
            const searchInput = document.getElementById('stock_search');
            const suggestionsList = document.getElementById('suggestions_list');
            const stockIdField = document.getElementById('stock_id');
            const stockInfoField = document.getElementById('stock_info');
            const form = document.getElementById('inboundForm');

            let currentSelected = null;

            // Fetch suggestions from API
            async function fetchSuggestions(query) {
                try {
                    let url = `<?php echo e(route('inbound.suggestions')); ?>?q=${encodeURIComponent(query)}`;

                    // If query is empty, try with a common character
                    if (!query || query.trim() === '') {
                        url = `<?php echo e(route('inbound.suggestions')); ?>?q=a`;
                    }

                    const response = await fetch(url);
                    const data = await response.json();

                    // Debug: Log the first stock item to see available fields
                    if (data.length > 0) {
                        console.log('Stock data fields:', Object.keys(data[0]));
                        console.log('First stock item:', data[0]);
                    }

                    // If no results with 'a', try with empty string
                    if (data.length === 0 && (!query || query.trim() === '')) {
                        const emptyResponse = await fetch(`<?php echo e(route('inbound.suggestions')); ?>?q=`);
                        const emptyData = await emptyResponse.json();

                        // Debug: Log the first stock item from empty query
                        if (emptyData.length > 0) {
                            console.log('Empty query stock data fields:', Object.keys(emptyData[0]));
                            console.log('Empty query first stock item:', emptyData[0]);
                        }

                        if (emptyData.length === 0) {
                            suggestionsList.innerHTML =
                                '<li style="color:var(--muted); padding:10px;">No stocks found</li>';
                            suggestionsList.style.display = 'block';
                            return;
                        }

                        // Use empty query results
                        suggestionsList.innerHTML = emptyData.map(stock => {
                            // Find the stock in local data to get accurate quantity
                            const localStock = stocksData.find(s => s.id == stock.id);
                            const quantity = localStock ? localStock.stock || localStock.quantity ||
                                localStock.available || localStock.amount || 0 : 0;

                            return `
                    <li data-id="${stock.id}" data-description="${stock.description}" data-code="${stock.id_no}" data-unit="${stock.unit}" style="padding:10px; cursor:pointer; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center;">
                        <div style="flex:1;">
                            <div style="font-weight:500; color:var(--text);">${stock.description}</div>
                            <div style="font-size:12px; color:var(--muted);">ID: ${stock.id_no} ${stock.category_name ? ' ' + stock.category_name : ''}</div>
                        </div>
                        <div style="display:flex; flex-direction:column; align-items:flex-end; margin-left:8px;">
                            <div style="font-size:12px; color:var(--muted);">${stock.unit || 'pcs'}</div>
                            <div style="font-size:11px; color:#059669; font-weight:600;">Available: ${quantity}</div>
                        </div>
                    </li>
                `;
                        }).join('');

                        // Add click handlers to each suggestion
                        suggestionsList.querySelectorAll('li').forEach(li => {
                            li.addEventListener('click', function() {
                                selectStock(this.dataset.id, this.dataset.description, this.dataset
                                    .code, this.dataset.unit);
                            });

                            // Add hover effect
                            li.addEventListener('mouseenter', function() {
                                this.style.background = 'rgba(37,99,235,.05)';
                            });

                            li.addEventListener('mouseleave', function() {
                                this.style.background = '';
                            });
                        });

                        suggestionsList.style.display = 'block';
                        return;
                    }

                    if (data.length === 0) {
                        suggestionsList.innerHTML =
                            '<li style="color:var(--muted); padding:10px;">No stocks found</li>';
                        suggestionsList.style.display = 'block';
                        return;
                    }

                    suggestionsList.innerHTML = data.map(stock => {
                        // Find the stock in local data to get accurate quantity
                        const localStock = stocksData.find(s => s.id == stock.id);
                        const quantity = localStock ? localStock.stock || localStock.quantity || localStock
                            .available || localStock.amount || 0 : 0;

                        return `
                <li data-id="${stock.id}" data-description="${stock.description}" data-code="${stock.id_no}" data-unit="${stock.unit}" style="padding:10px; cursor:pointer; border-bottom:1px solid var(--line); display:flex; justify-content:space-between; align-items:center;">
                    <div style="flex:1;">
                        <div style="font-weight:500; color:var(--text);">${stock.description}</div>
                        <div style="font-size:12px; color:var(--muted);">ID: ${stock.id_no} ${stock.category_name ? ' ' + stock.category_name : ''}</div>
                    </div>
                    <div style="display:flex; flex-direction:column; align-items:flex-end; margin-left:8px;">
                        <div style="font-size:12px; color:var(--muted);">${stock.unit || 'pcs'}</div>
                        <div style="font-size:11px; color:#059669; font-weight:600;">Available: ${quantity}</div>
                    </div>
                </li>
            `;
                    }).join('');

                    // Add click handlers to each suggestion
                    suggestionsList.querySelectorAll('li').forEach(li => {
                        li.addEventListener('click', function() {
                            selectStock(this.dataset.id, this.dataset.description, this.dataset
                                .code, this.dataset.unit);
                        });

                        // Add hover effect
                        li.addEventListener('mouseenter', function() {
                            this.style.background = 'rgba(37,99,235,.05)';
                        });

                        li.addEventListener('mouseleave', function() {
                            this.style.background = '';
                        });
                    });

                    suggestionsList.style.display = 'block';
                } catch (error) {
                    console.error('Error fetching suggestions:', error);
                }
            }

            // Select a stock from suggestions
            function selectStock(stockId, description, code, unit) {
                stockIdField.value = stockId;
                searchInput.value = description;
                stockInfoField.textContent = `Selected: ${description} (${code}) - Unit: ${unit || 'pcs'}`;
                suggestionsList.style.display = 'none';
                currentSelected = {
                    id: stockId,
                    description,
                    code,
                    unit
                };
            }

            // Handle clear selection
            function clearSelection() {
                stockIdField.value = '';
                stockInfoField.textContent = '';
                currentSelected = null;
            }

            // Click event listener to show all stocks
            searchInput.addEventListener('click', (e) => {
                fetchSuggestions('');
            });

            // Input event listener
            searchInput.addEventListener('input', (e) => {
                const query = e.target.value.trim();
                if (query.length < 1) {
                    clearSelection();
                    fetchSuggestions('');
                } else {
                    fetchSuggestions(query);
                }
            });

            // Click outside to close suggestions
            document.addEventListener('click', (e) => {
                if (!e.target.closest('#inboundModal')) {
                    suggestionsList.style.display = 'none';
                }
            });

            // Form validation
            form.addEventListener('submit', (e) => {
                if (!stockIdField.value) {
                    e.preventDefault();
                    alert('Please select a stock from the suggestions.');
                    searchInput.focus();
                }
            });

            // Show dropdown when input is focused
            searchInput.addEventListener('focus', (e) => {
                fetchSuggestions('');
            });
        })();

        // Inbound table search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('inboundSearchInput');
            const tableRows = document.querySelectorAll('tbody tr');

            if (searchInput && tableRows.length > 0) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = searchInput.value.toLowerCase();

                    tableRows.forEach(row => {
                        const cells = row.querySelectorAll('td');
                        let rowText = '';

                        cells.forEach((cell, index) => {
                            // Skip the first cell if it's the "No inbound records yet" message
                            if (cells.length === 1 && index === 0) {
                                return;
                            }
                            rowText += cell.textContent.toLowerCase() + ' ';
                        });

                        const matchesSearch = !searchTerm || rowText.includes(searchTerm);
                        row.style.display = matchesSearch ? '' : 'none';
                    });
                });
            }
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\wamp64\www\inventory-system\resources\views/admin/inbound/index.blade.php ENDPATH**/ ?>