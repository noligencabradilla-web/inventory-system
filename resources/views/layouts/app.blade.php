<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ?? 'Inventory System' }}</title>

    <style>
    :root{
        --bg: #f8fafc;                 /* page background (white-ish) */
        --panel: #ffffff;              /* cards / sidebar */
        --panel2: #f1f5f9;             /* subtle secondary panel */

        --text: #0f172a;               /* main text (dark blue) */
        --muted: #475569;              /* secondary text */

        --line: #e2e8f0;               /* borders */

        --blue: #2563eb;               /* primary blue */
        --blue-soft: #eff6ff;

        --orange: #f97316;             /* accent orange */
        --orange-soft: #fff7ed;

        --success: #16a34a;
        --danger: #dc2626;
    }


    *{ box-sizing:border-box; }

    body{
        margin:0;
        font-family: Arial, Helvetica, sans-serif;
        background: var(--bg);
        color: var(--text);
    }
    

    .app{
        display:flex;
        min-height:100vh;
    }

    /* Sidebar */
    .sidebar{
        width:260px;
        background: linear-gradient(180deg, var(--panel), var(--panel2));
        border-right:1px solid var(--line);
        padding:18px 14px;
        position:sticky;
        top:0;
        height:100vh;
        overflow:auto;

        /* hide scrollbar while retaining scroll behavior */
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE 10+ */
    }
    .sidebar::-webkit-scrollbar{ display: none; }

    .brand{
        padding:12px 12px 8px;
        border-bottom:1px solid var(--line);
        margin-bottom:8px;
    }

    .brand .name{
        font-weight:700;
        font-size:16px;
        color:var(--blue);
    }

    .brand .role{
        color:var(--muted);
        font-size:12px;
        margin-top:6px;
    }

    .nav{
        margin-top:12px;
        display:flex;
        flex-direction:column;
        gap:8px;
    }

    .nav a{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        text-decoration:none;
        color:var(--text);
        padding:11px 12px;
        min-height:44px;
        border-radius:10px;
        border:1px solid transparent;
        background:#ffffff;
        white-space:nowrap;
        transition:transform .16s ease, box-shadow .18s ease, background-color .18s ease, border-color .18s ease;
    }

    /* Logout-like tab (red accent) */
    .nav a.logout-link{
        color:#dc2626;
        border-color:rgba(220,38,38,0.2);
        background:linear-gradient(90deg, rgba(254,202,202,0.35), rgba(254,226,226,0.15));
        font-weight:700;
        justify-content:center;
        padding:10px;
    }
    .nav a.logout-link svg{ width:18px; height:18px; margin:0; }
    .nav a.logout-link span{ display:none; }
    .nav a.logout-link:hover{ box-shadow:0 12px 30px rgba(220,38,38,0.12); border-color:rgba(220,38,38,0.3); color:#b91c1c; background:linear-gradient(90deg, rgba(254,202,202,0.45), rgba(254,226,226,0.25)); }

    /* Hover / focus: subtle lift, shadow and left accent using primary blue */
    .nav a:hover,
    .nav a:focus{
        box-shadow:0 12px 30px rgba(2,6,23,0.06);
        border-color:rgba(37,99,235,0.12);
        background:linear-gradient(90deg,var(--blue-soft),#f8fbff);
        color:var(--blue);
    }

    /* Keyboard focus-visible for accessibility */
    .nav a:focus-visible{ outline:3px solid rgba(37,99,235,0.12); outline-offset:2px; }

    /* 🔵 ACTIVE TAB */
    .nav a.active{
        border-color:var(--blue);
        background:var(--blue-soft);
        color:var(--blue);
        font-weight:600;
        box-shadow: inset 0 -2px rgba(37,99,235,0.04);
        transform:none; /* keep active tab from lifting */
    }

    /* Hide footer logout on wide screens (we'll show logout in the nav instead) */
    @media (min-width: 641px){
        .sidebar-footer{ display:none; }
        .sidebar.client-sidebar{
            display:flex;
            flex-direction:column;
        }
        .sidebar.client-sidebar .nav{
            display:flex;
            flex-direction:column;
            gap:8px;
            flex:1;
            overflow:auto;
            scrollbar-width:none;
        }
        .sidebar.client-sidebar .nav::-webkit-scrollbar{ display:none; }
        .sidebar.client-sidebar .nav a.logout-link{
            margin-top:auto;
            width:100%;
            justify-content:center;
        }
        
        /* Position logout button at bottom for admin sidebar */
        .sidebar:not(.client-sidebar){
            display:flex;
            flex-direction:column;
        }
        .sidebar:not(.client-sidebar) .nav{
            display:flex;
            flex-direction:column;
            gap:8px;
            flex:1;
            overflow:auto;
            scrollbar-width:none;
        }
        .sidebar:not(.client-sidebar) .nav::-webkit-scrollbar{ display:none; }
        .sidebar:not(.client-sidebar) .nav a.logout-link{
            margin-top:auto;
            width:100%;
            justify-content:center;
        }
    }

    /* MOBILE: better responsive spacing and full-width nav on small screens */
    @media (max-width: 640px) {
        .sidebar {
            width: 100%;
            height: auto;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            padding: 12px 10px;
            border-right: none;
            border-bottom: 1px solid var(--line);
            background: var(--panel);
        }

        .sidebar .brand { 
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 10px;
        }

        .sidebar .nav{
            display:flex;
            flex-direction:column;
            gap:6px;
            max-height: calc(100vh - 240px);
            overflow-y:auto;
            padding-right: 4px;
            padding-bottom: 85px; /* space for fixed logout */
        }

        .sidebar .nav a {
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 15px;
            min-height: 42px;
        }

        .sidebar .nav a.logout-link{
            order: 99;
            position: fixed;
            bottom: 12px;
            left: 12px;
            right: 12px;
            z-index: 1500;
            margin: 0;
            justify-content:center;
            width: auto;
            display:flex;
            align-items:center;
            border-radius: 12px;
            background: #fee2e2;
            border-color: #fca5a5;
        }

        .sidebar .nav a.logout-link span{ display:none; }

        .sidebar .sidebar-footer {
            display:flex;
            justify-content:center;
            align-items:center;
            padding: 10px;
            border-top: none;
            background: transparent;
            position: fixed;
            bottom: 12px;
            left: 12px;
            right: 12px;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(15,23,42,.08);
            z-index: 1500;
            max-width: calc(100% - 24px);
            width: auto;
        }

        .sidebar .sidebar-footer .logout-btn {
            width: 46px;
            height: 46px;
            margin: 0;
            padding: 0;
            border-radius: 50%;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .sidebar .nav a.logout-link{ display:none; }

        .main {
            margin-top: 220px;
        }
    }

    .nav small{
        color:var(--muted);
        font-size:11px;
    }

    .sidebar-footer{
        margin-top:18px;
        padding-top:14px;
        border-top:1px solid var(--line);
        position: absolute;
        left: 14px;
        right: 14px;
        bottom: 18px;
    }

    .logout-btn{
        width:48px;
        height:48px;
        padding:0;
        border-radius:50%;
        border:1px solid #dc2626;
        background: #ffe4e6;
        color:#dc2626;
        font-weight:600;
        cursor:pointer;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:18px;
        line-height:1;
    }

    .logout-btn:hover{
        background:#fecdd3;
        color:#991b1b;
        border-color:#991b1b;

    }

    /* Hamburger Menu Button (hidden on desktop) */
    .sidebar-toggle{
        display:none;
        position:fixed;
        top:12px;
        left:12px;
        z-index:10002;
        width:40px;
        height:40px;
        padding:0;
        border:none;
        background:var(--blue);
        color:#fff;
        border-radius:8px;
        cursor:pointer;
        align-items:center;
        justify-content:center;
        box-shadow:0 4px 12px rgba(37,99,235,0.3);
        transition:transform .2s ease, box-shadow .2s ease;
    }

    .sidebar-toggle:hover{
        transform:scale(1.05);
        box-shadow:0 6px 16px rgba(37,99,235,0.4);
    }

    .sidebar-toggle:active{
        transform:scale(0.98);
    }

    .sidebar-overlay{
        display:none;
        position:fixed;
        inset:0;
        background:rgba(0,0,0,.5);
        z-index:9999;
    }

    /* Main */
    .main{
        flex:1;
        padding:22px;
    }

    

    .topbar{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        padding:14px 16px;
        background:#ffffff;
        border:1px solid var(--line);
        border-radius:14px;
        margin-bottom:16px;
    }

    .topbar .title{
        font-size:16px;
        font-weight:700;
        color:var(--blue);
    }

    .topbar .sub{
        color:var(--muted);
        font-size:12px;
        margin-top:4px;
    }

    .content{
        position: relative;
        background: #ffffff;
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 16px;
        min-height: calc(100vh - 22px - 16px - 16px - 22px);
        overflow: hidden;
        z-index: 0;
    }

    .content::before{
        content: '';
        position: fixed;
        top: 50%;
        left: 50%;
        width: 55vw;
        height: 55vw;
        max-width: 740px;
        max-height: 740px;
        transform: translate(-50%, -50%);
        background-image: url('/images/logo.png.png');
        background-repeat: no-repeat;
        background-position: center center;
        background-size: contain;
        opacity: 0.08;
        pointer-events: none;
        filter: blur(2px);
        z-index: -1;
    }

    /* FLASH MESSAGES */
    .flash-success{
        padding:10px 12px;
        border-radius:10px;
        border:1px solid var(--success);
        background:#ecfdf5;
        color:var(--success);
        margin-bottom:12px;
        transition: opacity .35s ease, transform .2s ease;
    }

    .flash-error{
        padding:10px 12px;
        border-radius:10px;
        border:1px solid var(--orange);
        background:var(--orange-soft);
        color:var(--orange);
        margin-bottom:12px;
    }

    /* RESPONSIVE DESIGN */
    @media (max-width: 768px){
        .app{
            flex-direction:column;
        }

        /* Show hamburger menu on tablet/mobile */
        .sidebar-toggle{
            display:flex;
        }

        .sidebar{
            width:100%;
            height:auto;
            position:fixed;
            top:0;
            left:0;
            right:0;
            bottom:0;
            border-right:none;
            border-bottom:none;
            padding:12px 8px;
            overflow:auto;
            z-index:10001;
            transform:translateX(-110%);
            transition:transform .3s ease;
            max-height:100vh;
        }

        /* Show sidebar when it has the "show" class */
        .sidebar.show{
            transform:translateX(0);
        }

        /* Show overlay when sidebar is shown */
        .sidebar-overlay.show{
            display:block;
        }

        .brand{
            padding:8px 8px 12px;
            margin-bottom:10px;
        }

        .brand .name{
            font-size:14px;
        }

        .brand .role{
            font-size:11px;
        }

        .nav{
            display:flex;
            flex-direction:row;
            flex-wrap:wrap;
            gap:6px;
        }

        .nav a{
            flex:1 1 calc(50% - 3px);
            min-width:120px;
            min-height:40px;
            padding:8px 10px;
            font-size:13px;
        }

        .nav a small{
            display:none;
        }

        .main{
            padding:12px;
            flex:1;
        }

        .topbar{
            flex-direction:column;
            align-items:flex-start;
            gap:8px;
            padding:10px 12px;
            margin-bottom:12px;
            margin-top:40px;
        }

        .topbar .title{
            font-size:14px;
        }

        .topbar .sub{
            font-size:11px;
        }

        .topbar > div:last-child{
            width:100%;
            display:flex;
            justify-content:space-between;
            align-items:center;
            font-size:11px;
        }

        .content{
            padding:12px;
            min-height:auto;
        }

        /* On small screens make logout appear inline in the sidebar so it's visible */
        .sidebar-footer{
            position: relative;
            left: auto;
            right: auto;
            bottom: auto;
            margin-top:18px;
            padding-top:14px;
            border-top:1px solid var(--line);
        }
    }

    @media (max-width: 480px){
        .sidebar-toggle{
            display:flex;
        }

        .sidebar{
            width:100%;
            padding:8px 4px;
        }

        .brand{
            padding:6px 6px 10px;
        }

        .brand .name{
            font-size:12px;
        }

        .nav a{
            flex:1 1 100%;
            min-width:auto;
        }

        .main{
            padding:8px;
            margin-top:40px;
        }

        .topbar{
            padding:8px 10px;
            margin-bottom:8px;
            margin-top:0;
        }

        .topbar .title{
            font-size:13px;
        }

        .content{
            padding:10px;
        }
    }

    /* RESPONSIVE TABLES FOR MOBILE */
    @media (max-width: 768px){
        table{
            font-size:13px;
        }

        table thead{
            font-size:12px;
        }

        table td, table th{
            padding:8px 6px;
        }

        /* Make tables scrollable on mobile */
        .table-wrapper, .table-container{
            overflow-x:auto;
            -webkit-overflow-scrolling:touch;
        }
    }

    @media (max-width: 480px){
        table{
            font-size:12px;
        }

        table td, table th{
            padding:6px 4px;
        }
    }

    /* RESPONSIVE FORMS FOR MOBILE */
    @media (max-width: 768px){
        .form-group, .form-wrapper, form > div{
            margin-bottom:12px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        input[type="date"],
        input[type="file"],
        select,
        textarea{
            width:100%;
            max-width:none;
            padding:10px 12px;
            box-sizing:border-box;
        }

        label{
            display:block;
            margin-bottom:6px;
            font-weight:600;
            font-size:13px;
        }

        /* Stack button groups vertically on tablet */
        .button-group, .form-actions, .action-buttons{
            display:flex;
            flex-direction:column;
            gap:8px;
            margin-top:12px;
        }

        .button-group button,
        .form-actions button,
        .action-buttons button,
        .button-group a,
        .form-actions a,
        .action-buttons a{
            width:100%;
            padding:10px 12px;
            font-size:13px;
        }
    }

    @media (max-width: 480px){
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        input[type="date"],
        input[type="file"],
        select,
        textarea{
            padding:8px 10px;
            font-size:14px;
        }

        label{
            font-size:12px;
            margin-bottom:4px;
        }

        .button-group button,
        .form-actions button,
        .action-buttons button,
        .button-group a,
        .form-actions a,
        .action-buttons a{
            padding:8px 10px;
            font-size:12px;
        }
    }

    /* RESPONSIVE MODALS FOR MOBILE */
    @media (max-width: 768px){
        .modal, [id$="Modal"], [class*="modal"]{
            max-width:90vw !important;
            width:90vw !important;
            margin:auto;
        }

        .modal-content, .modal-body, .modal-header, .modal-footer{
            padding:12px !important;
        }

        .modal-header{
            gap:8px !important;
        }

        .modal-title, .modal h2{
            font-size:16px;
        }

        .modal-body{
            max-height:60vh;
            overflow-y:auto;
        }

        .modal-footer{
            flex-direction:column;
            gap:8px;
        }

        .modal-footer button, .modal-footer a{
            width:100%;
        }
    }

    @media (max-width: 480px){
        .modal, [id$="Modal"], [class*="modal"]{
            max-width:95vw !important;
            width:95vw !important;
        }

        .modal-content, .modal-body, .modal-header, .modal-footer{
            padding:10px !important;
        }

        .modal-title, .modal h2{
            font-size:14px;
        }
    }

    /* RESPONSIVE DROPDOWNS & SELECTS FOR MOBILE */
    @media (max-width: 768px){
        select{
            font-size:14px;
            padding:10px;
        }

        .dropdown, .select-wrapper{
            width:100%;
        }
    }

    /* RESPONSIVE BUTTONS FOR MOBILE */
    @media (max-width: 768px){
        button, .btn, a.btn, [role="button"]{
            font-size:13px;
            padding:10px 12px;
            min-height:36px;
            min-width:36px;
        }

        button:active, .btn:active, a.btn:active{
            transform:scale(0.98);
        }
    }

    @media (max-width: 480px){
        button, .btn, a.btn, [role="button"]{
            font-size:12px;
            padding:8px 10px;
            min-height:40px;
            min-width:40px;
        }
    }

    /* RESPONSIVE GRID LAYOUTS FOR MOBILE */
    @media (max-width: 768px){
        .grid, .grid-2, .grid-3, .grid-4{
            grid-template-columns:1fr !important;
            gap:12px !important;
        }

        .flex-row{
            flex-direction:column;
            gap:8px;
        }

        .flex-wrap{
            flex-wrap:nowrap;
            overflow-x:auto;
        }
    }

    @media (max-width: 480px){
        .grid, .grid-2, .grid-3, .grid-4{
            gap:8px !important;
        }

        .flex-row{
            gap:6px;
        }
    }

    /* RESPONSIVE TEXT FOR MOBILE */
    @media (max-width: 768px){
        h1{ font-size:20px; }
        h2{ font-size:18px; }
        h3{ font-size:16px; }
        h4{ font-size:14px; }
        p{ font-size:13px; }
    }

    @media (max-width: 480px){
        h1{ font-size:18px; }
        h2{ font-size:16px; }
        h3{ font-size:14px; }
        h4{ font-size:12px; }
        p{ font-size:12px; }
    }

    /* RESPONSIVE SPACING FOR MOBILE */
    @media (max-width: 768px){
        .mb-4, .my-4{ margin-bottom:12px !important; }
        .mb-3, .my-3{ margin-bottom:10px !important; }
        .mt-4{ margin-top:12px !important; }
        .mt-3{ margin-top:10px !important; }
        .p-4{ padding:12px !important; }
        .p-3{ padding:10px !important; }
    }

    @media (max-width: 480px){
        .mb-4, .my-4{ margin-bottom:8px !important; }
        .mb-3, .my-3{ margin-bottom:6px !important; }
        .mt-4{ margin-top:8px !important; }
        .mt-3{ margin-top:6px !important; }
        .p-4{ padding:8px !important; }
        .p-3{ padding:6px !important; }
    }

    /* Hide sidebar logout on mobile screens (we'll show logout in the footer instead) */
    @media (max-width: 640px){
        #sidebar-logout{ display:none; }
    }

    /* Global loading overlay */
    #global-loading{ display:none; position:fixed; inset:0; background:rgba(0,0,0,.65); z-index:9999; align-items:center; justify-content:center; }
    #global-loading .loading-box{ background: transparent; padding:40px; border-radius:12px; display:flex; flex-direction:column; gap:24px; align-items:center; box-shadow:none; }
    #global-loading .spinner{ width:60px; height:60px; border-radius:50%; border:4px solid transparent; border-top-color:var(--blue); border-right-color:var(--blue); animation: spin 1.2s linear infinite; }
    @keyframes spin{ to { transform: rotate(360deg); } }
    #global-loading .msg{ color:#fff; font-weight:700; font-size:16px; text-align:center; }
</style>

</head>

<body>

<!-- Hamburger Menu Toggle Button (Mobile Only) -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle navigation menu" aria-expanded="false">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</button>

<!-- Overlay for Mobile Sidebar -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app">

    <aside class="sidebar @if(auth()->user()->role === 'client') client-sidebar @endif" id="sidebar">
        <div class="brand">
            <div style="display: flex; flex-direction: column; align-items: center; gap: 4px; margin-bottom: 8px;">
                <img src="/images/brand-logo.png" alt="Logo" style="height: 122; object-fit: contain;">
                <div class="name" style="text-align: center;">{{ $brand ?? 'Inventory System' }}</div>
            </div>
            <div class="role" style="position: relative; text-align: center;">
                Logged in as: <b>{{ auth()->user()->name ?? 'User' }}</b>
            </div>
        </div>

        <nav class="nav">
            {{-- Put your links here from child pages --}}
            @yield('sidebar')

            {{-- Sidebar logout tab (shows confirmation + icon) --}}
            <a href="#" class="logout-link" id="sidebar-logout" aria-haspopup="dialog" title="Logout">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2v10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M5.5 8.5a7 7 0 1 0 13 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Logout</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <form method="POST" action="{{ route('logout') }}" onsubmit="showLoading('Logging out...')">
                @csrf
                <button class="logout-btn" type="submit" aria-label="Logout">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2v10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M5.5 8.5a7 7 0 1 0 13 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </form>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div>
                @if(($pageTitle ?? 'Dashboard') === 'Client Portal')
                    <div>
                        <div style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 2px;">Client Portal</div>
                        <div style="font-size: 11px; color: #64748b; font-weight: 400;">Dashboard</div>
                    </div>
                @elseif(($pageTitle ?? 'Dashboard') === 'Transaction History')
                    <div>
                        <div style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 2px;">Transaction History</div>
                        <div style="font-size: 11px; color: #64748b; font-weight: 400;">Records</div>
                    </div>
                @elseif(($pageTitle ?? 'Dashboard') === 'My Inventory')
                    <div>
                        <div style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 2px;">My Inventory</div>
                        <div style="font-size: 11px; color: #64748b; font-weight: 400;">Stock items</div>
                    </div>
                @elseif(($pageTitle ?? 'Dashboard') === 'Report')
                    <div>
                        <div style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 2px;">Report</div>
                        <div style="font-size: 11px; color: #64748b; font-weight: 400;">Analytics</div>
                    </div>
                @elseif(($pageTitle ?? 'Dashboard') === 'Members')
                    <div>
                        <div style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 2px;">Members</div>
                        <div style="font-size: 11px; color: #64748b; font-weight: 400;">User management</div>
                    </div>
                @elseif(($pageTitle ?? 'Dashboard') === 'Offices')
                    <div>
                        <div style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 2px;">Offices</div>
                        <div style="font-size: 11px; color: #64748b; font-weight: 400;">Subaccounts</div>
                    </div>
                @elseif(($pageTitle ?? 'Dashboard') === 'Account Settings')
                    <div>
                        <div style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 2px;">Account Settings</div>
                        <div style="font-size: 11px; color: #64748b; font-weight: 400;">Profile</div>
                    </div>
                @elseif(($pageTitle ?? 'Dashboard') === 'Available Stocks')
                    <div>
                        <div style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 2px;">Available Stocks</div>
                        <div style="font-size: 11px; color: #64748b; font-weight: 400;">Stock catalog</div>
                    </div>
                @elseif(($pageTitle ?? 'Dashboard') === 'My Requests')
                    <div>
                        <div style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 2px;">My Requests</div>
                        <div style="font-size: 11px; color: #64748b; font-weight: 400;">Request status</div>
                    </div>
                @else
                    <div class="title">{{ $pageTitle ?? 'Dashboard' }}</div>
                @endif
            </div>
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="color: var(--muted); font-size:12px;">{{ now()->format('M d, Y h:i A') }}</div>
                @if(auth()->user()->role === 'admin')
                    @include('partials.top-notifications')
                @elseif(auth()->user()->role === 'client')
                    @include('partials.client-notifications')
                @endif
            </div>
        </div>

        <section class="content">
                @if(session('success'))
                    <div class="flash-success flash-auto">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="flash-error">{{ session('error') }}</div>
            @endif

            @yield('content')
        </section>
    </main>

</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    setTimeout(function(){
        document.querySelectorAll('.flash-auto').forEach(function(el){
            el.style.transition = 'opacity .45s ease, transform .25s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-6px)';
            setTimeout(function(){ if(el.parentNode) el.parentNode.removeChild(el); }, 500);
        });
    }, 3000);
});
</script>

<!-- Logout confirmation modal (triggered from sidebar) -->
<div id="logoutConfirmModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:12000; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; box-shadow:0 18px 60px rgba(2,6,23,.35); max-width:420px; width:92%; padding:20px;">
        <div style="display:flex; align-items:center; gap:12px;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2v10" stroke="#fb923c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5.5 8.5a7 7 0 1 0 13 0" stroke="#fb923c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <div>
                <div style="font-weight:800; color:#0f172a;">Confirm logout</div>
                <div style="color:#475569; font-size:13px; margin-top:4px;">Are you sure you want to logout?</div>
            </div>
        </div>
        <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:18px;">
            <button id="logoutCancelBtn" style="padding:8px 14px; border-radius:10px; border:1px solid #e2e8f0; background:#fff; color:#0f172a; font-weight:700; cursor:pointer;">Cancel</button>
            <button id="logoutConfirmBtn" style="padding:8px 14px; border-radius:10px; border:none; background:#fb923c; color:#fff; font-weight:700; cursor:pointer;">Logout</button>
        </div>
    </div>
</div>

<script>
// Sidebar logout: show confirmation modal then submit existing logout form
document.addEventListener('DOMContentLoaded', function(){
    const navLogout = document.getElementById('sidebar-logout');
    const modal = document.getElementById('logoutConfirmModal');
    const cancelBtn = document.getElementById('logoutCancelBtn');
    const confirmBtn = document.getElementById('logoutConfirmBtn');

    function showModal(){ if(modal) modal.style.display = 'flex'; }
    function hideModal(){ if(modal) modal.style.display = 'none'; }

    if(navLogout){
        navLogout.addEventListener('click', function(e){
            e.preventDefault();
            showModal();
        });
    }

    if(cancelBtn) cancelBtn.addEventListener('click', function(){ hideModal(); });

    if(confirmBtn) confirmBtn.addEventListener('click', function(){
        const footerForm = document.querySelector('.sidebar-footer form');
        if(footerForm) footerForm.submit();
        else hideModal();
    });

    // close on background click
    if(modal) modal.addEventListener('click', function(e){ if(e.target === modal) hideModal(); });
    // close on Escape
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape') hideModal(); });
});
</script>

<!-- Global loading overlay -->
<div id="global-loading" aria-hidden="true">
    <div class="loading-box" role="status" aria-live="polite">
        <div class="spinner" aria-hidden="true"></div>
        <div class="msg" id="loading-message">Loading...</div>
    </div>
</div>

<script>
function showLoading(msg){
    var overlay = document.getElementById('global-loading');
    var m = document.getElementById('loading-message');
    if(m) m.textContent = msg || 'Loading...';
    if(overlay) overlay.style.display = 'flex';
}
function hideLoading(){
    var overlay = document.getElementById('global-loading');
    if(overlay) overlay.style.display = 'none';
}

// Optionally hide overlay after navigation (in case browser doesn't submit synchronously)
window.addEventListener('pageshow', function(){ hideLoading(); });
</script>

<!-- Sidebar Toggle Script (Mobile Menu) -->
<script>
document.addEventListener('DOMContentLoaded', function(){
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if(!toggleBtn || !sidebar || !overlay) return;

    function closeSidebar(){
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        toggleBtn.setAttribute('aria-expanded', 'false');
    }

    function openSidebar(){
        sidebar.classList.add('show');
        overlay.classList.add('show');
        toggleBtn.setAttribute('aria-expanded', 'true');
    }

    function toggleSidebar(){
        if(sidebar.classList.contains('show')){
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    // Toggle on button click
    toggleBtn.addEventListener('click', toggleSidebar);

    // Close sidebar on overlay click
    overlay.addEventListener('click', closeSidebar);

    // Close sidebar when a nav link is clicked
    const navLinks = sidebar.querySelectorAll('.nav a');
    navLinks.forEach(link => {
        link.addEventListener('click', closeSidebar);
    });

    // Close sidebar on Escape key
    document.addEventListener('keydown', function(e){
        if(e.key === 'Escape' && sidebar.classList.contains('show')){
            closeSidebar();
        }
    });

    // Close sidebar when window is resized to desktop view (>768px)
    window.addEventListener('resize', function(){
        if(window.innerWidth > 768){
            closeSidebar();
        }
    });
});
</script>

</body>
</html>
