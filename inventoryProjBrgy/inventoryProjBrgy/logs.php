<?php
require_once 'require_auth.php';
include __DIR__ . '/dbcon.php';
include 'actions.php';
?><!DOCTYPE html>
<html>
<?php include 'head.php'; ?>
<body>
<div class="pane">
<?php include 'nav.php'; ?>
   <div class="content">
    <center><h1>Record Book </h1></br></center> 
          </div>
   <div class="content" >
     
          
          
          
          
        </br>
<table class="tableheader"  align="center" style="line-height:60px;">
            <tr><th>Name of Item</th>
            <th>Category</th>
            <th>Quantity</th>
            <th>Expiration Date</th>
            <th>Date of Inventory</th>
       </tr>
<?php

inventorylog();
?>

</table>
		  
        </div>
		
		<div class="content">
		
		</div>
       </div>
    </body>
</html>
