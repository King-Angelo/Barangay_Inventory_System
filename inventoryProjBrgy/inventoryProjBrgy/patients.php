<?php
require_once 'require_auth.php';
include __DIR__ . '/dbcon.php';
include 'actions.php';
$loc=$_GET['loc'];
?><!DOCTYPE html>
<html>
<?php include 'head.php'; ?>
<body>
<div class="pane">
<?php include 'nav.php'; ?>
   <div class="content">
     
          </div>
   <div class="content">
     
          </div><div class="content">
          <a href="brgy.php?loc=<?php echo $loc;?>"><--Goback</a>
          <H1>Barangay <?php echo " ".$loc; ?></h1>
        </br>
            <table class="tableheader"  align="center" style="line-height:60px;">
            <tr><th>Name</th>
            <th>Age</th>
            <th>Bithdate</th>
            <th>Gender</th>
       </tr>
<?php

patient($loc);
?>

</table>

		  
        </div>
		
		<div class="content">
		
		</div>
       </div>
    </body>
</html>
