<?php
include __DIR__ . '/dbcon.php';
function getsupply(){
$s=mysqli_query($con,"SELECT * FROM medsupply");

while($row=mysqli_fetch_assoc($s)){
    echo $row['name'];
    echo $row['quantity']; 
    //category,expdate,datereceived,itemcode
};
}
function getusers(){
$s=mysqli_query($con,"SELECT * FROM users");

while($row=mysqli_fetch_assoc($s)){
    echo $row['User_Name'];
    echo $row['PaSS']; 
};
}

function getLogs(){
    $s=mysqli_query($con,"SELECT * FROM logs");
    
    while($row=mysqli_fetch_assoc($s)){
        echo $row[''];
        echo $row['']; 
        //itemcode,itemname,quantity,transaction
    };
    }

function barangay(){
$s=mysqli_query($con,"SELECT * FROM barangays");

while($row=mysqli_fetch_assoc($s)){
    echo $row['brgy']."<br>";
};
}


function patient(){
$s=mysqli_query($con,"SELECT * FROM patient");

while($row=mysqli_fetch_assoc($s)){
    echo $row['Fname']."<br>";
    //Lname,Mname,adress,Birthday,age,gender,Medication
};
}
?>