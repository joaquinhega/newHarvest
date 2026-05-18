<?php
if (isset($_POST['signature'])) {
    $image = $_POST['signature'];
    
    $image = str_replace('data:image/png;base64,', '', $image);
    $image = str_replace(' ', '+', $image);
    
    $data = base64_decode($image);
    
    $file = '../firmas/' . uniqid() . '.png';
    
    if (file_put_contents($file, $data)) {
        echo "Firma guardada exitosamente en " . $file;
    } else {
        echo "Error al guardar la firma.";
    }
}
?>
