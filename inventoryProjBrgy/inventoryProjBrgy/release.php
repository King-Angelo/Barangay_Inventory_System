<?php
require_once __DIR__ . '/require_auth.php';
include __DIR__ . '/dbcon.php';
include __DIR__ . '/actions.php';
 $id=$_GET['id'];

$query=mysqli_query($con,"SELECT * FROM medsupply WHERE n=$id");
$r=mysqli_fetch_assoc($query);
$expdate=$r['expdate'];
$category=$r['category'];
?>

<!doctype html>
<html>
<?php include __DIR__ . '/head.php'; ?>
<?php $item=$r['iname']; ?>
<body>
<div class="pane">
<?php include __DIR__ . '/nav.php'; ?>
   <div class="content">
     
          </div>
<div class="content">
<form method="post">
<a href="items.php"><--Goback</a></br></br>
<h1><?php echo $item; ?></h1>
<h3><?php echo "(".$category.")"; ?></h3></br>


<table class="tableheader">
<tr><td>Expiration Date:</td>
	<td><input type="text" name="exp" value=<?php echo $expdate; ?> disabled><br/></td>
	</tr>
	<tr><td>Date of Inventory:</td>
	<td><input type="text" name="rcv" value=<?php echo $r['datereceived']; ?> disabled><br/></td>
    </tr>
    
	<tr><td>Name of Receiver:</td>
	<td><input type="text" name="iname" placeholder="name" required ><br/></td>
	</tr>
	<tr><td>Quantity</td>
	<td><input type="number" name="quantity" min=1 max=<?php  
	echo $r['quantity'];	
	?>
	>
	
	<br/></td>
	</tr>
	


<?php medlog($item,$r['n'],$expdate,$category);
?>
</table>
<center>
	<button type="submit" name="btn-send" id="btn-send" ><strong>Release</strong></button>
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