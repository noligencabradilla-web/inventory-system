@extends('layouts.app')

@php
    $brand = 'Inventory System';
    $pageTitle = 'Admin Panel';

    $quickLinks = [
        [
            'title' => 'Stocks',
            'url' => url('/admin/stocks'),
            'badge' => null,
        ],
        [
            'title' => 'Requests',
            'url' => url('/admin/requests'),
            'badge' => $pendingRequests ?? 0,
        ],
        [
            'title' => 'Password Reset',
            'url' => url('/admin/password-reset'),
            'badge' => $pendingPasswordResets ?? 0,
        ],
    ];

    $chartCards = [
        [
            'type' => 'category',
            'id' => 'categoryCard',
            'canvas' => 'categoryChart',
            'loading' => 'categoryLoading',
            'title' => 'Category Analytics',
            'subtitle' => 'Stock availability vs. approved requests by category',
            'icon' => '📊',
            'summary' => null,
        ],
        [
            'type' => 'office',
            'id' => 'officeCard',
            'canvas' => 'officeChart',
            'loading' => 'officeLoading',
            'title' => 'Requests by Office',
            'subtitle' => 'Which offices submit the most requests',
            'icon' => '🏢',
            'summary' => 'officeTop',
        ],
        [
            'type' => 'items',
            'id' => 'itemsCard',
            'canvas' => 'itemsChart',
            'loading' => 'itemsLoading',
            'title' => 'Most Requested Items',
            'subtitle' => 'Top 10 items by number of request lines',
            'icon' => '🔥',
            'summary' => 'itemsTop',
        ],
        [
            'type' => 'lowStock',
            'id' => 'lowStockCard',
            'canvas' => 'lowStockChart',
            'loading' => 'lowStockLoading',
            'title' => 'Low Stock Items',
            'subtitle' => 'Categories with items at or below the low-stock threshold',
            'icon' => '⚠️',
            'summary' => 'lowStockTop',
        ],
        [
            'type' => 'outStock',
            'id' => 'outStockCard',
            'canvas' => 'outStockChart',
            'loading' => 'outStockLoading',
            'title' => 'Out of Stock Items',
            'subtitle' => 'Zero-balance items grouped by category',
            'icon' => '🚫',
            'summary' => 'outStockTop',
        ],
    ];

    $dashboardChartData = [
        'chartEndpoint' => url('/admin/dashboard/chart-data'),
        'categories' => $categoryAnalytics ?? [],
        'offices' => $officeAnalytics ?? [],
        'items' => $itemAnalytics ?? [],
        'lowStock' => $lowStockAnalytics ?? [],
        'outStock' => $outStockAnalytics ?? [],
        'monthlyConsumption' => $monthlyConsumptionAnalytics ?? [],
        'stockMovement' => $stockMovementAnalytics ?? [],
        'fastMoving' => $fastMovingAnalytics ?? [],
        'officeQuantity' => $officeQuantityAnalytics ?? [],
        'pendingAging' => $pendingAgingAnalytics ?? [],
    ];
