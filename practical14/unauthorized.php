<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
page_header('Unauthorized', __FILE__);
?>
<div style="display:flex;align-items:center;justify-content:center;flex:1;padding:3rem">
  <div style="text-align:center;max-width:420px">
    <div style="font-size:4rem;margin-bottom:1rem">🚫</div>
    <h1 style="font-size:1.5rem;color:var(--danger);margin-bottom:.5rem">Access Denied</h1>
    <p style="color:var(--muted);margin-bottom:1.5rem">You don't have permission to view this page.</p>
    <a href="dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
  </div>
</div>
<?php page_footer(); ?>
