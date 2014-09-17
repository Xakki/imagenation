<?php
/**
 * Class Imagenation
 * Реализованно для GD2 , Imagick PHP и ImageMagick
 * @author Xakki
 */
class Imagenation
{
    public static $filemod = 0764;

    public static $quality = 80;

    /**
     * GD2
     * Imagick
     * ImageMagick cmd - very fast method
     */
    public static $method = 'GD2';

    /**
     * Меняет размер обрезая
     * @param $InFile
     * @param $OutFile
     * @param $WidthX
     * @param $HeightY
     *
     * @return bool
     * @throws Exception
     */
    static function thumbnailImage($InFile, $OutFile, $WidthX, $HeightY)
    {
        if (!$WidthX and !$HeightY) {
            return true;
        }

        self::_chmod($InFile);

        if (self::$method == 'GD2') {
            include_once(__DIR__ . '/ImagenationGD2.php');
            return ImagenationGD2::thumbnailImage($InFile, $OutFile, $WidthX, $HeightY);
        }
        elseif (self::$method == 'Imagick') {
            if (class_exists('Imagick', false)) {
                throw new Exception('Need instal Imagick plugin');
            }
            exit('TODO: Imagick');
            $thumb = new Imagick($InFile);
            if ($crop) {
                $thumb->cropThumbnailImage($WidthX, $HeightY);
            }
            else {
                $thumb->thumbnailImage($WidthX, $HeightY, true);
            }
            $res = $thumb->writeImage($OutFile);
            $thumb->destroy();
        }
        elseif (self::$method == 'ImageMagick') {
            if ($crop) {
                $cmd = 'convert ' . escapeshellarg($InFile) . ' -resize "' . $WidthX . 'x' . $HeightY . '^" -gravity center -crop ' . $WidthX . 'x' . $HeightY . '+0+0 +repage  ' . escapeshellarg($OutFile);
            }
            else {
                $cmd = 'convert ' . escapeshellarg($InFile) . ' -thumbnail "' . $WidthX . 'x' . $HeightY . '" ' . escapeshellarg($OutFile);
            }

            $out = array();
            $err = 0;
            $run = exec($cmd, $out, $err);
            if ($err) {
                throw new Exception('Error from ImageMagick: ' . $err);
            }
        }
        else {
            throw new Exception('Use incorrect method ' . self::$method);
        }

        self::_chmod($OutFile);

        return true;
    }

    // обрезает
    static function cropImage($InFile, $OutFile, $WidthX, $HeightY, $posX = 0, $posY = 0)
    {
        if (!$WidthX and !$HeightY) {
            return true;
        }
        if (!$WidthX) $WidthX = '';
        if (!$HeightY) $HeightY = '';

        self::_chmod($InFile);

        if (self::$method == 'GD2') {
            include_once(__DIR__ . '/ImagenationGD2.php');
            return ImagenationGD2::cropImage($InFile, $OutFile, $WidthX, $HeightY, $posX, $posY);
        }
        elseif (self::$method == 'Imagick') {
            if (class_exists('Imagick', false)) {
                throw new Exception('Need instal Imagick plugin');
            }
            $thumb = new Imagick($InFile);
            $thumb->cropImage($WidthX, $HeightY, $posX, $posY);
            $res = $thumb->writeImage($OutFile);
            $thumb->destroy();
        }
        elseif (self::$method == 'ImageMagick') {
            $cmd = 'convert ' . escapeshellarg($InFile) . ' -gravity Center -crop ' . $WidthX . 'x' . $HeightY . '+0 ' . escapeshellarg($OutFile);
            $out = array();
            $err = 0;
            $run = exec($cmd, $out, $err);
            if ($err) {
                throw new Exception('Error from ImageMagick: ' . $err);
            }
        }
        else {
            throw new Exception('Use correct method');
        }

        self::_chmod($OutFile);

        return true;
    }

    /**
     * Наложение водяного знака (маркера)
     *
     */
    static function waterMark($InFile, $OutFile, $logoFile, $posX = 0, $posY = 0)
    {
        if (!self::_is_image($logoFile)) { // опред тип файла
            throw new Exception('File ' . $logoFile . ' is not image');
        }

        if (!$imtypeIn = self::_is_image($InFile)) { // опред тип файла
            throw new Exception('File ' . $InFile . ' is not image');
        }

        $res = true;
        self::_chmod($InFile);
        list($width_orig, $height_orig) = getimagesize($InFile); // опред размер


        if (self::$method == 'GD2') {
            include_once(__DIR__ . '/ImagenationGD2.php');
            return ImagenationGD2::waterMark($InFile, $OutFile, $logoFile, $posX, $posY);
        }
        elseif (self::$method == 'Imagick') {
            if (class_exists('Imagick', false)) {
                throw new Exception('Need instal Imagick plugin');
            }
            if (strpos($posX, '%') !== false)
                $posX = $width_orig * substr($posX, 0, -1) / 100;
            if (strpos($posY, '%') !== false)
                $posY = $height_orig * substr($posY, 0, -1) / 100;

            $thumb = new Imagick($InFile);
            $logo = new Imagick($logoFile);
            $thumb->compositeImage($logo, imagick::COMPOSITE_DEFAULT, $posX, $posY);
            $res = $thumb->writeImage($OutFile);
            $thumb->destroy();
        }
        elseif (self::$method == 'ImageMagick') {
            //southeast //center
            //$cmd = 'composite -compose bumpmap -gravity south '.escapeshellarg($InFile).' '.escapeshellarg($logoFile).' '.escapeshellarg($OutFile);
            $cmd = 'convert ' . escapeshellarg($InFile) . ' -gravity SouthWest -draw "image Over 0,0,0,0 ' . escapeshellarg($logoFile) . '" ' . escapeshellarg($OutFile);
            $out = array();
            $err = 0;
            $run = exec($cmd, $out, $err);
            if ($err) {
                throw new Exception('Error from ImageMagick: ' . $err);
            }
        }
        else {
            throw new Exception('Use correct method');
        }


        self::_chmod($OutFile);

        return true;
    }

