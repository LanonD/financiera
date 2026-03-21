<<<<<<< HEAD
<?php
session_start();
if (isset($_SESSION['puesto'])) {
    $puesto = $_SESSION['puesto'];
    if ($puesto === 'admin')     { header("Location: admin_view.php"); exit(); }
    if ($puesto === 'collector') { header("Location: collector_view.php"); exit(); }
    if ($puesto === 'promo')     { header("Location: promo_view.php"); exit(); }
}
header("Location: login.php");
exit();
?>
=======
<?php 
//landing
?>
>>>>>>> 7ef515193e152815a482d2fee9480b090bb8b63e
