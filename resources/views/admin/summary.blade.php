@extends('layouts.app')

@php
  $brand = 'Inventory System';
  $pageTitle = 'Summary';
@endphp

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('content')
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

        .pill{ 
            display:inline-block; 
            padding:4px 10px; 
            border-radius:999px; 
            font-size:12px; 
            font-weight:700; 
            border:1px solid var(--line);
            transition: all 0.3s ease;
        }
        .pill:hover{
            transform: scale(1.05);
        }
        .pill.pending{ 
            background:linear-gradient(180deg,var(--orange-soft),#fff7ed); 
            color:var(--orange); 
            border-color:rgba(249,115,22,.2);
        }
        .pill.pending:hover{
            box-shadow: 0 4px 12px rgba(249,115,22,.2);
        }
        .pill.approved, .pill.ready_to_receive{ 
            background:linear-gradient(180deg,#ecfdf5,#f0fdfa); 
            color:#065f46; 
            border-color:rgba(34,197,94,.2);
        }
        .pill.approved:hover, .pill.ready_to_receive:hover{
            box-shadow: 0 4px 12px rgba(34,197,94,.2);
        }
        .pill.released{ 
            background:linear-gradient(180deg,#eff6ff,#f0f9ff); 
            color:var(--blue); 
            border-color:rgba(37,99,235,.2);
        }
        .pill.released:hover{
            box-shadow: 0 4px 12px rgba(37,99,235,.2);
        }
        .pill.rejected{ 
            background:linear-gradient(180deg,#fee2e2,#fff1f2); 
            color:#991b1b; 
            border-color:rgba(244,63,94,.2);
        }
        .pill.rejected:hover{
            box-shadow: 0 4px 12px rgba(244,63,94,.2);
        }
        .pill.cancelled{ 
            background:linear-gradient(180deg,#f3f4f6,#f8fafc); 
            color:#475569; 
            border-color:rgba(226,232,240,.6);
        }
        .pill.cancelled:hover{
            box-shadow: 0 4px 12px rgba(71,81,105,.1);
        }

        .muted{ color:var(--muted); }
        .list{ list-style: disc inside; color:var(--muted); }
        .list li{ margin-bottom:6px; }

        .summary-table-wrapper {
            overflow-x:auto;
            border-radius:16px;
            box-shadow:0 8px 25px rgba(59,130,246,0.15);
            background:linear-gradient(135deg, #eff6ff, #dbeafe);
            margin-top:22px;
        }
        .summary-table {
            width:100%;
            border-collapse:collapse;
            font-family:Inter, system-ui, sans-serif;
        }
        .summary-table th,
        .summary-table td {
            padding:12px 10px;
            border:1px solid #e0e7ff;
            text-align:left;
            font-size:14px;
        }
        .summary-table th {
            border-color:#1e40af;
            border-bottom:2px solid #1e40af;
            background:linear-gradient(135deg, #3b82f6, #1d4ed8);
            color:#ffffff;
            font-weight:700;
            min-width:140px;
        }
        .summary-table th:nth-child(1) { min-width:280px; }
        .summary-table th:nth-child(2) { min-width:150px; }
        .summary-table th:nth-child(3),
        .summary-table th:nth-child(4),
        .summary-table th:nth-child(5),
        .summary-table th:nth-child(6) { min-width:120px; }
        .summary-table tbody tr { background:#ffffff; }
        .summary-table tbody tr:hover { background:#f8fbff; }
        .summary-table td.numeric { text-align:right; color:#475569; font-weight:600; }
        .summary-table td .summary-label { color:#1e40af; font-weight:700; font-size:14px; }
        .summary-table td .summary-text { color:#334155; font-size:14px; }
    </style>

    <div class="card-body" style="position:relative;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
            <div>
                <div class="card-title">Summary Filters</div>
            </div>

            <form id="filterForm" method="GET" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-top:4px;">
                        <input
                            id="filterQuery"
                            type="text"
                            name="q"
                            value="{{ $q ?? '' }}"
                            placeholder="Search item # or description"
                            style="padding:8px 10px; border-radius:10px; border:1px solid var(--line); background:#fff; min-width:200px;"
                        />

                        <select
                            id="filterOffice"
                            name="office"
                            style="padding:8px 10px; border-radius:10px; border:1px solid var(--line); background:#fff; min-width:180px;"
                        >
                            <option value="">All Offices</option>
                            @foreach($offices as $off)
                                <option value="{{ $off }}" {{ $off === ($office ?? '') ? 'selected' : '' }}>{{ $off }}</option>
                            @endforeach
                        </select>

                        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                            <div style="display:flex; flex-direction:column; min-width:150px;">
                                <label for="date_from" style="font-size:12px; color:var(--muted); margin-bottom:4px;">From</label>
                                <input
                                    id="date_from"
                                    type="date"
                                    name="date_from"
                                    value="{{ $dateFrom ?? '' }}"
                                    style="padding:8px 10px; border-radius:10px; border:1px solid var(--line); background:#fff;"
                                />
                            </div>
                            <div style="display:flex; flex-direction:column; min-width:150px;">
                                <label for="date_to" style="font-size:12px; color:var(--muted); margin-bottom:4px;">To</label>
                                <input
                                    id="date_to"
                                    type="date"
                                    name="date_to"
                                    value="{{ $dateTo ?? '' }}"
                                    style="padding:8px 10px; border-radius:10px; border:1px solid var(--line); background:#fff;"
                                />
                            </div>
                        </div>

                        <a
                            href="{{ route('admin.summary.report.pdf') }}?{{ http_build_query(request()->only(['q', 'office', 'date_from', 'date_to'])) }}"
                            class="btn-submit"
                            style="padding:10px 14px; border-radius:10px; background:#2563eb; color:#fff; text-decoration:none; font-weight:700;"
                        >
                            Download PDF
                        </a>
                    </form>
                </div>

                <div class="summary-table-wrapper">
                    <table class="summary-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Starting Balance</th>
                                <th>Inbound</th>
                                <th>Sum</th>
                                <th>Outbound</th>
                                <th>Ending Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                                @forelse($stockSummaries as $row)
                                    <tr>
                                        <td>{{ $row['item'] }} @if($row['id_no']) <span class="muted">({{ $row['id_no'] }})</span>@endif</td>
                                        <td class="numeric">{{ number_format($row['starting_balance']) }}</td>
                                        <td class="numeric">{{ number_format($row['inbound']) }}</td>
                                        <td class="numeric">{{ number_format($row['sum']) }}</td>
                                        <td class="numeric">{{ number_format($row['outbound']) }}</td>
                                        <td class="numeric">{{ number_format($row['ending_balance']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align:center; padding:20px;">No stock records found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

    <script>
        // Auto-update filters without needing a button.
        (function(){
            const input = document.getElementById('filterQuery');
            const select = document.getElementById('filterOffice');
            const dateFrom = document.getElementById('date_from');
            const dateTo = document.getElementById('date_to');
            const form = document.getElementById('filterForm');

            if (!form) return;

            let timeout;
            const submit = () => {
                const params = new URLSearchParams();
                const q = input?.value?.trim();
                const office = select?.value?.trim();
                const from = dateFrom?.value?.trim();
                const to = dateTo?.value?.trim();

                if (q) params.set('q', q);
                if (office) params.set('office', office);
                if (from) params.set('date_from', from);
                if (to) params.set('date_to', to);

                const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                window.location.href = url;
            };

            input?.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(submit, 500);
            });

            select?.addEventListener('change', submit);
            dateFrom?.addEventListener('change', submit);
            dateTo?.addEventListener('change', submit);
        })();
    </script>
@endsection
