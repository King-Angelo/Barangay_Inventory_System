<?php
require_once 'require_auth.php';
include 'actions.php';
$loc=$_GET['loc'];
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
<form method="post" action="result.php" >
<a href="patients.php?loc=<?php echo $loc;?>"><--Goback</a>
<table class="tableheader">

	<tr><td>Name:</td>
	<td><input type="text" name="lname" placeholder="Last name" required >, <input type="text" name="fname" placeholder="First name" required ><input type="text" name="mname" placeholder="Middle Name" required ><br/></td>
	</tr>
	<tr><td>Barangay:</td>
	<td><input type="text" name="brgy" value="<?php echo $loc;?>"disabled><br/></td>
    </tr>
    <tr><td>Address:</td>
	<td><input type="text" name="adress" placeholder="address"><br/></td>
	</tr>
	<tr><td>Age</td>
	<td><input type="text" name="age" placeholder="age"><br/></td>
	</tr>
	<tr><td>Gender</td>
	<td><select name="gender"> 
<option value="M">M</option>
<option value="F">F</option>
</select><br/></td>

    </tr>
    <tr><td>BirthDate:</td>
	<td><input type="date" name="Bday"><br/></td>
	</tr>
	<tr><td>Medication</td>
	<td><textarea rows="20" cols="100" name="medication" required></textarea></br>
	</tr>
	
	</tr>
	
	</tr>



<?php addPatient();
?>
</table>
<center>
	<button type="submit" name="btn-send" id="btn-send" ><strong>Add</strong></button>
</center>
</form>
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