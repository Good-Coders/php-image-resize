<?php

namespace Eventviva;

class ImageResize
{

    protected $image;
    protected $image_type;

    public function __construct($filename) {
        $this->load($filename);
    }

    public function load($filename) {
        $this->image_type = exif_imagetype($filename);

        if ($this->image_type === IMAGETYPE_JPEG) {
            $this->image = imagecreatefromjpeg($filename);
        } elseif ($this->image_type === IMAGETYPE_GIF) {
            $this->image = imagecreatefromgif($filename);
        } elseif ($this->image_type === IMAGETYPE_PNG) {
            $this->image = imagecreatefrompng($filename);
        }
        return $this;
    }

    public function save($filename, $image_type = null, $compression = 75, $permissions = null) {
        if ($image_type === null) {
            return $this->saveSameImageType($filename, $compression, $permissions);
        }

        if ($image_type === IMAGETYPE_JPEG) {
            imagejpeg($this->image, $filename, $compression);
        } elseif ($image_type == IMAGETYPE_GIF) {

            imagegif($this->image, $filename);
        } elseif ($image_type === IMAGETYPE_PNG) {

            imagealphablending($this->image, false);
            imagesavealpha($this->image, true);
            imagepng($this->image, $filename);
        }
        if ($permissions !== null) {
            chmod($filename, $permissions);
        }
        return $this;
    }

    public function saveSameImageType($filename, $compression = 75, $permissions = null) {
        if ($this->image_type === IMAGETYPE_JPEG) {
            $filename .= (preg_match('/\.[a-z]{3,4}$/i', $filename)) ? '' : '.jpg';
            imagejpeg($this->image, $filename, $compression);
        } elseif ($this->image_type === IMAGETYPE_GIF) {
            $filename .= (preg_match('/\.[a-z]{3,4}$/i', $filename)) ? '' : '.gif';
            imagegif($this->image, $filename);
        } elseif ($this->image_type === IMAGETYPE_PNG) {
            $filename .= (preg_match('/\.[a-z]{3,4}$/i', $filename)) ? '' : '.png';
            imagepng($this->image, $filename);
        }
        if ($permissions !== null) {
            chmod($filename, $permissions);
        }
        return $this;
    }

    public function output($image_type = null) {
        if ($image_type === null) {
            $this->outputSameImageType();
        }
        if ($image_type === IMAGETYPE_JPEG) {
            imagejpeg($this->image);
        } elseif ($image_type === IMAGETYPE_GIF) {
            imagegif($this->image);
        } elseif ($image_type === IMAGETYPE_PNG) {
            imagepng($this->image);
        }
    }

    public function outputSameImageType() {
        if ($this->image_type === IMAGETYPE_JPEG) {
            imagejpeg($this->image);
        } elseif ($this->image_type === IMAGETYPE_GIF) {
            imagegif($this->image);
        } elseif ($this->image_type === IMAGETYPE_PNG) {
            imagepng($this->image);
        }
    }

    public function getWidth() {

        return imagesx($this->image);
    }

    public function getHeight() {

        return imagesy($this->image);
    }

    public function resizeToHeight($height) {

        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        $this->resize($width, $height);
        return $this;
    }

    public function resizeToWidth($width) {
        $ratio = $width / $this->getWidth();
        $height = $this->getheight() * $ratio;
        $this->resize($width, $height);
        return $this;
    }

    public function scale($scale) {
        $width = $this->getWidth() * $scale / 100;
        $height = $this->getheight() * $scale / 100;
        $this->resize($width, $height);
        return $this;
    }

