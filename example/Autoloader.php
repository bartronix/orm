<?php
function autoloadSystem($className) {
    $paths = array(
        '/example/entities/', 
        '/example/mappers/',
        '/orm/'
    );
    $parts = explode('\\',$className);
    $className = end($parts);
    $file = $className . '.php';
    for ($i = 0; $i < count($paths); $i++) {
        if(file_exists($_SERVER['DOCUMENT_ROOT'] . $paths[$i] . $file))
        {
            include_once $_SERVER['DOCUMENT_ROOT'] . $paths[$i].$file;
        } 
    }
}
spl_autoload_register("autoloadSystem");