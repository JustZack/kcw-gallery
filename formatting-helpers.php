<?php

//Filter a name so it reads well
function kcw_gallery_FilterName($gallery_name) {
    if ($gallery_name == NULL) 
        return NULL;
    else {
        $name = str_replace("-", " ", $gallery_name);
        $name = ucwords($name);
        return $name;
    }
}

?>