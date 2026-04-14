<?php

$id=$_GET['id'];

include 'dbcon.php';

$q=mysqli_query($con,"SELECT * FROM supply2 WHERE beneficiary='$id'");

while($r=mysqli_fetch_assoc($q)){
    $med=$r['medicine'];
    $quantity=$r['quanti'];
    $date=date("m/d/y");
    $date2=date('Y-m-d', strtotime($date));
$q3=mysqli_query($con,"INSERT INTO releaselogs (bname,medicine,quantity,DateRec) VALUES ('$id','$med','$quantity','$date2')");
$q4=mysqli_query($con,"INSERT INTO released (ben) VALUES ('$id')");
}

header("location:profile.php?id=$id");

?>