    public function smartResize($width, $height, $cropOverResize = false, $minWidth = false, $minHeight = false) {
        // Store the aspect ratio
        $aspect_o = $this->getWidth() / $this->getHeight();
        $aspect_f = $width / $height;

        // smart crop should reduce the opposite side down then perform crop!
        if ($cropOverResize) {
            if (((!$minWidth && !$minHeight) || ($minWidth && $minHeight)) && $this->getWidth() > $width && $this->getHeight() > $height) {
                // TODO : Fix, this should smartly resize and crop image appropriately
                // TODO : Should shrink based on if resulting image is hot dog hamburger or square.
                // If hot dog it should resize it by width
                // If hamburger it should resize it by height
                /*
                    If square it should resize it by the smaller dimension
                        if orig is
                            hot dog then height
                            hamburger then width
                            if square then either IE same result
                */
                if ($aspect_o >= $aspect_f) {
                    $this->resizeToWidth($width);
                } else {
                    $this->resizeToHeight($height);
                }
            }  else if ($minHeight && $this->getWidth() > $width) {
                // TODO : Fix, this should smartly resize and crop image appropriately
                $this->resizeToWidth($width);
            } else if ($minWidth && $this->getHeight() > $height) {
                // TODO : Fix, this should smartly resize and crop image appropriately
                $this->resizeToWidth($height);
            }

            // After resize
            if ((!$minWidth && !$minHeight) || ($minWidth && $this->getWidth() < $width) || ($minHeight && $this->getHeight() < $height)) {
                $this->canvas($width, $height);
            }

        } else { // Not Crop
            if (((!$minWidth && !$minHeight) || ($minWidth && $minHeight)) && ($this->getWidth() > $width || $this->getHeight() > $height)) {
                if ($aspect_o >= $aspect_f) {
                    $this->resizeToWidth($width);
                } else {
                    $this->resizeToHeight($height);
                }
            } else if ($minHeight && $this->getWidth() > $width) {
                $this->resizeToWidth($width);
            } else if ($minWidth && $this->getHeight() > $height) {
                $this->resizeToHeight($height);
            }

            // After resize
            if ((!$minWidth && !$minHeight) || ($minWidth && $this->getWidth() < $width) || ($minHeight && $this->getHeight() < $height)) {
                $this->canvas($width, $height);
            }
        }
        return $this;
    }

    public function canvas($width, $height) {
        $new_image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($new_image, 255, 255, 255);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);

        $fillColor = $white;
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);

        /* Check if this image is PNG or GIF, then set if Transparent */
        if (($this->image_type === IMAGETYPE_GIF) || ($this->image_type === IMAGETYPE_PNG)) {
            $fillColor = $transparent;
            imagefilledrectangle($new_image, 0, 0, $width, $height, $fillColor);
            imagesavealpha($this->image, true);
        } else {
            imagefill($new_image, 0, 0, $fillColor);
        }

        $offsetX = ($width - $this->getWidth()) * 0.5 * -1;
        $offsetY = ($height - $this->getHeight()) * 0.5 * -1;
        imagecopyresampled($new_image, $this->image, 0, 0, $offsetX, $offsetY, $width, $height, $width, $height);
        $offsetX = abs($offsetX);
        $offsetY = abs($offsetY);

        // Fill the sides with color OR transparency
        if ($offsetX > 0 && $width > $this->getWidth()) {
            imagefilledrectangle($new_image, 0, 0, $offsetX, $height, $fillColor);
            imagefilledrectangle($new_image, $offsetX + $this->getWidth(), 0, $width, $height, $fillColor);
        }

        // Fill the top and bottom with color OR transparency
        if ($offsetY > 0 && $height > $this->getHeight()) {
            imagefilledrectangle($new_image, 0, 0, $width, $offsetY, $fillColor);
            imagefilledrectangle($new_image, 0, $offsetY + $this->getHeight(), $width, $height, $fillColor);
        }

        $this->image = $new_image;
        return $this;
    }

    public function resize($width, $height, $forcesize = false) {
        /* optional. if file is smaller, do not resize. */
        if ($forcesize === false) {
            if ($width > $this->getWidth() && $height > $this->getHeight()) {
                $width = $this->getWidth();
                $height = $this->getHeight();
            }
        }

        $new_image = imagecreatetruecolor($width, $height);
        /* Check if this image is PNG or GIF, then set if Transparent */
        if (($this->image_type === IMAGETYPE_GIF) || ($this->image_type === IMAGETYPE_PNG)) {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);
        }
        imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());

        $this->image = $new_image;
        return $this;
    }

    /* center crops image to desired width height */
    public function crop($width, $height) {
        $aspect_o = $this->getWidth() / $this->getHeight();
        $aspect_f = $width / $height;

        if ($aspect_o >= $aspect_f) {
            $width_n = $this->getWidth() / ($this->getHeight() / $height);
            $height_n = $height;
        } else {
            $width_n = $width;
            $height_n = $this->getHeight() / ($this->getWidth() / $width);
        }

        $new_image = imagecreatetruecolor($width, $height);
        /* Check if this image is PNG or GIF, then set if Transparent */
        if (($this->image_type === IMAGETYPE_GIF) || ($this->image_type === IMAGETYPE_PNG)) {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $width, $height, $transparent);
        }
        imagecopyresampled($new_image, $this->image, 0 - ($width_n - $width) * 0.5, 0 - ($height_n - $height) * 0.5, 0, 0, $width_n, $height_n, $this->getWidth(), $this->getHeight());

        $this->image = $new_image;
        return $this;
    }

}
