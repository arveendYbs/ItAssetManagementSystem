<?php
/**
 * Simple Upload Test
 * Location: test_upload.php (root directory)
 * Purpose: Test if file uploads are working at all
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>File Upload Test</h1>";

if ($_POST) {
    echo "<h2>POST Data Received:</h2>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
    echo "<h2>FILES Data:</h2>";
    echo "<pre>" . print_r($_FILES, true) . "</pre>";
    
    if (isset($_FILES['test_file'])) {
        $file = $_FILES['test_file'];
        
        echo "<h2>File Analysis:</h2>";
        echo "File Name: " . $file['name'] . "<br>";
        echo "File Size: " . $file['size'] . " bytes<br>";
        echo "File Type: " . $file['type'] . "<br>";
        echo "File Error: " . $file['error'] . "<br>";
        echo "Temp File: " . $file['tmp_name'] . "<br>";
        echo "Temp File Exists: " . (file_exists($file['tmp_name']) ? 'YES' : 'NO') . "<br>";
        
        if ($file['error'] == 0 && file_exists($file['tmp_name'])) {
            echo "<h2>File Contents (first 500 chars):</h2>";
            $content = file_get_contents($file['tmp_name']);
            echo "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "</pre>";
        }
    }
} else {
    echo "<p>No POST data received yet.</p>";
}

echo "<h2>PHP Configuration:</h2>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
echo "file_uploads: " . (ini_get('file_uploads') ? 'ON' : 'OFF') . "<br>";

?>

<h2>Test Form:</h2>
<form method="POST" enctype="multipart/form-data">
    <p>
        <label>Select any file:</label><br>
        <input type="file" name="test_file" required>
    </p>
    
    <p>
        <input type="text" name="test_text" placeholder="Type something here" value="test123">
    </p>
    
    <p>
        <button type="submit">Test Upload</button>
    </p>
</form>

<h2>Instructions:</h2>
<ol>
    <li>Select any small file (txt, csv, anything)</li>
    <li>Click "Test Upload"</li>
    <li>Check if you see the file contents above</li>
    <li>If this works, the problem is in the import script</li>
    <li>If this doesn't work, there's a server configuration issue</li>
</ol>

<p><strong>Delete this file after testing!</strong></p>