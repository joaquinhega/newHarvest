<?php
function obtenerUltimoRemitoPorChofer($letra_chofer, $conn) {
    $query = "SELECT id_remito_c FROM combustible WHERE id_remito_c LIKE '$letra_chofer%' ORDER BY id_remito_c DESC LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result) {
        return mysqli_fetch_row($result)[0]; 
    }
    return null; 
}
?>
