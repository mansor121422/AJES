<?php
/**
 * AJES Template
 * Green pastel theme – dashboards, auth, records.
 */
?>
<style>
    /* ============================================
       BASE & GREEN THEME
       ============================================ */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', system-ui, sans-serif;
        margin: 0;
        background-color: #e8f5e9;
        color: #1b5e20;
    }

    /* ============================================
       DASHBOARD LAYOUT (reference-style)
       ============================================ */
    .layout {
        display: flex;
        min-height: 100vh;
    }

    /* Topbar */
    .topbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 56px;
        background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
        color: #fff;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 8px rgba(27, 94, 32, 0.25);
        z-index: 100;
    }

    .topbar-left {
        font-weight: 700;
        font-size: 1.1rem;
    }

    .topbar-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .badge {
        background-color: #c8e6c9;
        color: #1b5e20;
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 20px;
    }

    .icon-button {
        position: relative;
        cursor: pointer;
        padding: 6px;
    }

    .icon-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        background-color: #ff5722;
        color: #fff;
        border-radius: 50%;
        font-size: 10px;
        padding: 2px 5px;
    }

    /* Sidebar – left nav like reference */
    .sidebar {
        width: 240px;
        min-width: 240px;
        background: #fff;
        padding: 72px 0 16px;
        box-shadow: 2px 0 12px rgba(0,0,0,0.06);
        position: relative;
    }

    .sidebar-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 16px 20px;
        font-weight: 700;
        font-size: 1.1rem;
        color: #1b5e20;
        border-bottom: 1px solid #e8f5e9;
        margin-bottom: 12px;
    }

    .sidebar-brand-icon {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #66bb6a 0%, #43a047 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.1rem;
    }

    .menu a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        text-decoration: none;
        color: #2e7d32;
        font-size: 14px;
        margin: 2px 8px;
        border-radius: 10px;
        transition: background 0.2s, color 0.2s;
    }

    .menu a:hover {
        background-color: #e8f5e9;
        color: #1b5e20;
    }

    .menu a.active {
        background: linear-gradient(135deg, #81c784 0%, #66bb6a 100%);
        color: #fff;
    }

    .menu-icon {
        font-size: 1.1rem;
        opacity: 0.9;
    }

    .sidebar-footer {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 12px 16px;
        border-top: 1px solid #e8f5e9;
    }

    .sidebar-footer a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        color: #558b2f;
        text-decoration: none;
        font-size: 14px;
        border-radius: 8px;
    }

    .sidebar-footer a:hover {
        background-color: #e8f5e9;
    }

    /* Content area */
    .content {
        flex: 1;
        padding: 72px 24px 24px;
        min-width: 0;
    }

    .dashboard-header {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1b5e20;
        margin-bottom: 20px;
    }

    /* KPI row – 3 cards */
    .kpi-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }

    @media (max-width: 900px) {
        .kpi-row { grid-template-columns: 1fr; }
    }

    .kpi-card {
        background: #fff;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 14px rgba(27, 94, 32, 0.08);
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .kpi-card.kpi-mint { background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); }
    .kpi-card.kpi-sage { background: linear-gradient(135deg, #c8e6c9 0%, #a5d6a7 100%); }
    .kpi-card.kpi-green { background: linear-gradient(135deg, #a5d6a7 0%, #81c784 100%); }

    .kpi-body h3 {
        font-size: 0.85rem;
        color: #2e7d32;
        margin-bottom: 6px;
        font-weight: 600;
    }

    .kpi-body .kpi-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1b5e20;
    }

    .kpi-body .kpi-meta {
        font-size: 0.8rem;
        color: #558b2f;
        margin-top: 4px;
    }

    .kpi-progress {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: conic-gradient(#2e7d32 var(--pct, 0%), #e0e0e0 0);
        flex-shrink: 0;
    }

    .kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: rgba(255,255,255,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }

    /* Dashboard grid: main + sidebar column */
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 24px;
    }

    @media (max-width: 1000px) {
        .dashboard-grid { grid-template-columns: 1fr; }
    }

    /* Cards – generic */
    .card {
        background: #fff;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 14px rgba(27, 94, 32, 0.08);
        border: 1px solid rgba(46, 125, 50, 0.1);
    }

    .card-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1b5e20;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid #e8f5e9;
    }

    /* Admin feature cards (clickable grid) */
    .admin-features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 16px;
    }

    .admin-feature-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 20px 16px;
        background: #f1f8e9;
        border: 2px solid #c8e6c9;
        border-radius: 12px;
        text-decoration: none;
        color: #1b5e20;
        transition: background 0.2s, border-color 0.2s, transform 0.15s;
    }

    .admin-feature-card:hover {
        background: #e8f5e9;
        border-color: #81c784;
        transform: translateY(-2px);
    }

    .admin-feature-icon {
        font-size: 2rem;
        margin-bottom: 8px;
    }

    .admin-feature-label {
        font-weight: 600;
        font-size: 0.95rem;
        text-align: center;
    }

    .admin-feature-desc {
        font-size: 0.8rem;
        color: #558b2f;
        text-align: center;
        margin-top: 4px;
    }

    /* Recent table (announcements, activity) */
    .recent-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    .recent-table th,
    .recent-table td {
        padding: 12px 14px;
        text-align: left;
        border-bottom: 1px solid #e8f5e9;
    }

    .recent-table th {
        color: #2e7d32;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }

    .recent-table tr:hover {
        background-color: #f1f8e9;
    }

    .recent-table .link-details {
        color: #2e7d32;
        font-weight: 500;
        text-decoration: none;
    }

    .recent-table .link-details:hover {
        text-decoration: underline;
    }

    /* Status badges */
    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 20px;
    }

    .status-badge-approved,
    .status-badge-active,
    .status-badge-published {
        background: #c8e6c9;
        color: #1b5e20;
    }

    .status-badge-pending,
    .status-badge-draft {
        background: #fff3e0;
        color: #e65100;
    }

    .status-badge-delivered,
    .status-badge-read {
        background: #e3f2fd;
        color: #1565c0;
    }

    /* Updates card (notifications list) */
    .updates-card {
        background: #fff;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 14px rgba(27, 94, 32, 0.08);
        border: 1px solid rgba(46, 125, 50, 0.1);
    }

    .updates-card .card-title {
        border-bottom: 1px solid #e8f5e9;
        margin-bottom: 12px;
    }

    .updates-item {
        display: flex;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #e8f5e9;
        font-size: 0.9rem;
    }

    .updates-item:last-child {
        border-bottom: none;
    }

    .updates-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #a5d6a7 0%, #81c784 100%);
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1rem;
    }

    .updates-text {
        flex: 1;
        color: #333;
    }

    .updates-time {
        font-size: 0.8rem;
        color: #558b2f;
        margin-top: 2px;
    }

    /* Graph/activity card placeholder */
    .graph-card {
        background: #fff;
        border-radius: 16px;
        padding: 20px;
        margin-top: 20px;
        box-shadow: 0 4px 14px rgba(27, 94, 32, 0.08);
        border: 1px solid rgba(46, 125, 50, 0.1);
    }

    .graph-placeholder {
        height: 180px;
        background: linear-gradient(180deg, #e8f5e9 0%, #c8e6c9 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #2e7d32;
        font-size: 0.9rem;
    }

    /* Stats grid (teacher-style stat blocks) */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
        margin-top: 12px;
    }

    .stat-card {
        background: #f1f8e9;
        border: 1px solid #c8e6c9;
        border-radius: 12px;
        padding: 14px;
    }

    .stat-label {
        font-size: 0.8rem;
        color: #558b2f;
        margin-bottom: 4px;
    }

    .stat-value {
        font-size: 1.4rem;
        font-weight: 700;
        color: #1b5e20;
    }

    /* Welcome block */
    .welcome-card {
        background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid rgba(46, 125, 50, 0.15);
    }

    .welcome-card .card-title {
        border: none;
        padding: 0;
        margin-bottom: 6px;
    }

    .welcome-card p {
        font-size: 0.95rem;
        color: #2e7d32;
    }

    /* ============================================
       LOGIN PAGE
       ============================================ */
    .left-panel {
        width: 35%;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px;
    }

    .logo-container { text-align: center; }
    .logo { width: 250px; height: 250px; }

    .right-panel {
        width: 65%;
        background: linear-gradient(135deg, #66bb6a 0%, #2e7d32 50%, #1b5e20 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px;
    }

    .login-container { max-width: 460px; width: 100%; }

    h1 {
        font-size: 32px;
        margin-bottom: 40px;
        color: #c8e6c9;
        text-align: center;
    }

    .form-group { margin-bottom: 24px; }

    label {
        display: block;
        margin-bottom: 8px;
        color: #fff;
        font-size: 14px;
    }

    input[type="text"],
    input[type="password"],
    input[type="email"],
    input[type="number"],
    textarea {
        width: 100%;
        padding: 14px;
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 8px;
        font-size: 14px;
        background: rgba(255,255,255,0.95);
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }

    input:focus, textarea:focus {
        outline: none;
        border-color: #81c784;
        box-shadow: 0 0 0 3px rgba(129, 199, 132, 0.3);
    }

    .password-wrapper { position: relative; }
    .toggle-password {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #558b2f;
    }

    .forgot-password { text-align: right; margin-top: 8px; }
    .forgot-password a {
        color: #c8e6c9;
        text-decoration: none;
        font-size: 14px;
    }
    .forgot-password a:hover { color: #fff; }

    .login-button {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #81c784 0%, #66bb6a 100%);
        color: #1b5e20;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        margin-top: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .login-button:hover {
        background: linear-gradient(135deg, #66bb6a 0%, #43a047 100%);
        color: #fff;
    }

    /* ============================================
       AUTH PAGES (Forgot / Reset Password)
       ============================================ */
    .container { max-width: 400px; margin: 0 auto; }
    .message { margin-bottom: 12px; color: #c62828; }
    .message.success { color: #2e7d32; }
    button { padding: 8px 16px; cursor: pointer; border-radius: 8px; }

    /* ============================================
       RECORDS PAGES
       ============================================ */
    table {
        border-collapse: collapse;
        width: 100%;
        margin-top: 12px;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    }
    th, td {
        border: 1px solid #e8f5e9;
        padding: 12px 14px;
        text-align: left;
    }
    th {
        background: #e8f5e9;
        color: #1b5e20;
        font-weight: 600;
    }

    a { text-decoration: none; }
</style>
