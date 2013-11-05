<?php

/**
 * Class ImagenationGD2
 * Реализованно для GD2
 * @author Xakki
 */
class ImagenationGD2
{

    // Меняет размер обрезая
    static function thumbnailImage($InFile, $OutFile, $WidthX, $HeightY)
    {
        $trueX = $WidthX;
        $trueY = $HeightY;
        Imagenation::_chmod($InFile);
        list($width_orig, $height_orig) = getimagesize($InFile);

        if (!$trueX and !$trueY) {
            return true;
        }
        if (!$trueX) {
            $WidthX = $trueX = ($width_orig * $trueY) / $height_orig;
        }
        if (!$trueY) {
            $HeightY = $trueY = ($height_orig * $trueX) / $width_orig;
        }

        $ratio_orig = $width_orig / $height_orig;
        if ($WidthX / $HeightY > $ratio_orig) {
            $HeightY = $WidthX / $ratio_orig;
        }
        else {
            $WidthX = $HeightY * $ratio_orig;
        }
        /*Создаем пустое изображение на вывод*/
        if (!($thumb = @imagecreatetruecolor($WidthX, $HeightY))) {
            throw new Exception('Cannot Initialize new GD image stream');
        }
        /*Определяем тип рисунка*/
        if (!$imtype = self::_get_type($InFile)) { // опред тип файла
            throw new Exception('File is not image ' . $InFile);
        }

        /*Обработка только jpeg, gif, png*/
        if ($imtype > 3) {
            trigger_error('Данный тип изображения не поддерживается на данный момент, рекомендуем использовать JPEG, PNG или GIF', E_USER_NOTICE);
            copy($InFile, $OutFile);
            return true;
        }
        if ($imtype == 3) {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
        }

        /*Открываем исходный рисунок*/
        if (!$source = self::_imagecreatefrom($InFile, $imtype)) { //открываем рисунок
            throw new Exception('File ' . $InFile . ' is not image');
        }

        if (!imagecopyresampled($thumb, $source, 0, 0, 0, 0, $WidthX, $HeightY, $width_orig, $height_orig)) {
            throw new Exception('Error imagecopyresampled ' . $thumb);
        }

        if (!($thumb2 = @imagecreatetruecolor($trueX, $trueY))) {
            throw new Exception('Cannot Initialize new GD image stream');
        }

        if ($imtype == 3) {
            imagealphablending($thumb2, false);
            imagesavealpha($thumb2, true);
//            imagealphablending($thumb2, false);
//            $col=imagecolorallocatealpha($thumb2,255,255,255,127);
//            imagefilledrectangle($thumb2,0,0,485, 500,$col);
//            imagealphablending($thumb2,true);
        }


        if (!imagecopyresampled($thumb2, $thumb, 0, 0, $WidthX / 2 - $trueX / 2, $HeightY / 2 - $trueY / 2, $trueX, $trueY, $trueX, $trueY)) {
            throw new Exception('Error imagecopyresampled');
        }


        self::_image_to_file($thumb2, $OutFile, Imagenation::$quality, $imtype); //сохраняем в файл

        if (!file_exists($OutFile)) {
            throw new Exception('Cant create file');
        }
        return true;
    }


    /**
     * обрезает картинку
     * @param $InFile
     * @param $OutFile
     * @param $WidthX
     * @param $HeightY
     * @return bool
     * @throws Exception
     */
    static function cropImage($InFile, $OutFile, $WidthX, $HeightY)
    {
        Imagenation::_chmod($InFile);
        list($width_orig, $height_orig) = getimagesize($InFile); // опред размер

        if (!$WidthX and !$HeightY)
            return true;
        if (!$WidthX)
            $WidthX = ($width_orig * $HeightY) / $height_orig;
        if (!$HeightY) {
            $HeightY = ($height_orig * $WidthX) / $width_orig;
        }

        // Resample
        $thumb = imagecreatetruecolor($WidthX, $HeightY); //созд пустой рисунок
        if (!$imtype = self::_get_type($InFile)) { // опред тип файла
            throw new Exception('File is not image ' . $InFile);
        }
        if ($imtype > 3) {
            trigger_error('Данный тип изображения не поддерживается на данный момент, рекомендуем использовать JPEG, PNG или GIF', E_USER_NOTICE);
            copy($InFile, $OutFile);
            return true;
        }
        $source = self::_imagecreatefrom($InFile, $imtype); //открываем рисунок
        if (!$source) {
            throw new Exception('Unable to open image file ' . $InFile);
        }

        imagecopyresampled($thumb, $source, 0, 0, $width_orig / 2 - $WidthX / 2, $height_orig / 2 - $HeightY / 2, $WidthX, $HeightY, $WidthX, $HeightY);
        self::_image_to_file($thumb, $OutFile, Imagenation::$quality, $imtype); //сохраняем в файл
        if (!file_exists($OutFile)) {
            throw new Exception('Cant create output file ' . $OutFile);
        }
        return true;
    }


