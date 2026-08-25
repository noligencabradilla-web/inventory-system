@extends('layouts.app')

@php
    $brand = 'Inventory System';
    $pageTitle = 'Accounts';
    $pageSubtitle = 'Manage and create user accounts.';
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
            background: linear-gradient(135deg, #2563eb, #1e40af);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
            border-color: rgba(59, 130, 246, 0.5);
        }

        .btn-link:hover::after {
            left: 100%;
        }

        .btn-link:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.2);
        }

        .btn-create-account {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
            border-color: rgba(59, 130, 246, 0.5);
        }

        .btn-create-account:hover {
            background: linear-gradient(135deg, #2563eb, #1e40af) !important;
            transform: translateY(-3px) !important;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3) !important;
            border-color: rgba(59, 130, 246, 0.5) !important;
        }

        .btn-create-account:hover::after {
            left: 100% !important;
        }

        .btn-create-account:active {
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.2) !important;
        }

        /* Modal button hover effects - Higher specificity */
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

        .table-wrap {
            overflow-x: auto;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #e0e7ff;
            padding: 10px;
            text-align: left;
        }

        th {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #ffffff;
            font-weight: 700;
            font-size: 12px;
            border-bottom: 2px solid #1e40af;
        }

        td {
            color: #475569;
            font-size: 13px;
            border-bottom: 1px solid #e0e7ff;
        }

        .muted {
            color: var(--muted);
        }

        .btn-action {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            margin-right: 6px;
        }

        .btn-edit {
            background: rgba(37, 99, 235, .15);
            color: var(--blue);
            border: 1px solid rgba(37, 99, 235, .3);
            transition: all 0.3s ease;
        }

        .btn-edit:hover {
            background: rgba(37, 99, 235, .25);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(37, 99, 235, .15);
        }

        .btn-edit:active {
            transform: translateY(0);
        }

        .btn-delete {
            background: rgba(220, 38, 38, .15);
            color: var(--danger);
            border: 1px solid rgba(220, 38, 38, .3);
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background: rgba(220, 38, 38, .25);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(220, 38, 38, .15);
        }

        .btn-delete:active {
            transform: translateY(0);
        }

        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            border: 1px solid;
        }

        .alert-success {
            background: rgba(22, 163, 74, .1);
            border-color: rgba(22, 163, 74, .3);
            color: var(--success);
        }
    </style>

    <div class="toolbar">
        <h2 style="margin:0;">Client Accounts</h2>
        <button type="button" onclick="openCreateModal()" class="btn-create-account"
            style="display:flex; align-items:center; gap:8px; padding:12px 20px; border-radius:12px; border:2px solid transparent; background:linear-gradient(135deg, #3b82f6, #1d4ed8); color:#ffffff; text-decoration:none; font-weight:700; transition:all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow:0 4px 12px rgba(59,130,246,0.2); position:relative; overflow:hidden; transform:translateY(0);">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="9" r="2"></circle>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            Create new account
            <span
                style="position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg, transparent, rgba(255,255,255,0.2)); transition:left 0.3s ease;"></span>
        </button>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="min-width:200px;">Name</th>
                    <th style="min-width:220px;">Email</th>
                    <th style="min-width:160px;">Office</th>
                    {{-- <th style="min-width:120px;">Role</th> --}}
                    <th style="min-width:140px;">Member Since</th>
                    <th style="min-width:160px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr style="border-bottom:1px solid #e0e7ff; background:linear-gradient(135deg, #ffffff, #f8fafc);">
                        <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff;">
                            <div style="font-weight:700; color:#1e40af; font-size:14px;">{{ $user->name }}</div>
                        </td>
                        <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; color:#64748b; font-size:14px;">
                            {{ $user->email }}</td>
                        <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; color:#64748b; font-size:14px;">
                            {{ $user->office_name ?? '-' }}</td>
                        {{-- <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; color:#475569; font-weight:600;">
                            {{ $user->role ?? '-' }}</td> --}}
                        <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff; color:#475569; font-weight:600;">
                            {{ $user->created_at->format('M d, Y') }}</td>
                        <td style="padding:14px 10px; border-bottom:1px solid #e0e7ff;">
                            <button type="button"
                                onclick="openEditModal({{ $user->id }}, '{{ $user->name }}', '{{ $user->email }}', '{{ $user->s_p_office_id ?? '' }}', '{{ $user->role ?? '' }}')"
                                class="btn-action btn-edit"
                                style="padding:8px 16px; border-radius:8px; border:2px solid #3b82f6; background:linear-gradient(135deg, #3b82f6, #1d4ed8); color:#ffffff; font-weight:700; transition:all 0.3s ease; box-shadow:0 4px 12px rgba(59,130,246,0.2);">Edit</button>
                            <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}"
                                style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action btn-delete"
                                    style="padding:8px 16px; border-radius:8px; border:2px solid #ef4444; background:linear-gradient(135deg, #ef4444, #dc2626); color:#ffffff; font-weight:700; transition:all 0.3s ease; box-shadow:0 4px 12px rgba(239,68,68,0.2);"
                                    onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr style="background:linear-gradient(135deg, #f8fafc, #f1f5f9);">
                        <td colspan="6" style="padding:20px 10px; text-align:center; color:#64748b; font-size:14px;">No
                            client accounts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal"
        style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,.5); z-index:999; justify-content:center; align-items:center;">
        <div
            style="background:#ffffff; border-radius:16px; padding:24px; width:480px; max-width:95%; box-shadow:0 18px 40px rgba(2,6,23,.2);">
            <h3 style="margin:0 0 20px 0; font-size:18px; font-weight:800; color:#1e293b;">Edit User Account</h3>

            <form id="editUserForm" method="POST">
                @csrf
                @method('PUT')

                <div style="margin-bottom:16px;">
                    <label
                        style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Name</label>
                    <input type="text" name="name" id="editName" required
                        style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05);">
                </div>

                <div style="margin-bottom:16px;">
                    <label
                        style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Email</label>
                    <input type="email" name="email" id="editEmail" required
                        style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05);">
                </div>

                {{-- <div style="margin-bottom:16px;">
                    <label
                        style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Office</label>
                    <input type="text" name="office" id="editOffice"
                        style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05);">
                </div> --}}

                <div style="margin-bottom:20px;">
                    <label
                        style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Role</label>
                    <select name="role" id="editRole"
                        style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05);">
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="client">Client</option>
                    </select>
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Office
                        / Department (optional)</label>
                    {{-- <input type="text" name="office" id="createOffice" placeholder="e.g. Purchasing" style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05);"> --}}
                    <select name="s_p_office_id" id="editOffice"
                        style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05); text-transform: capitalize;">
                        <option value="null">Select Office</option>
                        @foreach ($spOffices as $office)
                            <option class="capitalize" value="{{ $office->id }}">{{ $office->office }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display:flex; gap:12px;">
                    <button type="submit"
                        style="flex:1; padding:12px 20px; border-radius:10px; border:2px solid #3b82f6; background:linear-gradient(135deg, #3b82f6, #1d4ed8); color:#ffffff; font-weight:700; transition:all 0.3s ease; box-shadow:0 4px 12px rgba(59,130,246,0.2);">Save
                        Changes</button>
                    <button type="button" onclick="closeEditModal()"
                        style="flex:1; padding:12px 20px; border-radius:10px; border:2px solid #e2e8f0; background:#ffffff; color:#64748b; font-weight:600; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05);">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(userId, name, email, s_p_office_id, role) {
            const modal = document.getElementById('editUserModal');
            const form = document.getElementById('editUserForm');

            // Set form action
            form.action = '/admin/users/' + userId;

            // Populate form fields
            document.getElementById('editName').value = name;
            document.getElementById('editEmail').value = email;
            document.getElementById('editOffice').value = s_p_office_id;
            document.getElementById('editRole').value = role;

            // Show modal
            modal.style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('editUserModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>

    <!-- Create User Modal -->
    <div id="createUserModal"
        style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,.5); z-index:9999; justify-content:center; align-items:center;">
        <div
            style="background:#ffffff; border-radius:16px; padding:24px; width:520px; max-width:95%; box-shadow:0 18px 40px rgba(2,6,23,.2);">
            <h3 style="margin:0 0 20px 0; font-size:18px; font-weight:800; color:#1e293b;">Create New Account</h3>

            <form id="createUserForm" method="POST" action="{{ route('admin.users.store') }}">
                @csrf

                <div style="margin-bottom:16px;">
                    <label style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Full
                        Name</label>
                    <input type="text" name="name" id="createName" placeholder="Enter client full name" required
                        style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05);">
                </div>

                <div style="margin-bottom:16px;">
                    <label style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Email
                        Address</label>
                    <input type="email" name="email" id="createEmail" placeholder="Enter email address" required
                        style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05);">
                </div>

                <div style="margin-bottom:16px;">
                    <label
                        style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Password</label>
                    <div style="position:relative;">
                        <input type="password" name="password" id="createPassword"
                            placeholder="Enter password (min 6 characters)" required
                            style="width:100%; padding:12px 45px 12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05);">
                        <button type="button" onclick="togglePassword('createPassword', 'createPasswordIcon')"
                            id="createPasswordIcon"
                            style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:#64748b; cursor:pointer; font-size:14px; padding:4px; border-radius:4px; transition:all 0.3s ease;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label
                        style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Confirm
                        Password</label>
                    <div style="position:relative;">
                        <input type="password" name="password_confirmation" id="createPasswordConfirm"
                            placeholder="Confirm password" required
                            style="width:100%; padding:12px 45px 12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05);">
                        <button type="button"
                            onclick="togglePassword('createPasswordConfirm', 'createPasswordConfirmIcon')"
                            id="createPasswordConfirmIcon"
                            style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:#64748b; cursor:pointer; font-size:14px; padding:4px; border-radius:4px; transition:all 0.3s ease;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label
                        style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Role</label>
                    <select name="role" id="createRole" required
                        style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05);">
                        <option value="client">Client</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div style="margin-bottom:20px;">
                    <label style="display:block; margin-bottom:8px; font-weight:700; color:#374151; font-size:14px;">Office
                        / Department (optional)</label>
                    {{-- <input type="text" name="office" id="createOffice" placeholder="e.g. Purchasing" style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05);"> --}}
                    <select name="s_p_office_id" id="createOffice"
                        style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; color:#374151; background:#ffffff; transition:all 0.3s ease; box-shadow:0 1px 3px rgba(15,23,42,.05); text-transform: capitalize;">
                        <option value="null">Select Office</option>
                        @foreach ($spOffices as $office)
                            <option class="capitalize" value="{{ $office->id }}">{{ $office->office }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display:flex; gap:12px;">
                    <button type="submit" class="modal-btn-primary"
                        style="flex:1; padding:12px 20px; border-radius:10px; border:2px solid #3b82f6; background:linear-gradient(135deg, #3b82f6, #1d4ed8); color:#ffffff; font-weight:700; transition:all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow:0 4px 12px rgba(59,130,246,0.2); position:relative; overflow:hidden; transform:translateY(0); pointer-events:auto;">
                        <span style="position:relative; z-index:1;">Create Account</span>
                        <span
                            style="position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg, transparent, rgba(255,255,255,0.2)); transition:left 0.3s ease;"></span>
                    </button>
                    <button type="button" onclick="closeCreateModal()" class="modal-btn-secondary"
                        style="flex:1; padding:12px 20px; border-radius:10px; border:2px solid #e2e8f0; background:#ffffff; color:#64748b; font-weight:600; transition:all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow:0 1px 3px rgba(15,23,42,.05); position:relative; overflow:hidden; transform:translateY(0); pointer-events:auto;">
                        <span style="position:relative; z-index:1;">Cancel</span>
                        <span
                            style="position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg, transparent, rgba(59,130,246,0.1)); transition:left 0.3s ease;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('createUserModal').style.display = 'flex';
        }

        function closeCreateModal() {
            document.getElementById('createUserModal').style.display = 'none';
        }

        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 0 5.06 5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 0-5.06 5.94M15 12h3m-6 0h.01"></path>
                <line x1="2" y1="2" x2="22" y2="22"></line>
            </svg>
        `;
            } else {
                input.type = 'password';
                icon.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
        `;
            }
        }

        // Close modal when clicking outside
        document.getElementById('createUserModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCreateModal();
            }
        });
    </script>
@endsection