    /**************** VALIDATE ************************/

    static function validImageTypeExt($file, $fileExt)
    {
        include_once(__DIR__ . '/ImagenationGD2.php');
        if (!$c_type = ImagenationGD2::_get_type($file)) {
            return true;
        }
        $fileExt = str_replace('jpeg','jpg', $fileExt);

        $TrueExt = ImagenationGD2::_get_ext($c_type);
        $TrueExt = str_replace('jpeg','jpg', $TrueExt);
        $TrueExt = trim($TrueExt, '. ');


        if($TrueExt!==$fileExt) {
            return false;
        }
        return true;
    }


    /*************** Helper function *********************/

    static function _is_image($file)
    {
        return exif_imagetype($file);
    }

    static function _get_ext($file, $include_dot = false)
    {
        return image_type_to_extension($file, $include_dot);
    }

    /**
     * get image color in RGB format function
     * @param $imageFile_URL
     * @param int $numColors
     * @param int $image_granularity
     * @return array
     * @throws Exception
     */
    static function getImageColor($imageFile_URL, $numColors = 10, $image_granularity = 5)
    {
        $image_granularity = max(1, abs((int)$image_granularity));
        $colors = array();
        //find image size
        $size = getimagesize($imageFile_URL);
        if ($size === false) {
            throw new Exception('File ' . $imageFile_URL . ": Unable to get image size data");
        }

        // open image
        //$img = @imagecreatefromjpeg($imageFile_URL);
        $img = ImagenationGD2::_imagecreatefrom($imageFile_URL);
        if (!$img) {
            throw new Exception('File ' . $imageFile_URL . ": Unable to open image file");
        }

        // fetch color in RGB format
        for ($x = 0; $x < $size[0]; $x += $image_granularity) {
            for ($y = 0; $y < $size[1]; $y += $image_granularity) {
                $thisColor = imagecolorat($img, $x, $y);
                $rgb = imagecolorsforindex($img, $thisColor);
                $red = round(round(($rgb['red'] / 0x33)) * 0x33);
                $green = round(round(($rgb['green'] / 0x33)) * 0x33);
                $blue = round(round(($rgb['blue'] / 0x33)) * 0x33);
                $thisRGB = sprintf('%02X%02X%02X', $red, $green, $blue);
                if (array_key_exists($thisRGB, $colors)) {
                    $colors[$thisRGB]++;
                }
                else {
                    $colors[$thisRGB] = 1;
                }
            }
        }
        arsort($colors);
        // returns maximum used color of image format like #C0C0C0.
        return array_slice(($colors), 0, $numColors, true);
    }

    /**
     * RGB-Colorcodes(i.e: 255 0 255) to HEX-Colorcodes (i.e: FF00FF)
     * example - print_r(rgb2hex(array(10,255,255)));
     */
    static function rgb2hex($rgb)
    {
        if (strlen($hex = dechex($rgb)) == 1) {
            $hex = "0" . $hex;
        }
        return $hex;
    }

    /**
     * html(HEX) color to convert in RGB format color like R(255) G(255) B(255)
     */
    static function getHtml2Rgb($str_color)
    {
        if ($str_color[0] == '#')
            $str_color = substr($str_color, 1);

        if (strlen($str_color) == 6)
            list($r, $g, $b) = array($str_color[0] . $str_color[1],
                $str_color[2] . $str_color[3],
                $str_color[4] . $str_color[5]);
        elseif (strlen($str_color) == 3)
            list($r, $g, $b) = array($str_color[0] . $str_color[0], $str_color[1] . $str_color[1], $str_color[2] . $str_color[2]);
        else
            return false;

        $r = hexdec($r);
        $g = hexdec($g);
        $b = hexdec($b);
        $arr_rgb = array($r, $g, $b);
        // Return colors format liek R(255) G(255) B(255)
        return $arr_rgb;
    }

    /**
     * Поворот изображений
     * @param $path
     * @param $angle
     *
     * @return bool
     * @throws Exception
     */
    public static function imgRotate($path, $angle)
    {
        self::_chmod($path);

        if (self::$method == 'GD2') {
            include_once(__DIR__ . '/ImagenationGD2.php');
            return ImagenationGD2::imgRotate($path, $angle);
        }
        elseif (self::$method == 'Imagick') {
            // TODO for Imagick
        }
        elseif (self::$method == 'ImageMagick') {
            // TODO for ImageMagick
        }
        else {
            throw new Exception('Use correct method');
        }
    }
    /*************** TOOLS *******************/


    static function _chmod($file)
    {
        if (self::$filemod) {
            chmod($file, self::$filemod);
        }
    }

}