    /**
     * Наложение водяного знака (маркера)
     *
     */
    static function waterMark($InFile, $OutFile, $logoFile, $posX = 0, $posY = 0)
    {
        if (!$imtypeIn = self::_get_type($InFile)) { // опред тип файла
            throw new Exception('File ' . $InFile . ' is not image');
        }

        if ($imtypeIn > 3) return false;

        if (!$imtypeLogo = self::_get_type($logoFile)) { // опред тип файла
            throw new Exception('File ' . $logoFile . ' is not image');
        }
        if ($imtypeLogo > 3) return false;

        $znak_hw = getimagesize($logoFile);
        $foto_hw = getimagesize($InFile);

        $znak = self::_imagecreatefrom($logoFile, $imtypeLogo);
        if (!$znak) {
            throw new Exception("Unable to open image file " . $logoFile);
        }

        $foto = self::_imagecreatefrom($InFile, $imtypeIn);
        if (!$foto) {
            throw new Exception('error', "Unable to open image file " . $InFile);
        }

        imagecopy(
            $foto,
            $znak,
            $foto_hw[0] - $znak_hw[0],
            $foto_hw[1] - $znak_hw[1],
            0,
            0,
            $znak_hw[0],
            $znak_hw[1]
        );
        if (file_exists($OutFile)) {
            Imagenation::_chmod($OutFile);
            unlink($OutFile);
        }
        self::_image_to_file($foto, $OutFile, Imagenation::$quality, $imtypeIn); //сохраняем в файл
        imagedestroy($znak);
        imagedestroy($foto);
        if (!file_exists($OutFile)) {
            throw new Exception('Cant save file to ' . $OutFile);
        }
        return true;
    }

    // Меняет размер. пропорционально, до минимального соответсявия по стороне
    static function _resizeImage($InFile, $OutFile, $WidthX, $HeightY)
    {
        Imagenation::_chmod($InFile);
        list($width_orig, $height_orig) = getimagesize($InFile); // опред размер

        if (!$WidthX and !$HeightY)
            return true;
        if (!$WidthX)
            $WidthX = ($width_orig * $HeightY) / $height_orig;
        if (!$HeightY) {
            $HeightY = ($height_orig * $WidthX) / $width_orig;
        }

        if ($width_orig < $WidthX and $height_orig < $HeightY) {
            if ($InFile != $OutFile) {
                copy($InFile, $OutFile);
                Imagenation::_chmod($OutFile);
            }
            return true;
        }
        elseif ($width_orig / $WidthX < $height_orig / $HeightY) {
            $WidthX = round($HeightY * $width_orig / $height_orig);
        }
        elseif ($width_orig / $WidthX > $height_orig / $HeightY) {
            $HeightY = round($WidthX * $height_orig / $width_orig);
        }

        $thumb = imagecreatetruecolor($WidthX, $HeightY); //созд пустой рисунок
        if (!$imtype = self::_get_type($InFile)) { // опред тип файла
            throw new Exception('File ' . $InFile . ' is not image');
        }

        if ($imtype > 3) {
            trigger_error('Данный тип изображения не поддерживается на данный момент, рекомендуем использовать JPEG, PNG или GIF', E_USER_NOTICE);
            copy($InFile, $OutFile);
            return true;
        }

        $source = self::_imagecreatefrom($InFile, $imtype); //открываем рисунок
        if (!$source) {
            throw new Exception("Unable to open image file " . $InFile);
        }

        imagecopyresized($thumb, $source, 0, 0, 0, 0, $WidthX, $HeightY, $width_orig, $height_orig); //меняем размер
        self::_image_to_file($thumb, $OutFile, Imagenation::$quality, $imtype); //сохраняем в файл
        if (!file_exists($OutFile)) {
            throw new Exception('Cant create output file ' . $OutFile);
        }
        return true;
    }

    /*************** Helper function *********************/

    static function _imagecreatefrom($im_file, $imtype = null)
    {
        if (is_null($imtype)) {
            $imtype = self::_get_type($im_file);
        }
        /*
Возвращаемое значение	Константа
1	IMAGETYPE_GIF
2	IMAGETYPE_JPEG
3	IMAGETYPE_PNG
4	IMAGETYPE_SWF
5	IMAGETYPE_PSD
6	IMAGETYPE_BMP
7	IMAGETYPE_TIFF_II
8	IMAGETYPE_TIFF_MM
9	IMAGETYPE_JPC
10	IMAGETYPE_JP2
11	IMAGETYPE_JPX
12	IMAGETYPE_JB2
13	IMAGETYPE_SWC
14	IMAGETYPE_IFF
15	IMAGETYPE_WBMP
16	IMAGETYPE_XBM
        */
        if ($imtype == 1) {
            if (!($image = @imagecreatefromgif($im_file))) {
                throw new Exception('Can not create a new image from file');
            }
        }
        elseif ($imtype == 2) {
            if (!($image = imagecreatefromjpeg($im_file))) {
                throw new Exception('Can not create a new image from file');
            }
        }
        elseif ($imtype == 3) {
            if (!($image = imagecreatefrompng($im_file))) {
                throw new Exception('Can not create a new image from file');
            }
        }
        else return false;
        return $image;
    }

    static function _image_to_file($im, $file, $q, $imtype)
    {
        if ($imtype == 1) {
            imagegif($im, $file, $q);
        }
        elseif ($imtype == 2) {
            imagejpeg($im, $file, $q);
        }
        elseif ($imtype == 3) {
            imagepng($im, $file, 8);
        }
        else {
            return false;
        }
        return true;
    }

    static function _is_image($file)
    {
        $res = exif_imagetype($file);
        return ($res > 0 && $res < 4);
    }

    static function _get_type($file)
    {
        return exif_imagetype($file);
    }

    static function _get_ext($file)
    {
        return image_type_to_extension($file);
    }
}
