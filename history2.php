
<!DOCTYPE html>
<html>
<?php include'head.php';
include 'dbcon.php';
include 'actions.php';
$loc=$_GET['loc'];
 ?>
<body>
<div class="pane">
<?php include 'nav.php'; ?>
   <div class="content">
     
          </div>
   <div class="content">
     
          </div><div class="content">
          <a href="brgy.php?loc=<?php echo $loc;?>"><--Goback</a>
          <H1>Baranggay <?php echo " ".$loc; ?></h1>
        </br>
            <table class="tableheader"  align="center" style="line-height:60px;">
            <tr><th>Name</th>
            <th>Age</th>
            <th>Bithdate</th>
            <th>Gender</th>
       </tr>
<?php

patient2($loc);
?>

</table>

		  
        </div>
		
		<div class="content">
		
		</div>
       </div>
    </body>
</html>
