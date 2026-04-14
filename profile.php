<?php

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
  <?php include'head.php';?>
<body>
<div class="pane">
<?php include 'nav.php'; ?>
   <div class="content">
     
          </div>
<div class="content">
<form method="post"  >
<a href="items.php"><--Goback</a>
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
	<tr><td>Medication:</td>
	<td>
		</tr>
		<!-- <tr>
		<td><br/>
		<?php 
		// echo $row['Medication']; 
		?>
		<br/></td>
</tr> -->

<?php release($id);?>


</table>


</form>
<center>
<?php 

$query4=mysqli_query($con,"SELECT * FROM supply2 WHERE beneficiary='$id'");
 $row4=mysqli_fetch_assoc($query4);

 $query5=mysqli_query($con,"SELECT * FROM releaselogs WHERE bname='$id'");
 $row2=mysqli_fetch_assoc($query5);

 if (isset($row2)){

	echo "
		<button dissabled>RELEASED</button></form>";
	
	}
else{
 
if (isset($row4)){

echo "<form method='POST' action='releaseben.php?id=".$id."'>
	<button >RELEASE</button></form>";

}
}
?>


</center><br>
<!-- <button onclick="medi()">Add Medication</button></td> -->

<div id="Div1" style="display:none">
<table class="tableheader"><?php inventory2($id);?><table>
</div>


<!-- 
<script>
function update(){
	var x;
	if(confirm("Updated data Sucessfully") == true){
		x= "update";
	}
}
</script> -->
</div>
<div class="content">
</div>

</body>
</html>
