<?php
include 'dbcon.php';
include 'actions.php';
 $id=$_GET['id2'];
 $id2=$_GET['id'];
$query=mysqli_query($con,"SELECT * FROM medsupply WHERE n=$id");
$r=mysqli_fetch_assoc($query);

$query2=mysqli_query($con,"SELECT * FROM logs WHERE n=$id2");
$r2=mysqli_fetch_assoc($query2);

?>

<!doctype html>
<html>
	<?php include'head.php';
	 $item=$r['iname'];?>
<body>
<div class="pane">
<?php include 'nav.php'; ?>
   <div class="content">
     
          </div>
<div class="content">
<form method="post">
<a href="logs2.php?id=<?php echo $id; ?>"><--Goback</a></br></br>
<h1><?php echo $item; ?></h1>
<h3><?php echo "(".$r['category'].")"; ?></h3></br>


<table class="tableheader">
<tr><td>Expiration Date:</td>
	<td><input type="text" name="exp" value=<?php echo $r['expdate']; ?> disabled><br/></td>
	</tr>
	<tr><td>Date of Inventory:</td>
	<td><input type="text" name="rcv" value=<?php echo $r['datereceived']; ?> disabled><br/></td>
    </tr>
    
	<tr><td>Name of Receiver:</td>
	<td><input type="text" name="iname" value=<?php echo $r2['receiver']; ?> disabled ><br/></td>
	</tr>
	<tr><td>Quantity:</td>
	<td><input type="text" name="quantity" value=<?php echo $r2['quantity']; ?> disabled>
	
	<br/></td>
	</tr>
	<tr><td>Date Released:</td>
	<td><input type="text" name="Drelease" value=<?php  
      echo $r2['transactionD']; 
	?> disabled>
	
	<br/></td>
	</tr>


</table>
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