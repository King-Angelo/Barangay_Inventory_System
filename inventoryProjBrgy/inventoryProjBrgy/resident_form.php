<?php
require_once __DIR__ . '/require_auth.php';
include __DIR__ . '/dbcon.php';
include __DIR__ . '/actions.php';

$id       = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$resident = ($id > 0) ? get_resident($id) : null;
$is_edit  = ($resident !== null);
$title    = $is_edit ? 'Edit Resident' : 'New Resident';

$v = array(
    'last_name'    => $is_edit ? $resident['last_name']    : '',
    'first_name'   => $is_edit ? $resident['first_name']   : '',
    'middle_name'  => $is_edit ? ($resident['middle_name'] ?? '') : '',
    'email'        => $is_edit ? $resident['email']        : '',
    'phone'        => $is_edit ? ($resident['phone']       ?? '') : '',
    'birthdate'    => $is_edit ? ($resident['birthdate']   ?? '') : '',
    'gender'       => $is_edit ? ($resident['gender']      ?? '') : '',
    'address_line' => $is_edit ? ($resident['address_line']?? '') : '',
    'barangay_id'  => $is_edit ? $resident['barangay_id']  : (isset($_SESSION['barangay_id']) ? (int)$_SESSION['barangay_id'] : 1),
);
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
            array('Barangay ID *','barangay_id',  'number',   true),
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