@endphp

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('content')
    <h2 class="page-heading">Welcome, {{ auth()->user()->name }}</h2>

    <div class="quick-grid">
        @foreach ($quickLinks as $quickLink)
            <div class="quick-card">
                <div class="eyebrow">Quick Access</div>

                <div class="quick-title">
                    <span>{{ $quickLink['title'] }}</span>

                    @if (!empty($quickLink['badge']) && $quickLink['badge'] > 0)
                        <span class="badge">{{ $quickLink['badge'] }}</span>
                    @endif
                </div>

                <a href="{{ $quickLink['url'] }}" class="quick-link">Open →</a>
            </div>
        @endforeach
    </div>

    <style>
        .page-heading { margin: 0 0 10px; }

        .quick-grid,
        .charts-grid {
            display: grid;
            gap: 12px;
        }

        .quick-grid {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            margin-top: 14px;
        }

        .quick-card,
        .chart-card {
            border: 1px solid rgba(255, 255, 255, .08);
            background: rgba(255, 255, 255, .02);
        }

        .quick-card {
            padding: 14px;
            border-radius: 14px;
        }

        .eyebrow {
            color: #9ca3af;
            font-size: 12px;
        }

        .quick-title {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 6px;
            font-weight: 700;
        }

        .badge {
            display: inline-block;
            min-width: 24px;
            padding: 2px 8px;
            border-radius: 999px;
            background: #ef4444;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
        }

        .quick-link {
            display: inline-block;
            margin-top: 10px;
            color: #22c55e;
            text-decoration: none;
        }

        .charts-grid {
            grid-template-columns: repeat(3, 1fr);
            align-items: start;
            gap: 20px;
            margin-top: 32px;
        }

        @media (max-width: 1200px) {
            .charts-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 900px) {
            .charts-grid { grid-template-columns: 1fr; }
        }

        .chart-card {
            position: relative;
            overflow: hidden;
            padding: 20px;
            border-radius: 16px;
            background: linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .01));
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .chart-card:hover {
            border-color: rgba(59, 130, 246, .3);
            box-shadow: 0 20px 40px rgba(59, 130, 246, .15);
            transform: translateY(-6px);
        }

        .chart-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
        }

        .chart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .chart-title {
            margin: 0;
            color: #000;
            font-size: 18px;
            font-weight: 800;
        }

        .chart-sub {
            margin: 0;
            color: #9ca3af;
            font-size: 13px;
        }

        .chart-icon {
            font-size: 24px;
            opacity: .8;
        }

        .chart-body {
            position: relative;
            width: 100%;
            height: 320px;
        }

        .chart-summary {
            margin-top: 12px;
            color: #9ca3af;
            font-size: 13px;
            text-align: center;
        }

        .summary-main { color: #000; }
        .summary-sub { color: #9ca3af; font-size: 11px; }

        .chart-loading-overlay {
            position: absolute;
            inset: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: rgba(255, 255, 255, .9);
            opacity: 0;
            visibility: hidden;
            transition: opacity .3s ease, visibility .3s ease;
        }

        .chart-loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f4f6;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .loading-text {
            position: absolute;
            bottom: 20px;
            left: 50%;
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
            transform: translateX(-50%);
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .chart-modal {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: auto;
            background: rgba(0, 0, 0, .7);
            backdrop-filter: blur(8px);
        }

        .chart-modal.hidden { display: none; }

        .modal-content {
            position: relative;
            width: 95vw;
            max-width: 1400px;
            max-height: 90vh;
            padding: 24px;
            overflow: auto;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 25px 50px rgba(0, 0, 0, .3);
        }

        .close-btn {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 40px;
            height: 40px;
            border: 0;
            border-radius: 50%;
            background: #f3f4f6;
            cursor: pointer;
            font-size: 20px;
            line-height: 0;
            transition: transform .2s ease, background .2s ease;
        }

        .close-btn:hover {
            background: #e5e7eb;
            transform: scale(1.1);
        }

        .modal-controls {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .modal-label {
            margin: 0;
            color: #374151;
            font-size: 14px;
            font-weight: 600;
        }

        .modal-input {
            min-width: 140px;
            padding: 6px 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        .modal-btn {
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .modal-btn-primary {
            border: 1px solid #3b82f6;
            background: #3b82f6;
            color: #fff;
        }

        .modal-btn-secondary {
            border: 1px solid #6b7280;
            background: #f3f4f6;
            color: #374151;
        }

        .modal-message {
            position: absolute;
            top: 50%;
            left: 50%;
            z-index: 2;
            display: none;
            color: #6b7280;
            font-size: 18px;
            text-align: center;
            pointer-events: none;
            transform: translate(-50%, -50%);
        }

        .modal-message.active { display: block; }
        .modal-chart-wrapper { overflow-x: auto; }

        #modalChart {
            width: 100%;
            height: 70vh;
        }
    </style>

    <div class="charts-grid" id="chartsGrid">
        @foreach ($chartCards as $chart)
            <div class="chart-card" id="{{ $chart['id'] }}" data-chart-type="{{ $chart['type'] }}">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title">{{ $chart['title'] }}</h3>
                        <div class="chart-sub">{{ $chart['subtitle'] }}</div>
                    </div>
                    <div class="chart-icon">{{ $chart['icon'] }}</div>
                </div>

                <div class="chart-body">
                    <canvas id="{{ $chart['canvas'] }}"></canvas>
                    <div class="chart-loading-overlay" id="{{ $chart['loading'] }}">
                        <div class="spinner"></div>
                        <div class="loading-text">Loading data...</div>
                    </div>
                </div>

                @if (!empty($chart['summary']))
                    <div id="{{ $chart['summary'] }}" class="chart-summary"></div>
                @endif
            </div>
        @endforeach
    </div>

    <div id="chartModal" class="chart-modal hidden" aria-hidden="true">
        <div class="modal-content" role="dialog" aria-modal="true" aria-label="Chart details">
            <button id="closeModal" class="close-btn" type="button" aria-label="Close modal">&times;</button>

            <div class="modal-controls">
                <label for="startDateModal" class="modal-label">Start Date:</label>
                <input type="date" id="startDateModal" class="modal-input">

                <label for="endDateModal" class="modal-label">End Date:</label>
                <input type="date" id="endDateModal" class="modal-input">

                <button id="applyModalFilter" type="button" class="modal-btn modal-btn-primary">Apply</button>
                <button id="resetModalFilter" type="button" class="modal-btn modal-btn-secondary">Reset</button>
            </div>

            <div id="modalMessage" class="modal-message">No data for selected date range</div>

            <div id="modalChartWrapper" class="modal-chart-wrapper">
                <canvas id="modalChart"></canvas>
            </div>
        </div>
    </div>

    <script id="dashboardChartData" type="application/json">
        {!! json_encode($dashboardChartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dashboardChartData = JSON.parse(
                document.getElementById('dashboardChartData').textContent
            );

            const chartEndpoint = dashboardChartData.chartEndpoint;
            const analytics = normalizeData(dashboardChartData);
            const today = new Date();
            const defaultStartDate = new Date(today.getFullYear(), today.getMonth(), 1);
            const charts = {};
            const modalState = { type: null, instance: null };

            const elements = {
                modal: document.getElementById('chartModal'),
                modalCanvas: document.getElementById('modalChart'),
                modalWrapper: document.getElementById('modalChartWrapper'),
                modalMessage: document.getElementById('modalMessage'),
                startDate: document.getElementById('startDateModal'),
                endDate: document.getElementById('endDateModal'),
                closeModal: document.getElementById('closeModal'),
                applyFilter: document.getElementById('applyModalFilter'),
                resetFilter: document.getElementById('resetModalFilter'),
                officeTop: document.getElementById('officeTop'),
                itemsTop: document.getElementById('itemsTop'),
                lowStockTop: document.getElementById('lowStockTop'),
                outStockTop: document.getElementById('outStockTop'),
                fastMovingTop: document.getElementById('fastMovingTop'),
                officeQuantityTop: document.getElementById('officeQuantityTop'),
            };

            const chartBuilders = {
                category: buildCategoryChart,
                office: buildOfficeChart,
                items: buildItemsChart,
                lowStock: buildLowStockChart,
                outStock: buildOutStockChart,
                requestStatus: buildRequestStatusChart,
                monthlyConsumption: buildMonthlyConsumptionChart,
                stockMovement: buildStockMovementChart,
                fastMoving: buildFastMovingChart,
                officeQuantity: buildOfficeQuantityChart,
                pendingAging: buildPendingAgingChart,
            };

            initCharts();
            renderSummaries(analytics);
            bindEvents();

            function initCharts() {
                Object.keys(chartBuilders).forEach((type) => {
                    const canvasId = `${type}Chart`;
                    charts[type] = createChart(canvasId, type, analytics);
                });
            }

            function createChart(canvasId, type, data) {
                const canvas = document.getElementById(canvasId);
                if (!canvas || !chartBuilders[type]) return null;

                const ctx = canvas.getContext('2d');
                return new Chart(ctx, chartBuilders[type](ctx, data));
            }

            function bindEvents() {
                document.querySelectorAll('[data-chart-type]').forEach((card) => {
                    card.addEventListener('click', () => openModal(card.dataset.chartType));
                });

                elements.closeModal.addEventListener('click', closeModal);
                elements.applyFilter.addEventListener('click', applyModalFilter);
                elements.resetFilter.addEventListener('click', resetModalFilter);

                elements.modal.addEventListener('click', (event) => {
                    if (event.target === elements.modal) closeModal();
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !elements.modal.classList.contains('hidden')) {
                        closeModal();
                    }
                });

                window.addEventListener('resize', () => {
                    if (modalState.instance) modalState.instance.resize();
                });
            }

            function buildCategoryChart(ctx, data) {
                return barChart({
                    labels: data.categories.map((category) => category.name),
                    datasets: [
                        dataset('Available Stock', data.categories.map((category) => numberValue(category.availability)), greenGradient(ctx)),
                        dataset('Approved Requests', data.categories.map((category) => numberValue(category.requested)), blueGradient(ctx)),
                    ],
                    xTitle: 'Categories',
                    yTitle: 'Quantity',
                });
            }

            function buildOfficeChart(ctx, data) {
                return horizontalBarChart({
                    labels: data.offices.map((office) => office.office),
                    datasets: [
                        dataset(
                            'Requests',
                            data.offices.map((office) => numberValue(office.count)),
                            'rgba(249, 115, 22, .60)'
                        )
                    ],
                    xTitle: 'Number of Requests',
                    yTitle: 'Offices',
                    shortYAxis: true,
                    yTickLimit: 26,
                    tooltipTitle: (context) => context[0].label,
                    tooltipLabel: (context) => `${context.parsed.x} request(s)`,
                });
            }

            function buildItemsChart(ctx, data) {
                return horizontalBarChart({
                    labels: data.items.map((item) => `${item.id_no} - ${item.description}`),
                    datasets: [
                        dataset(
                            'Request Count',
                            data.items.map((item) => numberValue(item.total_requested)),
                            'rgba(59, 130, 246, .65)'
                        )
                    ],
                    xTitle: 'Times Requested',
                    yTitle: 'Items',
                    shortYAxis: true,
                    yTickLimit: 34,
                    tooltipTitle: (context) => {
                        const item = data.items[context[0].dataIndex];
                        return item ? `${item.id_no} - ${item.description}` : '';
                    },
                    tooltipLabel: (context) => {
                        const item = data.items[context.dataIndex];

                        return item
                            ? [
                                `Requested: ${context.parsed.x} time(s)`,
                                `Category: ${item.category}`,
                                `Unit: ${item.unit}`,
                            ]
                            : `${context.parsed.x} request(s)`;
                    },
                });
            }

            function buildLowStockChart(ctx, data) {
                return horizontalBarChart({
                    labels: data.lowStock.map((row) => row.category),
                    datasets: [
                        dataset(
                            'Low Stock Items',
                            data.lowStock.map((row) => numberValue(row.total)),
                            'rgba(249, 115, 22, .60)'
                        )
                    ],
                    xTitle: 'Number of Items',
                    yTitle: 'Category',
                    shortYAxis: true,
                    yTickLimit: 28,
                    tooltipTitle: (context) => context[0].label,
                    tooltipLabel: (context) => {
                        const row = data.lowStock[context.dataIndex];
                        const threshold = row?.threshold ? `Threshold: ${row.threshold}` : 'Threshold: 49';

                        return [
                            `${context.parsed.x} low stock item(s)`,
                            threshold,
                        ];
                    },
                });
            }

            function buildOutStockChart(ctx, data) {
                return horizontalBarChart({
                    labels: data.outStock.map((row) => row.category),
                    datasets: [
                        dataset(
                            'Out of Stock Items',
                            data.outStock.map((row) => numberValue(row.total)),
                            'rgba(239, 68, 68, .60)'
                        )
                    ],
                    xTitle: 'Number of Items',
                    yTitle: 'Category',
                    shortYAxis: true,
                    yTickLimit: 28,
                    tooltipTitle: (context) => context[0].label,
                    tooltipLabel: (context) => `${context.parsed.x} out of stock item(s)`,
                });
            }

            function buildRequestStatusChart(ctx, data) {
                return {
                    type: 'doughnut',
                    data: {
                        labels: data.requestStatus.map((row) => row.status),
                        datasets: [{
                            label: 'Requests',
                            data: data.requestStatus.map((row) => numberValue(row.total)),
                            backgroundColor: palette(data.requestStatus.length),
                            borderColor: 'rgba(255, 255, 255, .8)',
                            borderWidth: 2,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: animationOptions(1000),
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { color: '#000', font: { size: 12, weight: '600' } },
                            },
                            tooltip: tooltipOptions(),
                        },
                    },
                };
            }

            function buildMonthlyConsumptionChart(ctx, data) {
                return lineChart({
                    labels: data.monthlyConsumption.map((row) => row.month || row.label),
                    datasets: [{
                        label: 'Approved Quantity',
                        data: data.monthlyConsumption.map((row) => numberValue(row.total ?? row.approved_quantity)),
                        backgroundColor: 'rgba(59, 130, 246, .25)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 3,
                        pointRadius: 4,
                        tension: .35,
                        fill: true,
                    }],
                    xTitle: 'Month',
                    yTitle: 'Approved Quantity',
                });
            }

            function buildStockMovementChart(ctx, data) {
                const hasMonthlyFormat = data.stockMovement.some((row) => {
                    return Object.prototype.hasOwnProperty.call(row, 'stock_in')
                        || Object.prototype.hasOwnProperty.call(row, 'stock_out');
                });

                if (hasMonthlyFormat) {
                    return barChart({
                        labels: data.stockMovement.map((row) => row.month || row.label),
                        datasets: [
                            dataset('Stock In', data.stockMovement.map((row) => numberValue(row.stock_in)), greenGradient(ctx)),
                            dataset('Stock Out', data.stockMovement.map((row) => numberValue(row.stock_out)), blueGradient(ctx)),
                        ],
                        xTitle: 'Month',
                        yTitle: 'Quantity',
                    });
                }

                return barChart({
                    labels: data.stockMovement.map((row) => row.label),
                    datasets: [
                        dataset('Quantity', data.stockMovement.map((row) => numberValue(row.total)), 'rgba(34, 197, 94, .60)'),
                    ],
                    xTitle: 'Movement',
                    yTitle: 'Quantity',
                });
            }

            function buildFastMovingChart(ctx, data) {
                return horizontalBarChart({
                    labels: data.fastMoving.map((item) => `${item.id_no} - ${item.description}`),
                    datasets: [
                        dataset(
                            'Released Quantity',
                            data.fastMoving.map((item) => movementQuantity(item)),
                            'rgba(20, 184, 166, .65)'
                        )
                    ],
                    xTitle: 'Released Quantity',
                    yTitle: 'Items',
                    shortYAxis: true,
                    yTickLimit: 34,
                    tooltipTitle: (context) => {
                        const item = data.fastMoving[context[0].dataIndex];
                        return item ? `${item.id_no} - ${item.description}` : '';
                    },
                    tooltipLabel: (context) => {
                        const item = data.fastMoving[context.dataIndex];

                        return item
                            ? [
                                `Quantity: ${context.parsed.x}`,
                                `Category: ${item.category}`,
                                `Unit: ${item.unit}`,
                            ]
                            : `${context.parsed.x}`;
                    },
                });
            }

            function buildOfficeQuantityChart(ctx, data) {
                return horizontalBarChart({
                    labels: data.officeQuantity.map((office) => office.office),
                    datasets: [
                        dataset(
                            'Approved Quantity',
                            data.officeQuantity.map((office) => numberValue(office.total_quantity ?? office.quantity)),
                            'rgba(168, 85, 247, .60)'
                        )
                    ],
                    xTitle: 'Approved Quantity',
                    yTitle: 'Offices',
                    shortYAxis: true,
                    yTickLimit: 26,
                    tooltipTitle: (context) => context[0].label,
                    tooltipLabel: (context) => `${context.parsed.x} approved item(s)`,
                });
            }

            function buildPendingAgingChart(ctx, data) {
                return barChart({
                    labels: data.pendingAging.map((row) => row.range || row.label),
                    datasets: [
                        dataset(
                            'Pending Requests',
                            data.pendingAging.map((row) => numberValue(row.total)),
                            'rgba(249, 115, 22, .60)'
                        )
                    ],
                    xTitle: 'Age Range',
                    yTitle: 'Pending Requests',
                    tooltipLabel: (context) => `${context.parsed.y} pending request(s)`,
                });
            }

            function barChart({ labels, datasets, xTitle, yTitle, tooltipTitle = null, tooltipLabel = null, rotateX = false }) {
                return {
                    type: 'bar',
                    data: { labels, datasets },
                    options: commonOptions({ xTitle, yTitle, tooltipTitle, tooltipLabel, rotateX }),
                };
            }

            function horizontalBarChart({
                labels,
                datasets,
                xTitle,
                yTitle,
                tooltipTitle = null,
                tooltipLabel = null,
                shortYAxis = false,
                yTickLimit = 24,
            }) {
                const options = commonOptions({ xTitle, yTitle, tooltipTitle, tooltipLabel });
                options.indexAxis = 'y';
                options.scales.x = chartAxis(xTitle);
                options.scales.y = shortYAxis ? shortLabelAxis(yTitle, yTickLimit) : chartAxis(yTitle, false);

                return {
                    type: 'bar',
                    data: { labels, datasets },
                    options,
                };
            }

            function lineChart({ labels, datasets, xTitle, yTitle }) {
                return {
                    type: 'line',
                    data: { labels, datasets },
                    options: commonOptions({ xTitle, yTitle }),
                };
            }

            function dataset(label, data, backgroundColor) {
                return {
                    label,
                    data,
                    backgroundColor,
                    borderColor: Array.isArray(backgroundColor)
                        ? 'rgba(255, 255, 255, .25)'
                        : backgroundColor,
                    borderWidth: 1,
                    borderRadius: 8,
                    borderSkipped: false,
                    barPercentage: .75,
                };
            }

            function commonOptions({ xTitle, yTitle, tooltipTitle = null, tooltipLabel = null, rotateX = false }) {
                const tooltip = tooltipOptions();

                if (tooltipTitle || tooltipLabel) {
                    tooltip.callbacks = {};
                    if (tooltipTitle) tooltip.callbacks.title = tooltipTitle;
                    if (tooltipLabel) tooltip.callbacks.label = tooltipLabel;
                }

                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: animationOptions(1000),
                    plugins: {
                        legend: {
                            labels: {
                                color: '#000',
                                font: { size: 12, weight: '600' },
                                padding: 15,
                            },
                        },
                        tooltip,
                    },
                    scales: {
                        x: rotateX ? rotatedAxis(xTitle) : chartAxis(xTitle, false),
                        y: chartAxis(yTitle),
                    },
                    interaction: { intersect: false, mode: 'index' },
                };
            }

            function chartAxis(title, showGrid = true) {
                return {
                    beginAtZero: true,
                    ticks: {
                        color: '#000',
                        font: { size: 11 },
                        precision: 0,
                    },
                    grid: {
                        display: showGrid,
                        color: 'rgba(0, 0, 0, .08)',
                    },
                    title: {
                        display: true,
                        text: title,
                        color: '#000',
                        font: { size: 12, weight: '600' },
                    },
                };
            }

            function shortLabelAxis(title, limit = 24) {
                return {
                    ...chartAxis(title, false),
                    ticks: {
                        color: '#000',
                        font: { size: 10 },
                        callback: function(value) {
                            const label = this.getLabelForValue(value);
                            return truncate(label, limit);
                        },
                    },
                };
            }

            function rotatedAxis(title) {
                return {
                    ...chartAxis(title, false),
                    ticks: {
                        color: '#000',
                        font: { size: 10 },
                        maxRotation: 45,
                        minRotation: 45,
                    },
                };
            }

            function tooltipOptions() {
                return {
                    backgroundColor: 'rgba(0, 0, 0, .8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, .2)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    padding: 12,
                };
            }

            function animationOptions(duration) {
                return { duration, easing: 'easeOutQuart' };
            }

            function palette(count) {
                const colors = [
                    'rgba(59, 130, 246, .75)',
                    'rgba(34, 197, 94, .75)',
                    'rgba(249, 115, 22, .75)',
                    'rgba(239, 68, 68, .75)',
                    'rgba(168, 85, 247, .75)',
                    'rgba(20, 184, 166, .75)',
                    'rgba(234, 179, 8, .75)',
                ];

                return Array.from({ length: Math.max(count, 1) }, (_, index) => colors[index % colors.length]);
            }

            function greenGradient(ctx) {
                return verticalGradient(ctx, [[0, 'rgba(34, 197, 94, .6)'], [.5, 'rgba(34, 197, 94, .4)'], [1, 'rgba(34, 197, 94, .3)']]);
            }

            function blueGradient(ctx) {
                return verticalGradient(ctx, [[0, 'rgba(59, 130, 246, .6)'], [.5, 'rgba(59, 130, 246, .4)'], [1, 'rgba(59, 130, 246, .3)']]);
            }

            function verticalGradient(ctx, stops) {
                return gradient(ctx, 0, 0, 0, 320, stops);
            }

            function gradient(ctx, x0, y0, x1, y1, stops) {
                const canvasGradient = ctx.createLinearGradient(x0, y0, x1, y1);
                stops.forEach(([offset, color]) => canvasGradient.addColorStop(offset, color));
                return canvasGradient;
            }

            function renderSummaries(data) {
                const topOffice = data.offices[0];
                const topItem = data.items[0];
                const topLowStock = data.lowStock[0];
                const totalOutStock = data.outStock.reduce((sum, row) => sum + numberValue(row.total), 0);
                const topFastMoving = data.fastMoving[0];
                const topOfficeQuantity = data.officeQuantity[0];

                renderSummary(
                    elements.officeTop,
                    topOffice ? `Top: ${topOffice.office}` : null,
                    topOffice ? `${numberValue(topOffice.count)} request(s)` : null
                );

                renderSummary(
                    elements.itemsTop,
                    topItem ? `Top: ${topItem.id_no} - ${topItem.description}` : null,
                    topItem ? `Requested ${numberValue(topItem.total_requested)} time(s)` : null
                );

                renderSummary(
                    elements.lowStockTop,
                    topLowStock ? `Top category: ${topLowStock.category}` : null,
                    topLowStock ? `${numberValue(topLowStock.total)} low stock item(s)` : null
                );

                renderSummary(
                    elements.outStockTop,
                    totalOutStock > 0 ? `${totalOutStock} item(s) out of stock` : null,
                    totalOutStock > 0 ? 'Grouped by category' : null
                );

                renderSummary(
                    elements.fastMovingTop,
                    topFastMoving ? `Top: ${topFastMoving.id_no} - ${topFastMoving.description}` : null,
                    topFastMoving ? `${movementQuantity(topFastMoving)} released item(s)` : null
                );

                renderSummary(
                    elements.officeQuantityTop,
                    topOfficeQuantity ? `Top: ${topOfficeQuantity.office}` : null,
                    topOfficeQuantity ? `${numberValue(topOfficeQuantity.total_quantity ?? topOfficeQuantity.quantity)} approved item(s)` : null
                );
            }

            function renderSummary(element, title, subtitle) {
                if (!element) return;
                element.replaceChildren();

                if (!title) {
                    element.textContent = 'No data available';
                    return;
                }

                const strong = document.createElement('strong');
                strong.className = 'summary-main';
                strong.textContent = title;

                const lineBreak = document.createElement('br');

                const span = document.createElement('span');
                span.className = 'summary-sub';
                span.textContent = subtitle || '';

                element.append(strong, lineBreak, span);
            }

            function openModal(type) {
                if (!chartBuilders[type]) return;

                modalState.type = type;
                elements.startDate.value = formatDate(defaultStartDate);
                elements.endDate.value = formatDate(today);
                elements.modal.classList.remove('hidden');
                elements.modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';

                renderModalChart(analytics);
            }

            function closeModal() {
                destroyModalChart();
                modalState.type = null;
                elements.modal.classList.add('hidden');
                elements.modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
                hideModalMessage();
            }

            function applyModalFilter() {
                fetchModalData(elements.startDate.value, elements.endDate.value);
            }

            function resetModalFilter() {
                elements.startDate.value = formatDate(defaultStartDate);
                elements.endDate.value = formatDate(today);
                renderModalChart(analytics);
            }

            async function fetchModalData(startDate, endDate) {
                if (!startDate || !endDate) {
                    showModalMessage('Please select both start and end dates.');
                    return;
                }

                showModalMessage('Loading chart data...');

                try {
                    const params = new URLSearchParams({ start_date: startDate, end_date: endDate });
                    const response = await fetch(`${chartEndpoint}?${params.toString()}`);

                    if (!response.ok) throw new Error(`Request failed with status ${response.status}`);

                    renderModalChart(normalizeData(await response.json()));
                } catch (error) {
                    console.error('Error loading modal chart:', error);
                    showModalMessage('Error loading chart data.');
                }
            }

            function renderModalChart(data) {
                destroyModalChart();

                if (!hasChartData(modalState.type, data)) {
                    clearModalCanvas();
                    showModalMessage('No data for selected date range.');
                    return;
                }

                hideModalMessage();
                resizeModalCanvas(getDataCount(modalState.type, data));

                const ctx = elements.modalCanvas.getContext('2d');
                modalState.instance = new Chart(ctx, chartBuilders[modalState.type](ctx, data));
                modalState.instance.resize();
            }

            function destroyModalChart() {
                if (!modalState.instance) return;
                modalState.instance.destroy();
                modalState.instance = null;
            }

            function resizeModalCanvas(labelsCount) {
                const wrapperWidth = elements.modalWrapper.clientWidth || 900;
                elements.modalCanvas.style.width = `${Math.max(wrapperWidth, labelsCount * 105)}px`;
                elements.modalWrapper.scrollLeft = 0;
            }

            function clearModalCanvas() {
                const ctx = elements.modalCanvas.getContext('2d');
                ctx.clearRect(0, 0, elements.modalCanvas.width, elements.modalCanvas.height);
            }

            function showModalMessage(message) {
                elements.modalMessage.textContent = message;
                elements.modalMessage.classList.add('active');
            }

            function hideModalMessage() {
                elements.modalMessage.classList.remove('active');
            }

            function hasChartData(type, data) {
                return getDataCount(type, data) > 0;
            }

            function getDataCount(type, data) {
                const map = {
                    category: data.categories,
                    office: data.offices,
                    items: data.items,
                    lowStock: data.lowStock,
                    outStock: data.outStock,
                    requestStatus: data.requestStatus,
                    monthlyConsumption: data.monthlyConsumption,
                    stockMovement: data.stockMovement,
                    fastMoving: data.fastMoving,
                    officeQuantity: data.officeQuantity,
                    pendingAging: data.pendingAging,
                };

                return Array.isArray(map[type]) ? map[type].length : 0;
            }

            function normalizeData(data) {
                return {
                    categories: Array.isArray(data.categories) ? data.categories : [],
                    offices: Array.isArray(data.offices) ? data.offices : [],
                    items: Array.isArray(data.items) ? data.items : [],
                    lowStock: Array.isArray(data.lowStock) ? data.lowStock : [],
                    outStock: Array.isArray(data.outStock) ? data.outStock : [],
                    requestStatus: Array.isArray(data.requestStatus) ? data.requestStatus : [],
                    monthlyConsumption: Array.isArray(data.monthlyConsumption) ? data.monthlyConsumption : [],
                    stockMovement: Array.isArray(data.stockMovement) ? data.stockMovement : [],
                    fastMoving: Array.isArray(data.fastMoving) ? data.fastMoving : [],
                    officeQuantity: Array.isArray(data.officeQuantity) ? data.officeQuantity : [],
                    pendingAging: Array.isArray(data.pendingAging) ? data.pendingAging : [],
                };
            }

            function numberValue(value) {
                const number = Number(value);
                return Number.isFinite(number) ? number : 0;
            }

            function movementQuantity(item) {
                return numberValue(item.total_quantity ?? item.total_released ?? item.total_requested);
            }

            function truncate(value, length) {
                const text = String(value || '');
                return text.length > length ? `${text.substring(0, length)}...` : text;
            }

            function formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');

                return `${year}-${month}-${day}`;
            }
        });
    </script>

@endsection