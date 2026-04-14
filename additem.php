<?php

include 'actions.php';
// $loc=$_GET['loc'];
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

	<tr><td>Name of Item:</td>
	<td><input type="text" name="iname" placeholder="name" required ><br/></td>
	</tr>
	<tr><td>Category:</td>
	<td><select name="category">
	<option value="Medicine">Medicine</option>	
    <option value="Supply">Supply</option>
    <option value="Equipment">Equipment</option>	
    <br/></td>
    </tr>
    <tr><td>Quantity</td>
	<td><input type="number" name="quantity" placeholder="quantity" min=1 required><br/></td>
	</tr>
	<tr><td>Expiration Date:</td>
	<td><input type="date" name="exp" required><br/></td>
	</tr>
	<tr><td>Received Date:</td>
	<td><input type="date" name="rcv" required><br/></td>
    </tr>
    


<?php addItem();
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