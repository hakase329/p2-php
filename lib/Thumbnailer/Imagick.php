<?php
/**
 * Thumbnailer_Imagick
 * PHP Version 5
 */

// {{{ Thumbnailer_Imagick

/**
 * Image manipulation class which uses imagick php extension version 2.0 or later.
 */
class Thumbnailer_Imagick extends Thumbnailer
{
    // {{{ save()

    /**
     * Convert and save.
     *
     * @param string $source
     * @param string $thumbnail
     * @param array $size
     * @return boolean
     * @throws PEAR_Error
     */
    public function save($source, $thumbnail, $size)
    {
        try {
            return $this->decorate($source, $this->_convert($source, $size))->writeImage($thumbnail);
        } catch (Exception $e) {
            return PEAR::raiseError(get_class($e) . '::' . $e->getMessage());
        }
    }

    // }}}
    // {{{ capture()

    /**
     * Convert and capture.
     *
     * @param string $source
     * @param array $size
     * @return string
     * @throws PEAR_Error
     */
    public function capture($source, $size)
    {
        try {
            return $this->_convert($source, $size)->getImageBlob();
        } catch (Exception $e) {
            return PEAR::raiseError(get_class($e) . '::' . $e->getMessage());
        }
    }

    // }}}
    // {{{ output()

    /**
     * Convert and output.
     *
     * @param string $source
     * @param string $name
     * @param array $size
     * @return boolean
     * @throws PEAR_Error
     */
    public function output($source, $name, $size)
    {
        try {
            $blob = $this->_convert($source, $size)->getImageBlob();
            if ($blob) {
                $this->_httpHeader($name, strlen($blob));
                echo $blob;
                return true;
            } else {
                return PEAR::raiseError("Failed to create a thumbnail.");
            }
        } catch (Exception $e) {
            return PEAR::raiseError(get_class($e) . '::' . $e->getMessage());
        }
    }

    // }}}
    // {{{ _convert()

    /**
     * Image conversion abstraction.
     *
     * @param string $source
     * @param array $size
     * @return Imagick
     */
    protected function _convert($source, $size)
    {
        extract($size);

        $im = new Imagick();
        $js = 0;
        $hint = max($tw, $th) * $js;
        if ($hint > 0 && $hint < $sw && $hint < $sh) {
            if (pathinfo($source, PATHINFO_EXTENSION) === 'jpg') {
                $im->setOption('jpeg:size', sprintf('%dx%d', $hint, $hint));
            }
        }
        $im->readImage($source);

        if ($im->getNumberImages() > 1) {
            $im->flattenImages();
        }

        $colorspace = $im->getImageColorSpace();
        if ($colorspace !== Imagick::COLORSPACE_RGB &&
            $colorspace !== Imagick::COLORSPACE_SRGB) {
            $im->setImageColorSpace(Imagick::COLORSPACE_SRGB);
        }

        if ($im->getImageMatte()) {
            $im->setImageMatte(false);
        }

        if ($this->doesTrimming()) {
            $im->cropImage($sw, $sh, $sx, $sy);
        }

        if ($this->doesResampling()) {
            $im->resizeImage($tw, $th, Imagick::FILTER_LANCZOS, 0.9, true);
        }

        $im->stripImage();

        $degrees = $this->getRotation();
        if ($degrees) {
            $bgcolor = $this->getBgColor();
            $bg = sprintf('rgb(%d,%d,%d)', $bgcolor[0], $bgcolor[1], $bgcolor[2]);
            $im->rotateImage(new ImagickPixel($bg), $degrees);
        }

        if ($this->isPng()) {
            $im->setFormat('PNG');
        } else {
            $im->setFormat('JPEG');
            if ($this->getQuality()) {
                $im->setCompressionQuality($this->getQuality());
            }
        }

        return $im;
    }

    // }}}
    // {{{ _decorateAnimationGif()

    /**
     * stamp animation gif mark.
     *
     * @param resource $thumb
     * @return resource
     */
    protected function _decorateAnimationGif($thumb)
    {
        $deco = new Imagick();
        $deco->readImage($this->getDecorateAnigifFilePath());
        $deco->resizeImage($thumb->getImageWidth(), $thumb->getImageHeight(),
            Imagick::FILTER_UNDEFINED, 1);
        $thumb->compositeImage($deco, Imagick::COMPOSITE_OVER, 0, 0);
        $deco->destroy();
        return $thumb;
    }

    // }}}
    // {{{ _decorateGifCaution()

    /**
     * stamp gif caution mark.
     *
     * @param resource $thumb
     * @return resource
     */
    protected function _decorateGifCaution($thumb)
    {
        $deco = new Imagick();
        $deco->readImage($this->getDecorateGifCautionFilePath());
        $thumb->compositeImage($deco, Imagick::COMPOSITE_OVER,
            ($thumb->getImageWidth() - $deco->getImageWidth())/2,
            ($thumb->getImageHeight() - $deco->getImageHeight())/2);
        $deco->destroy();
        return $thumb;
    }

    // }}}
}

// }}}

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
