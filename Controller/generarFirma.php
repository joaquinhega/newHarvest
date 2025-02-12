<?php
if (isset($_POST['signature'])) {
    // Recibir el contenido de la firma (base64)
    $image = $_POST['signature'];
    
    // Eliminar la parte de "data:image/png;base64," para quedarnos solo con los datos base64
    $image = str_replace('data:image/png;base64,', '', $image);
    $image = str_replace(' ', '+', $image);
    
    // Decodificar los datos base64 para convertirlos en un archivo binario
    $data = base64_decode($image);
    
    // Definir la ruta y nombre del archivo para guardarlo
    $file = '../firmas/' . uniqid() . '.png';
    
    // Guardar la firma como un archivo PNG
    if (file_put_contents($file, $data)) {
        echo "Firma guardada exitosamente en " . $file;
    } else {
        echo "Error al guardar la firma.";
    }
}
?>
