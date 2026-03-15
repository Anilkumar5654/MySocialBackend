<style>
  :root {
    --bg-light: #f4f7fa;
    --sidebar-dark: #343e5a;
    --text-dark: #4e5e7a;
    --primary-blue: #5d78ff;
    --accent-orange: #f99b2d;
    --accent-red: #fd397a;
    --accent-green: #0abb87;
    --border-soft: #ebedf2;
    --card-shadow: 0 2px 20px rgba(0,0,0,0.05);
  }

  body, .content-wrapper { background-color: var(--bg-light) !important; color: var(--text-dark); }
  
  /* 👇 SIDEBAR FIX (Jo miss ho gaya tha) 👇 */
  .main-sidebar { background-color: var(--sidebar-dark) !important; }
  .nav-sidebar .nav-link { color: #a2a3b7 !important; border-radius: 0 !important; }
  .nav-sidebar .nav-link.active { background: #2e3752 !important; color: #fff !important; border-left: 3px solid var(--primary-blue); }
  /* 👆 -------------------------------- 👆 */

  /* Stats Cards (Top row) */
  .stats-card {
    background: #fff !important;
    border: none !important;
    border-radius: 8px !important;
    box-shadow: var(--card-shadow) !important;
    margin-bottom: 20px;
  }

  .stat-blue { background: var(--primary-blue) !important; color: #fff !important; }
  .stat-orange { background: var(--accent-orange) !important; color: #fff !important; }
  .stat-red { background: var(--accent-red) !important; color: #fff !important; }
  .stat-green { background: var(--accent-green) !important; color: #fff !important; }

  /* 🖥️ DESKTOP 2*2 LAYOUT LOGIC */
  @media (min-width: 992px) {
    .main-grid {
      display: flex;
      flex-wrap: wrap;
      margin: 0 -10px; /* Spacing adjustment */
    }
    .main-grid > .grid-item {
      flex: 0 0 50%; /* 2 columns logic */
      max-width: 50%;
      padding: 0 10px;
    }
    .dash-card {
      height: auto !important; /* Auto height fix */
      min-height: 100px;
      margin-bottom: 20px;
    }
  }

  /* 📱 MOBILE FIXES */
  @media (max-width: 768px) {
    .content-wrapper { padding: 10px !important; }
    .main-grid > .grid-item {
      flex: 0 0 100%; /* Mobile pe single column */
      max-width: 100%;
    }
    .stats-card h3 { font-size: 1.4rem !important; }
    .card-body { overflow-x: auto; }
  }

  /* Shared Card Styling */
  .card {
    background: #fff !important;
    border: none !important;
    border-radius: 8px !important;
    box-shadow: var(--card-shadow) !important;
    height: auto; /* Ensure no fixed height */
  }
</style>
