<?php
// Task 2: admin-only
require_once __DIR__ . '/require_admin.php';
include __DIR__ . '/actions.php';
include __DIR__ . '/dbcon.php';

$usr=$_SESSION['user'];
$q=mysqli_query($con,"SELECT * FROM users WHERE UserName='$usr'");
$r=mysqli_fetch_assoc($q);
?>

<html>
<?php include __DIR__ . '/head.php'; ?>
<body>
<div class="pane">
    <?php 
    include __DIR__ . '/nav.php';
    ?>

    <div class="content">
        <form method="POST" >
        <table class="tableheader">

<tr><td>UserName:</td>
<td><input type="text" name="User" placeholder="User" value=<?php echo $r['UserName']; ?>  ><br/></td>
</tr>

<tr><td>Pass:</td>
<td><input type="password" name="Pass" placeholder="Pass" value=<?php echo $r['PaSS'];?> required ><br/></td>
</tr></table>
<?php 
changeuser($usr);
?>
<button type="submit" name="btn-send" id="btn-send"><strong>Add</strong></button>
</form>

</div>

    <div>
</body>
</html>
<script>
function update(){
    prompt("Logging out. Please Sign in Again");
}
</script>