<?php
require_once 'require_auth.php';
include 'actions.php';
include 'dbcon.php';
 
 
if(!isset($_GET['id'])){
	$query1=mysqli_query($con,"SELECT * FROM patient ORDER BY n desc");
$row1=mysqli_fetch_assoc($query1);
$id=$row1['n'];
}
else{
$id=$_GET['id'];
}
 $query=mysqli_query($con,"SELECT * FROM patient WHERE n='$id'");
 $row=mysqli_fetch_assoc($query);
?>

<!doctype html>
<html>
<?php include 'head.php'; ?>
<body>
<div class="pane">
<?php include 'nav.php'; ?>
   <div class="content">
     
          </div>
<div class="content">
<form method="post"  >
<!-- <a href="history2.php"><--Goback</a> -->
<table class="tableheader">
<h1><?php 
echo $row['Lname'].", ".$row['Fname']." ".$row['Mname'];
?></h1>
	<tr><td>Address:</td>
	<td><?php echo $row['addres']; ?> <br/></td>
	</tr>
	<tr><td>Age:</td>
	<td>
    <br/><?php echo $row['age']; ?><br/></td>
    </tr>
    <tr><td>BirthDate</td>
	<td><br/><?php echo $row['Birthdate']; ?><br/></td>
	</tr>
	<tr><td>Gender:</td>
	<td><br/><?php echo $row['Gender']; ?><br/></td>
	</tr>
	<tr><td>Medication Given:</td>
	<td>
		</tr>
		<br/></td>
</tr> 

<?php release($id);?>

<tr><td>Release Date:</td><td>
<?php
$query2=mysqli_query($con, "SELECT * FROM releaselogs WHERE bname='$id'");
$r=mysqli_fetch_assoc($query2);
echo $r['DateRec'];

?>

</td></tr>
</table>


</form>

</div>
<div class="content">
</div>

</body>
</html>
