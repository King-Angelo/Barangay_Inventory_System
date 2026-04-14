<!DOCTYPE html>
<html>
<?php include'head.php';
include 'dbcon.php';
include 'actions.php';
$id2=$_GET['id'];
$q=mysqli_query($con,"SELECT * FROM logs WHERE itemcode='$id2'");
$r=mysqli_fetch_assoc($q);
$id=$r['itemname'];

// $loc=$_GET['loc'];
 ?>
<body>
<div class="pane">
<?php include 'nav.php'; ?>
   <div class="content">
     
          </div>
   <div class="content">
     
          </div><div class="content">
          <a href="logs.php"><--Goback</a></br></br>
          
          
   <h1><?php echo $id; ?></h1></br>
       
<table class="tableheader"  align="center" style="line-height:60px;">
            <tr><th>Date of Transaction</th>
            <th>Receiver</th>
            <th>Quantity</th>
            
       </tr>
<?php

logs2($id);
?>

</table>
		  
        </div>
		
		<div class="content">
		
		</div>
       </div>
    </body>
</html>
