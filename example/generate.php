<?php

/*set the dir of mappers and entities*/
error_reporting(E_ALL);
ini_set('display_errors', '1');
define('MAPPERS_DIR', 'mappers');
define('ENTITIES_DIR', 'entities');
require_once 'Autoloader.php';
$db = new PdoAdapter();
$files = scandir(MAPPERS_DIR);
foreach ($files as $file) {
    $fullPath = MAPPERS_DIR.'/'.$file;
    if (is_file($fullPath)) {
        require_once $fullPath;
        echo 'Mapper class found: '.getClass($fullPath).'<br />',
        $class = getClass($fullPath);
        $tmp = new $class($db);
        $script = 'CREATE TABLE '.$tmp->getDataSource().' (';
        foreach ($tmp->getDbFields() as $key => $value) {
            $script .= $key.' '.$value['dbtype'];
            if (isset($value['primary'])) {
                $script .= ' PRIMARY KEY';
            }
            if (isset($value['auto_increment'])) {
                $script .= ' AUTO_INCREMENT';
            }
            $script .= ',';
        }
        $script = substr($script, 0, -1).');';
        //execute script
        $db->query($script);
        echo 'Database script executed successfully.<br />';
        //create classfile
        if (!file_exists(ENTITIES_DIR)) {
            $test = @mkdir(ENTITIES_DIR, 0755, true);
        }
        chmod(ENTITIES_DIR, 0777);
        $classFile = fopen(ENTITIES_DIR.'/'.$tmp->getEntityClass().'.php', 'w') or die('Unable to create file!');
        chmod(ENTITIES_DIR, 0755);
        $output = "<?php \nclass ".$tmp->getEntityClass()." extends BaseEntity{ \n} \n?>";
        fwrite($classFile, $output);
        fclose($classFile);
        echo 'Entity class created successfully.<br />';
        echo '<br /><br />';
    }
}
echo 'Finished.<br />';

function getClass($file)
{
    $php_file = file_get_contents($file);
    $tokens = token_get_all($php_file);
    $class_token = false;
    foreach ($tokens as $token) {
        if (is_array($token)) {
            if ($token[0] == T_CLASS) {
                $class_token = true;
            } elseif ($class_token && $token[0] == T_STRING) {
                return $token[1];
                $class_token = false;
            }
        }
    }
}
