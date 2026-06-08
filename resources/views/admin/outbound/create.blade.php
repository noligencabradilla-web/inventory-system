@extends('layouts.app')

@php
    $brand = 'Inventory System';
    $pageTitle = 'Outbound';
@endphp

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('content')
    <style>
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

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

        .form-container {
            max-width: 100%;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text);
            font-weight: 700;
        }

        .form-group select,
        .form-group input[type="text"],
        .form-group input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--line);
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-group select:focus,
        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .btn-submit {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            background: var(--blue);
            color: white;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: rgba(37, 99, 235, .9);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(37, 99, 235, .2);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-cancel {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 10px;
            border: 1px solid var(--line);
            background: transparent;
            color: var(--text);
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: var(--line);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, .08);
        }

        .btn-cancel:active {
            transform: translateY(0);
        }

        .error-message {
            color: var(--red);
            margin-bottom: 16px;
            padding: 12px;
            background: rgba(239, 68, 68, .1);
            border: 1px solid rgba(239, 68, 68, .3);
            border-radius: 8px;
        }

        .error-message ul {
            margin: 0;
            padding-left: 20px;
        }

        .error-message li {
            margin: 4px 0;
        }
    </style>

    <div class="toolbar">
        <h2 style="margin:0;">Add Outbound</h2>
        <a class="btn-link" href="{{ route('outbound.index') }}">Back to Outbound</a>
    </div>

    <div class="form-container">
        @if ($errors->any())
            <div class="error-message">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="/admin/outbound/make" method="POST">
            @csrf

            <div class="form-group">
                <div style="margin-bottom:16px;">
                    <label style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Select
                        Stock</label>
                    <div style="position:relative;">
                        <!-- Hidden input to store the selected stock ID -->
                        <input type="hidden" name="stock_id" id="stockIdInput" required>

                        <!-- Custom dropdown trigger -->
                        <div id="stockDropdown"
                            style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05); cursor:pointer; position:relative;">
                            <span id="selectedStockText">-- Choose a stock --</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                style="position:absolute; right:14px; top:50%; transform:translateY(-50%); pointer-events:none;">
                                <path d="M6 9l6 6 6-6"></path>
                            </svg>
                        </div>

                        <!-- Custom dropdown with integrated search -->
                        <div id="stockDropdownMenu"
                            style="position:absolute; top:100%; left:0; right:0; background:#ffffff; border:2px solid #e2e8f0; border-radius:10px; box-shadow:0 8px 25px rgba(15,23,42,.15); margin-top:4px; max-height:300px; overflow-y:auto; z-index:1000; display:none;">
                            <!-- Search bar inside dropdown -->
                            <div style="position:relative; border-bottom:1px solid #e2e8f0;">
                                <input type="text" id="stockSearchInput" placeholder="Search stocks..."
                                    style="width:100%; padding:12px 14px; border:none; border-radius:0; font-size:14px; color:#374151; background:#ffffff; outline:none;">
                            </div>

                            <!-- Stock options -->
                            <div id="stockOptions">
                                @foreach ($stocks as $stock)
                                    <div class="stock-option-item" data-stock-id="{{ $stock->id }}"
                                        data-stock-text="{{ $stock->description }} ({{ $stock->id_no }})"
                                        style="padding:12px 14px; cursor:pointer; border-bottom:1px solid #f1f5f9; transition:background 0.2s ease; font-size:14px; color:#374151;">
                                        <div style="font-weight:600; color:#1e40af;">{{ $stock->description }}</div>
                                        <div style="font-size:12px; color:#64748b; margin-top:2px;">ID: {{ $stock->id_no }}
                                            | Available: {{ $stock->stock }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>



            <div class="form-group">
                <div style="margin-bottom:16px;">
                    <label
                        style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Client</label>
                    <div style="position:relative;">
                        <!-- Hidden input to store the selected client ID -->
                        <input type="hidden" name="client_id" id="clientIdInput" required>
                        <input type="hidden" name="office" id="officeInput">
                        <input type="hidden" name="office_id" id="officeIdInput">

                        <!-- Input field for client/member/non-member entry -->
                        <input type="text" id="clientDropdown" onChange=`checkAllocation() name="recipient_name"
                            placeholder="Type client name, member name, or non-member name..."
                            style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05); outline:none;">

                        <!-- Custom dropdown -->
                        <div id="clientDropdownMenu"
                            style="position:absolute; top:100%; left:0; right:0; background:#ffffff; border:2px solid #e2e8f0; border-radius:10px; box-shadow:0 8px 25px rgba(15,23,42,.15); margin-top:4px; max-height:300px; overflow-y:auto; z-index:1000; display:none;">
                            <!-- Client and Member options -->
                            <div id="clientOptions">
                                @foreach ($clients as $client)
                                    <div class="client-option-item" data-type="client" data-client-id="{{ $client->id }}"
                                        data-client-name="{{ $client->name }}"
                                        data-client-office="{{ $client->office ?? 'Not specified' }}"
                                        data-client-office-id="{{ $client->office_id }}"
                                        style="padding:12px 14px; cursor:pointer; border-bottom:1px solid #f1f5f9; transition:background 0.2s ease; font-size:14px; color:#374151;">
                                        <div style="font-weight:600; color:#1e40af;">{{ $client->name }}</div>
                                        <div style="font-size:12px; color:#64748b; margin-top:2px;">Client • Office:
                                            {{ $client->office ?? 'Not specified' }}</div>
                                    </div>
                                @endforeach
                                @foreach ($members as $member)
                                    <div class="client-option-item" data-type="member"
                                        data-client-id="{{ $member->client_id }}" data-member-id="{{ $member->id }}"
                                        data-client-name="{{ $member->name }}"
                                        data-client-office="{{ $member->client->office ?? 'non office member' }}"
                                        data-client-office-id="{{ $member->client->s_p_office_id }}"
                                        style="padding:12px 14px; cursor:pointer; border-bottom:1px solid #f1f5f9; transition:background 0.2s ease; font-size:14px; color:#374151;">
                                        <div style="font-weight:600; color:#059669;">{{ $member->name }}</div>
                                        <div style="font-size:12px; color:#64748b; margin-top:2px;">Member of
                                            {{ $member->client->name }} • Office:
                                            {{ $member->client->office->office ?? 'non office member' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="total">Quantity:</label>
                <input type="number" name="total" id="total" value="{{ old('total', 1) }}" min="1" required>
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Reason for
                    Request</label>
                <textarea name="reason" placeholder="Enter reason for this outbound request..."
                    style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05); resize:vertical; min-height:80px; outline:none;"></textarea>
            </div>

            {{-- Approval and Status are set automatically for admin-created outbounds --}}

            <div class="form-actions">
                <button type="submit" class="btn-submit">Add Outbound</button>
                <a href="{{ route('outbound.index') }}" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            /**@argument
             * ada
             **/

            const stockDropdown = document.getElementById('stockDropdown');
            const stockDropdownMenu = document.getElementById('stockDropdownMenu');
            const stockSearchInput = document.getElementById('stockSearchInput');
            const stockIdInput = document.getElementById('stockIdInput');
            const selectedStockText = document.getElementById('selectedStockText');
            const stockOptions = document.querySelectorAll('.stock-option-item');

            if (stockDropdown && stockDropdownMenu && stockSearchInput) {
                // Toggle dropdown when clicking on the trigger
                stockDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isOpen = stockDropdownMenu.style.display === 'block';
                    stockDropdownMenu.style.display = isOpen ? 'none' : 'block';
                    if (!isOpen) {
                        stockSearchInput.value = '';
                        stockSearchInput.focus();
                        resetStockOptions();
                    }
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!stockDropdown.contains(e.target) && !stockDropdownMenu.contains(e.target)) {
                        stockDropdownMenu.style.display = 'none';
                    }
                });

                // Search functionality
                stockSearchInput.addEventListener('input', function() {
                    const searchTerm = stockSearchInput.value.toLowerCase();

                    stockOptions.forEach(option => {
                        const stockText = option.dataset.stockText.toLowerCase();
                        if (stockText.includes(searchTerm)) {
                            option.style.display = 'block';
                        } else {
                            option.style.display = 'none';
                        }
                    });
                });

                // Handle option selection
                stockOptions.forEach(option => {
                    option.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const stockId = option.dataset.stockId;
                        const stockText = option.dataset.stockText;

                        stockIdInput.value = stockId;
                        selectedStockText.textContent = stockText;
                        stockDropdownMenu.style.display = 'none';

                        // Add hover effect
                        option.style.background = '#f8fafc';
                        setTimeout(() => {
                            option.style.background = '';
                        }, 200);
                    });

                    // Add hover effect
                    option.addEventListener('mouseenter', function() {
                        option.style.background = '#f8fafc';
                    });

                    option.addEventListener('mouseleave', function() {
                        option.style.background = '';
                    });
                });

                function resetStockOptions() {
                    stockOptions.forEach(option => {
                        option.style.display = 'block';
                    });
                }
            }

            /**@argument
             * OJT - Noli
             **/
            // Client dropdown functionality
            const clientDropdown = document.getElementById('clientDropdown');
            const clientDropdownMenu = document.getElementById('clientDropdownMenu');
            const clientIdInput = document.getElementById('clientIdInput');
            const officeInput = document.getElementById('officeInput');
            const officeDisplay = document.getElementById('officeDisplay');
            const clientOptions = document.querySelectorAll('.client-option-item');
            if (clientDropdown && clientDropdownMenu) {
                // Show dropdown when input receives focus
                clientDropdown.addEventListener('focus', function(e) {
                    clientDropdownMenu.style.display = 'block';
                    resetClientOptions();
                });

                // Handle input typing to show dropdown and filter results
                clientDropdown.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase();

                    if (searchTerm.length >= 1) {
                        clientDropdownMenu.style.display = 'block';

                        let hasMatches = false;
                        // Filter options based on input (both clients and members)
                        clientOptions.forEach(option => {
                            const clientName = option.dataset.clientName.toLowerCase();
                            const clientOffice = option.dataset.clientOffice.toLowerCase();
                            const type = option.dataset.type || 'client';
                            const searchText = clientName + ' ' + clientOffice + ' ' + type;

                            if (searchText.includes(searchTerm)) {
                                option.style.display = 'block';
                                hasMatches = true;
                            } else {
                                option.style.display = 'none';
                            }
                        });

                        // Handle non-member option
                        if (!hasMatches && searchTerm.length >= 2) {
                            addNonMemberOption();
                        } else {
                            removeNonMemberOption();
                        }
                    } else {
                        resetClientOptions();
                        removeNonMemberOption();
                    }
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!clientDropdown.contains(e.target) && !clientDropdownMenu.contains(e.target)) {
                        clientDropdownMenu.style.display = 'none';
                    }
                });

                // Helper functions for non-member option handling
                function addNonMemberOption() {
                    let nonMemberOption = document.getElementById('nonMemberOption');
                    if (!nonMemberOption) {
                        nonMemberOption = document.createElement('div');
                        nonMemberOption.id = 'nonMemberOption';
                        nonMemberOption.className = 'client-option-item';
                        nonMemberOption.style.cssText =
                            'padding:12px 14px; cursor:pointer; border-bottom:1px solid #f1f5f9; transition:background 0.2s ease; font-size:14px; color:#dc2626; font-weight:600;';
                        nonMemberOption.innerHTML = `
                    <div style="font-weight:600; color:#dc2626;">${clientDropdown.value}</div>
                    <div style="font-size:12px; color:#6b7280; margin-top:2px;">Non-member - Will create urgent recipient</div>
                `;

                        nonMemberOption.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const recipientName = clientDropdown.value;

                            // Clear client data and set as urgent recipient
                            clientIdInput.value = '';
                            officeInput.value = 'Not specified';
                            clientDropdown.value = recipientName;

                            // Add hidden fields for urgent recipient
                            let urgentNameInput = document.getElementById('urgent_recipient_name');
                            let urgentOfficeInput = document.getElementById('urgent_recipient_office');
                            let isUrgentInput = document.getElementById('is_urgent_outbound');

                            if (!urgentNameInput) {
                                urgentNameInput = document.createElement('input');
                                urgentNameInput.type = 'hidden';
                                urgentNameInput.id = 'urgent_recipient_name';
                                urgentNameInput.name = 'urgent_recipient_name';
                                clientDropdown.parentNode.appendChild(urgentNameInput);
                            }

                            if (!urgentOfficeInput) {
                                urgentOfficeInput = document.createElement('input');
                                urgentOfficeInput.type = 'hidden';
                                urgentOfficeInput.id = 'urgent_recipient_office';
                                urgentOfficeInput.name = 'urgent_recipient_office';
                                clientDropdown.parentNode.appendChild(urgentOfficeInput);
                            }

                            if (!isUrgentInput) {
                                isUrgentInput = document.createElement('input');
                                isUrgentInput.type = 'hidden';
                                isUrgentInput.id = 'is_urgent_outbound';
                                isUrgentInput.name = 'is_urgent_outbound';
                                isUrgentInput.value = 'true';
                                clientDropdown.parentNode.appendChild(isUrgentInput);
                            }

                            urgentNameInput.value = recipientName;
                            urgentOfficeInput.value = officeInput.value;

                            if (officeDisplay) {
                                officeDisplay.textContent = 'Non-member';
                                officeDisplay.style.color = '#dc2626';
                                officeDisplay.style.background = '#fef2f2';
                            }

                            clientDropdownMenu.style.display = 'none';
                        });

                        nonMemberOption.addEventListener('mouseenter', function() {
                            this.style.background = '#fef2f2';
                        });

                        nonMemberOption.addEventListener('mouseleave', function() {
                            this.style.background = '';
                        });

                        document.getElementById('clientOptions').appendChild(nonMemberOption);
                    } else {
                        nonMemberOption.querySelector('div').textContent = clientDropdown.value;
                    }
                    nonMemberOption.style.display = 'block';
                }

                function removeNonMemberOption() {
                    const nonMemberOption = document.getElementById('nonMemberOption');
                    if (nonMemberOption) {
                        nonMemberOption.style.display = 'none';
                    }
                }

                // Handle option selection
                clientOptions.forEach(option => {
                    option.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const type = option.dataset.type || 'client';
                        const clientId = option.dataset.clientId;
                        const clientName = option.dataset.clientName;
                        const clientOffice = option.dataset.clientOffice;
                        const memberId = option.dataset.memberId;
                        const officeId = option.dataset.clientOfficeId;

                        clientIdInput.value = clientId;
                        officeInput.value = clientOffice;
                        clientDropdown.value = clientName + ' - ' + clientOffice;
                        officeIdInput.value = officeId;
                        console.log({
                            officeId
                        })
                        // Handle member-specific logic
                        if (type === 'member' && memberId) {
                            // Add member_id hidden field
                            let memberInput = document.getElementById('member_id');
                            if (!memberInput) {
                                memberInput = document.createElement('input');
                                memberInput.type = 'hidden';
                                memberInput.id = 'member_id';
                                memberInput.name = 'member_id';
                                clientDropdown.parentNode.appendChild(memberInput);
                            }
                            memberInput.value = memberId;

                            // Add is_direct_request hidden field
                            let directRequestInput = document.getElementById('is_direct_request');
                            if (!directRequestInput) {
                                directRequestInput = document.createElement('input');
                                directRequestInput.type = 'hidden';
                                directRequestInput.id = 'is_direct_request';
                                directRequestInput.name = 'is_direct_request';
                                directRequestInput.value = 'true';
                                clientDropdown.parentNode.appendChild(directRequestInput);
                            }

                            // Disable office field for non-office members
                            if (clientOffice === 'non office member') {
                                officeInput.value = 'non office member';
                                officeInput.disabled = true;
                            }
                        } else {
                            // Remove member-specific fields for client selection
                            const memberInput = document.getElementById('member_id');
                            const directRequestInput = document.getElementById('is_direct_request');
                            if (memberInput) memberInput.remove();
                            if (directRequestInput) directRequestInput.remove();
                            officeInput.disabled = false;
                        }

                        clientDropdownMenu.style.display = 'none';

                        // Add hover effect
                        option.style.background = '#f8fafc';
                        setTimeout(() => {
                            option.style.background = '';
                        }, 200);
                    });

                    // Add hover effect
                    option.addEventListener('mouseenter', function() {
                        option.style.background = '#f8fafc';
                    });

                    option.addEventListener('mouseleave', function() {
                        option.style.background = '';
                    });
                });

                function checkAllocation(stock_id, office_id) {
                    // console.log(stock_id, office_id)
                    console.log("test");

                }

                function resetClientOptions() {
                    clientOptions.forEach(option => {
                        option.style.display = 'block';
                    });
                }
            }


        });
    </script>

@endsection
