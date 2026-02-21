<?php
/**
 * AJES Template
 * 
 * Consolidated CSS styles for all views (Dashboards, Auth, Records, etc.)
 * Include this file in your views to use the shared styles
 */
?>
<style>
    /* ============================================
       BASE STYLES
       ============================================ */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: Arial, sans-serif;
        margin: 0;
        background-color: #f5f5f5;
    }

    /* ============================================
       DASHBOARD LAYOUT
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
        background-color: #2d5f3a;
        color: #ffffff;
        padding: 8px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-sizing: border-box;
    }

    .topbar-left {
        font-weight: bold;
    }

    .topbar-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    /* Badge */
    .badge {
        background-color: #f9d71c;
        color: #2d5f3a;
        padding: 2px 6px;
        font-size: 12px;
        border-radius: 3px;
    }

    /* Icon Button & Badge */
    .icon-button {
        position: relative;
        cursor: pointer;
    }

    .icon-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background-color: #d32f2f;
        color: #ffffff;
        border-radius: 50%;
        font-size: 10px;
        padding: 2px 5px;
    }

    /* Sidebar */
    .sidebar {
        width: 220px;
        background-color: #f0f0f0;
        padding: 72px 12px 12px;
        box-sizing: border-box;
    }

    .menu a {
        display: block;
        padding: 10px;
        text-decoration: none;
        color: #333;
        margin-bottom: 4px;
        font-size: 14px;
    }

    .menu a:hover {
        background-color: #ddd;
    }

    /* Content Area */
    .content {
        flex: 1;
        padding: 72px 16px 16px;
        box-sizing: border-box;
    }

    /* Card */
    .card {
        background-color: #ffffff;
        border: 1px solid #ddd;
        padding: 12px 14px;
        margin-bottom: 12px;
    }

    .card-title {
        font-size: 16px;
        margin-bottom: 4px;
    }

    /* Stats Grid (for Teacher Dashboard) */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 10px;
        margin-top: 8px;
    }

    .stat-card {
        background-color: #ffffff;
        border: 1px solid #ddd;
        padding: 10px;
    }

    .stat-label {
        font-size: 13px;
        color: #555;
        margin-bottom: 4px;
    }

    .stat-value {
        font-size: 20px;
        font-weight: bold;
    }

    /* Status Badge (for Teacher Dashboard) */
    .status-badge {
        display: inline-block;
        padding: 2px 6px;
        font-size: 11px;
        border-radius: 3px;
        border: 1px solid #ccc;
    }

    /* ============================================
       LOGIN PAGE STYLES
       ============================================ */
    .left-panel {
        width: 35%;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px;
    }

    .logo-container {
        text-align: center;
    }

    .logo {
        width: 250px;
        height: 250px;
    }

    .right-panel {
        width: 65%;
        background: linear-gradient(135deg, #8bc34a 0%, #2d5f3a 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px;
    }

    .login-container {
        width: 100%;
        max-width: 460px;
    }

    h1 {
        font-size: 32px;
        margin-bottom: 40px;
        color: #f9d71c;
        text-align: center;
    }

    .form-group {
        margin-bottom: 24px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        color: #ffffff;
        font-size: 14px;
    }

    input[type="text"],
    input[type="password"],
    input[type="email"],
    input[type="number"],
    textarea {
        width: 100%;
        padding: 14px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        background-color: #fff;
        box-shadow: 0 2px 8px rgba(249, 215, 28, 0.3);
    }

    input[type="text"]:focus,
    input[type="password"]:focus,
    input[type="email"]:focus,
    input[type="number"]:focus,
    textarea:focus {
        outline: none;
        border-color: #f9d71c;
        box-shadow: 0 4px 12px rgba(249, 215, 28, 0.5);
    }

    .password-wrapper {
        position: relative;
    }

    .toggle-password {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #666;
    }

    .forgot-password {
        text-align: right;
        margin-top: 8px;
    }

    .forgot-password a {
        color: #f9d71c;
        text-decoration: none;
        font-size: 14px;
    }

    .forgot-password a:hover {
        color: #ffffff;
    }

    .login-button {
        width: 100%;
        padding: 14px;
        background-color: #f9d71c;
        color: #2d5f3a;
        border: none;
        border-radius: 4px;
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
        background-color: #e6c519;
    }

    /* ============================================
       AUTH PAGES (Forgot Password, Reset Password)
       ============================================ */
    .container {
        max-width: 400px;
        margin: 0 auto;
    }

    .message {
        margin-bottom: 12px;
        color: red;
    }

    .message.success {
        color: green;
    }

    button {
        padding: 8px 16px;
        cursor: pointer;
    }

    /* ============================================
       RECORDS PAGES
       ============================================ */
    table {
        border-collapse: collapse;
        width: 100%;
        margin-top: 12px;
    }

    th, td {
        border: 1px solid #ccc;
        padding: 8px;
        text-align: left;
    }

    th {
        background-color: #f0f0f0;
    }

    /* ============================================
       COMMON STYLES
       ============================================ */
    a {
        text-decoration: none;
    }
</style>
