<style>
    .ay-module {
        display: flex;
        gap: 0;
        align-items: flex-start;
        margin: 0 -4px 24px;
        min-height: 420px;
    }
    .ay-sidebar {
        width: 240px;
        flex-shrink: 0;
        background: linear-gradient(180deg, #1b5e20 0%, #2e7d32 100%);
        border-radius: 12px;
        padding: 16px 0;
        box-shadow: 0 4px 14px rgba(27, 94, 32, 0.2);
        position: sticky;
        top: 72px;
        max-height: calc(100vh - 88px);
        overflow-y: auto;
    }
    .ay-sidebar-brand {
        padding: 8px 18px 14px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        margin-bottom: 8px;
    }
    .ay-sidebar-brand .ay-brand-icon {
        font-size: 1.5rem;
        margin-bottom: 4px;
    }
    .ay-sidebar-brand .ay-brand-title {
        color: #fff;
        font-weight: 700;
        font-size: 1rem;
        line-height: 1.3;
    }
    .ay-sidebar-brand .ay-brand-sub {
        color: rgba(255, 255, 255, 0.75);
        font-size: 0.78rem;
        margin-top: 4px;
    }
    .ay-sidebar-active-pill {
        display: inline-block;
        margin-top: 8px;
        padding: 4px 10px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        color: #fff;
    }
    .ay-side-nav {
        display: flex;
        flex-direction: column;
        gap: 2px;
        padding: 4px 10px;
    }
    .ay-side-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 8px;
        color: rgba(255, 255, 255, 0.9);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .ay-side-link:hover {
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
    }
    .ay-side-link.active {
        background: #fff;
        color: #1b5e20;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    }
    .ay-side-link.ay-side-warn:not(.active) {
        color: #ffe082;
    }
    .ay-side-link.ay-side-warn.active {
        background: #ff8f00;
        color: #fff;
    }
    .ay-side-icon {
        width: 22px;
        text-align: center;
        flex-shrink: 0;
    }
    .ay-side-section {
        padding: 12px 18px 6px;
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: rgba(255, 255, 255, 0.5);
        font-weight: 600;
    }
    .ay-side-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.12);
        margin: 10px 14px;
    }
    .ay-panel {
        flex: 1;
        min-width: 0;
        padding-left: 20px;
    }
    .ay-panel .dashboard-header {
        margin-top: 0;
    }
    @media (max-width: 900px) {
        .ay-module {
            flex-direction: column;
        }
        .ay-sidebar {
            width: 100%;
            position: static;
            max-height: none;
        }
        .ay-side-nav {
            flex-direction: row;
            flex-wrap: wrap;
        }
        .ay-panel {
            padding-left: 0;
            padding-top: 16px;
        }
    }
</style>
