<?php
require_once __DIR__ . '/require_auth.php';
include __DIR__ . '/dbcon.php';
include __DIR__ . '/actions.php';

$id       = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$resident = ($id > 0) ? get_resident($id) : null;
$is_edit  = ($resident !== null);
$title    = $is_edit ? 'Edit Resident' : 'New Resident';

if ($id > 0 && (!$resident || !user_can_access_resident_id($id))) {
    header('Location: residents.php?error=' . urlencode('Access denied.'));
    exit;
}

$default_brgy = 1;
if (array_key_exists('barangay_id', $_SESSION) && $_SESSION['barangay_id'] !== null && $_SESSION['barangay_id'] !== '') {
    $default_brgy = (int)$_SESSION['barangay_id'];
}

$v = array(
    'last_name'    => $is_edit ? $resident['last_name']    : '',
    'first_name'   => $is_edit ? $resident['first_name']   : '',
    'middle_name'  => $is_edit ? ($resident['middle_name'] ?? '') : '',
    'email'        => $is_edit ? $resident['email']        : '',
    'phone'        => $is_edit ? ($resident['phone']       ?? '') : '',
    'birthdate'    => $is_edit ? ($resident['birthdate']   ?? '') : '',
    'gender'       => $is_edit ? ($resident['gender']      ?? '') : '',
    'address_line' => $is_edit ? ($resident['address_line']?? '') : '',
    'barangay_id'  => $is_edit ? (int)$resident['barangay_id'] : $default_brgy,
);

$barangays     = get_barangays();
$is_admin_user = function_exists('is_admin') && is_admin();
?><!DOCTYPE html>
<html>
<head>
  <?php include __DIR__ . '/head.php'; ?>
  <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
</head>
<body>
<div class="pane">
  <?php include __DIR__ . '/nav.php'; ?>
  <div class="content">
    <h2><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2>
    <a href="residents.php">&larr; Back to Residents</a>
    <br><br>

    <form method="POST" action="resident_save.php">
      <?php if ($is_edit): ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
      <?php endif; ?>

      <table class="tableheader">
        <?php
        $fields = array(
            array('Last Name *',  'last_name',    'text',     true),
            array('First Name *', 'first_name',   'text',     true),
            array('Middle Name',  'middle_name',  'text',     false),
            array('Email *',      'email',        'email',    true),
            array('Phone',        'phone',        'text',     false),
            array('Birthdate',    'birthdate',    'date',     false),
            array('Address',      'address_line', 'text',     false),
        );
        foreach ($fields as $f):
            list($label, $name, $type, $req) = $f;
            $val = htmlspecialchars((string)$v[$name], ENT_QUOTES, 'UTF-8');
        ?>
        <tr>
          <td><label><?php echo $label; ?></label></td>
          <td>
            <input type="<?php echo $type; ?>" name="<?php echo $name; ?>"
                   value="<?php echo $val; ?>"
                   <?php if ($req) echo 'required'; ?>>
          </td>
        </tr>
        <?php endforeach; ?>
        <tr>
          <td><label>Barangay *</label></td>
          <td>
            <?php if ($is_admin_user): ?>
              <select name="barangay_id" required style="min-width:220px;padding:6px">
                <?php foreach ($barangays as $b): ?>
                  <option value="<?php echo (int)$b['n']; ?>"
                    <?php echo ((int)$v['barangay_id'] === (int)$b['n']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($b['brgy'], ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <?php
              $brgy_label = '';
              foreach ($barangays as $b) {
                  if ((int)$b['n'] === (int)$v['barangay_id']) {
                      $brgy_label = (string)$b['brgy'];
                      break;
                  }
              }
              ?>
              <input type="hidden" name="barangay_id" value="<?php echo (int)$v['barangay_id']; ?>">
              <span><?php echo htmlspecialchars($brgy_label !== '' ? $brgy_label : ('#' . (int)$v['barangay_id']), ENT_QUOTES, 'UTF-8'); ?></span>
              <span style="color:#888;font-size:12px"> (staff &mdash; change barangay: admin only)</span>
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <td><label>Gender</label></td>
          <td>
            <select name="gender">
              <option value="">— select —</option>
              <?php foreach (array('Male', 'Female', 'Other') as $g): ?>
                <option value="<?php echo $g; ?>" <?php echo ($v['gender'] === $g) ? 'selected' : ''; ?>>
                  <?php echo $g; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
      </table>
      <br>
      <button type="submit" class="btn btn-primary" name="btn-send">
        <?php echo $is_edit ? 'Save Changes' : 'Create Resident'; ?>
      </button>
      <a href="residents.php" class="btn btn-default" style="margin-left:8px">Cancel</a>
    </form>
  </div>
</div>
<script src="assets/js/jquery.min.js"></script>
<script src="assets/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
