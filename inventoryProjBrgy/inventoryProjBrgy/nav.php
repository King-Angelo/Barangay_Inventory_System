<?php
// Task 2 & 3 — Role-aware navigation
// current_role() is defined in require_auth.php which every protected page includes first
$_nav_role = function_exists('current_role') ? current_role() : 'staff';
$_nav_user = isset($_SESSION['user']) ? htmlspecialchars((string)$_SESSION['user'], ENT_QUOTES, 'UTF-8') : '';
?>
<nav class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-header">
      <button class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navcol-1">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
    </div>
    <div class="collapse navbar-collapse" id="navcol-1">
      <ul class="nav navbar-nav navbar-right">
        <li><a href="brgy.php">BENEFICIARIES</a></li>
        <li><a href="items.php">SUPPLIES</a></li>
        <li><a href="logs.php">RECORD BOOK</a></li>
        <li><a href="history.php">HISTORY</a></li>
        <li><a href="residents.php">RESIDENTS</a></li>
        <li><a href="permits.php">PERMITS</a></li>
        <?php if ($_nav_role === 'admin'): ?>
          <li><a href="Settings.php">SETTINGS</a></li>
        <?php endif; ?>
        <li>
          <a href="logout.php">
            LOGOUT
            <?php if ($_nav_user !== ''): ?>
              <small style="opacity:.65;font-size:10px">
                (<?php echo $_nav_user; ?> &mdash; <?php echo strtoupper($_nav_role); ?>)
              </small>
            <?php endif; ?>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
