
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
          <a href="history.php"><--Goback</a>
          <H1>Baranggay <?php echo " ".$loc; ?></h1>
        </br>
            <table class="tableheader"  align="center" style="line-height:60px;">
            <tr><th>Name</th>
            <th>Age</th>
            <th>Bithdate</th>
            <th>Gender</th>
       </tr>
<?php

patient3($loc);
?>

</table>

		  
        </div>
		
		<div class="content">
		
		</div>
       </div>
    </body>
</html>
