
<!DOCTYPE html>
<html>
<?php include 'head.php';
include 'dbcon.php';
include 'actions.php';

// $loc=$_GET['loc'];
 ?>
<body>
<div class="pane">
   <?php include 'nav.php'; ?>
   <div class="content">
     
          </div><div class="content">
          
          
          
        <button><a href="additem.php">ADD ITEM<a></button></br></br>
            <table class="tableheader"  align="center" style="line-height:60px;">
            <tr><th>Name of Item</th>
            <th>Category</th>
            <th>Quantity</th>
            <th>Expiration Date</th>
            <th>Date of Inventory</th>
       </tr>
<?php

inventory();
?>

</table>

		  
        </div>
		
		<div class="content">
		
		</div>
       </div>
    </body>
</html>
