<?php
class resize
{
	// *** Class variables
	private $image;
	private $width;
	private $height;
	private $imageResized;

	function __construct($fileName)
	{
		// *** Open up the file
		$this->image = $this->openImage($fileName);

		// *** Get width and height
		$this->width  = imagesx($this->image);
		$this->height = imagesy($this->image);
	}

	## --------------------------------------------------------

	private function openImage($file)
	{
		// *** Get extension
		$extension = strtolower(strrchr($file, '.'));

		switch($extension)
		{
			case '.jpg':
			case '.jpeg':
				$img = @imagecreatefromjpeg($file);
				break;
			case '.gif':
				$img = @imagecreatefromgif($file);
				break;
			case '.png':
				$img = @imagecreatefrompng($file);
				break;
			default:
				$img = false;
				break;
		}
		return $img;
	}

	## --------------------------------------------------------

	public function resizeImage($newWidth, $newHeight, $option="auto")
	{
		// *** Get optimal width and height - based on $option
		$optionArray = $this->getDimensions($newWidth, $newHeight, $option);

		$optimalWidth  = $optionArray['optimalWidth'];
		$optimalHeight = $optionArray['optimalHeight'];


		// *** Resample - create image canvas of x, y size
		$this->imageResized = imagecreatetruecolor($optimalWidth, $optimalHeight);
		imagecopyresampled($this->imageResized, $this->image, 0, 0, 0, 0, $optimalWidth, $optimalHeight, $this->width, $this->height);


		// *** if option is 'crop', then crop too
		if ($option == 'crop') {
			$this->crop($optimalWidth, $optimalHeight, $newWidth, $newHeight);
		}
	}

	## --------------------------------------------------------

	private function getDimensions($newWidth, $newHeight, $option)
	{

	   switch ($option)
		{
			case 'exact':
				$optimalWidth = $newWidth;
				$optimalHeight= $newHeight;
				break;
			case 'portrait':
				$optimalWidth = $this->getSizeByFixedHeight($newHeight);
				$optimalHeight= $newHeight;
				break;
			case 'landscape':
				$optimalWidth = $newWidth;
				$optimalHeight= $this->getSizeByFixedWidth($newWidth);
				break;
			case 'auto':
				$optionArray = $this->getSizeByAuto($newWidth, $newHeight);
				$optimalWidth = $optionArray['optimalWidth'];
				$optimalHeight = $optionArray['optimalHeight'];
				break;
			case 'crop':
				$optionArray = $this->getOptimalCrop($newWidth, $newHeight);
				$optimalWidth = $optionArray['optimalWidth'];
				$optimalHeight = $optionArray['optimalHeight'];
				break;
		}
		return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
	}

	## --------------------------------------------------------

	private function getSizeByFixedHeight($newHeight)
	{
		$ratio = $this->width / $this->height;
		$newWidth = $newHeight * $ratio;
		return $newWidth;
	}

	private function getSizeByFixedWidth($newWidth)
	{
		$ratio = $this->height / $this->width;
		$newHeight = $newWidth * $ratio;
		return $newHeight;
	}

	private function getSizeByAuto($newWidth, $newHeight)
	{
		if ($this->height < $this->width)
		// *** Image to be resized is wider (landscape)
		{
			$optimalWidth = $newWidth;
			$optimalHeight= $this->getSizeByFixedWidth($newWidth);
		}
		elseif ($this->height > $this->width)
		// *** Image to be resized is taller (portrait)
		{
			$optimalWidth = $this->getSizeByFixedHeight($newHeight);
			$optimalHeight= $newHeight;
		}
		else
		// *** Image to be resizerd is a square
		{
			if ($newHeight < $newWidth) {
				$optimalWidth = $newWidth;
				$optimalHeight= $this->getSizeByFixedWidth($newWidth);
			} else if ($newHeight > $newWidth) {
				$optimalWidth = $this->getSizeByFixedHeight($newHeight);
				$optimalHeight= $newHeight;
			} else {
				// *** Sqaure being resized to a square
				$optimalWidth = $newWidth;
				$optimalHeight= $newHeight;
			}
		}

		return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
	}

	## --------------------------------------------------------

	private function getOptimalCrop($newWidth, $newHeight)
	{

		$heightRatio = $this->height / $newHeight;
		$widthRatio  = $this->width /  $newWidth;

		if ($heightRatio < $widthRatio) {
			$optimalRatio = $heightRatio;
		} else {
			$optimalRatio = $widthRatio;
		}

		$optimalHeight = $this->height / $optimalRatio;
		$optimalWidth  = $this->width  / $optimalRatio;

		return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
	}

	## --------------------------------------------------------

	private function crop($optimalWidth, $optimalHeight, $newWidth, $newHeight)
	{
		// *** Find center - this will be used for the crop
		$cropStartX = ( $optimalWidth / 2) - ( $newWidth /2 );
		$cropStartY = ( $optimalHeight/ 2) - ( $newHeight/2 );

		$crop = $this->imageResized;
		//imagedestroy($this->imageResized);

		// *** Now crop from center to exact requested size
		$this->imageResized = imagecreatetruecolor($newWidth , $newHeight);
		imagecopyresampled($this->imageResized, $crop , 0, 0, $cropStartX, $cropStartY, $newWidth, $newHeight , $newWidth, $newHeight);
    }

	## --------------------------------------------------------

	public function saveImage($savePath, $imageQuality="100")
    {
    // *** Get extension
    $extension = strrchr($savePath, '.');
    $extension = strtolower($extension);
    $im = $this->imageResized;
    switch($extension)
    {
        case '.jpg':
        case '.jpeg':
            if (imagetypes() & IMG_JPG) {
                imagejpeg($im, $savePath, $imageQuality);
            }
            break;

        case '.gif':
            if (imagetypes() & IMG_GIF) {
                imagegif($im, $savePath);
            }
            break;

        case '.png':
            $scaleQuality = round(($imageQuality/100) * 9);
            $invertScaleQuality = 9 - $scaleQuality;
            if (imagetypes() & IMG_PNG)
            {
                //imagepng($this->imageResized, $savePath, $invertScaleQuality);
//                $stamp = imagecreatefrompng('logoWmark.png');
//                $marge_right = 10;
//                $marge_bottom = 10;
//                $sx = imagesx($stamp);
//                $sy = imagesy($stamp);
//                imagecopy($im, $stamp, imagesx($im) - $sx - $marge_right, imagesy($im) - $sy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp));
//                header('Content-type: image/png');
                imagepng($im, $savePath, $invertScaleQuality);

            }
            break;

        // ... etc

        default:
            // *** No extension - No save.
            break;
    }
    imagedestroy($im);
}

    public function saveImageProfile($savePath, $imageQuality="100")
    {
        // *** Get extension
        $extension = strrchr($savePath, '.');
        $extension = strtolower($extension);
        $im = $this->imageResized;
        switch($extension)
        {
            case '.jpg':
            case '.jpeg':
                if (imagetypes() & IMG_JPG) {
                    imagejpeg($im, $savePath, $imageQuality);
                }
                break;

            case '.gif':
                if (imagetypes() & IMG_GIF) {
                    imagegif($im, $savePath);
                }
                break;

            case '.png':
                $scaleQuality = round(($imageQuality/100) * 9);
                $invertScaleQuality = 9 - $scaleQuality;
                if (imagetypes() & IMG_PNG)
                {
                    imagepng($this->imageResized, $savePath, $invertScaleQuality);
                }
                break;

            // ... etc

            default:
                // *** No extension - No save.
                break;
        }
        imagedestroy($im);
    }

    public function saveImageCropped($savePath, $imageQuality="100")
    {
        // *** Get extension
        $extension = strrchr($savePath, '.');
        $extension = strtolower($extension);
        $im = $this->imageResized;
        switch($extension)
        {
            case '.jpg':
            case '.jpeg':
                if (imagetypes() & IMG_JPG) {
                    imagejpeg($im, $savePath, $imageQuality);
                }
                break;

            case '.gif':
                if (imagetypes() & IMG_GIF) {
                    imagegif($im, $savePath);
                }
                break;

            case '.png':
                $scaleQuality = round(($imageQuality/100) * 9);
                $invertScaleQuality = 9 - $scaleQuality;
                if (imagetypes() & IMG_PNG)
                {
                    imagepng($this->imageResized, $savePath, $invertScaleQuality);
                $stamp = imagecreatefrompng('logoWmark.png');
                $marge_right = 10;
                $marge_bottom = 10;
                $sx = imagesx($stamp);
                $sy = imagesy($stamp);
                imagecopy($im, $stamp, imagesx($im) - $sx - $marge_right, imagesy($im) - $sy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp));
                header('Content-type: image/png');
                    imagepng($im, $savePath, $invertScaleQuality);

                }
                break;

            // ... etc

            default:
                // *** No extension - No save.
                break;
        }
        imagedestroy($im);
    }

    public function saveImageCroppedSmall($savePath, $imageQuality="100")
    {
        // *** Get extension
        $extension = strrchr($savePath, '.');
        $extension = strtolower($extension);
        $im = $this->imageResized;
        switch($extension)
        {
            case '.jpg':
            case '.jpeg':
                if (imagetypes() & IMG_JPG) {
                    imagejpeg($im, $savePath, $imageQuality);
                }
                break;

            case '.gif':
                if (imagetypes() & IMG_GIF) {
                    imagegif($im, $savePath);
                }
                break;

            case '.png':
                $scaleQuality = round(($imageQuality/100) * 9);
                $invertScaleQuality = 9 - $scaleQuality;
                if (imagetypes() & IMG_PNG)
                {
                    imagepng($this->imageResized, $savePath, $invertScaleQuality);
                    $stamp = imagecreatefrompng('logoWmarkSmall.png');
                    $marge_right = 5;
                    $marge_bottom = 5;
                    $sx = imagesx($stamp);
                    $sy = imagesy($stamp);
                    imagecopy($im, $stamp, imagesx($im) - $sx - $marge_right, imagesy($im) - $sy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp));
                    header('Content-type: image/png');
                    imagepng($im, $savePath, $invertScaleQuality);

                }
                break;

            // ... etc

            default:
                // *** No extension - No save.
                break;
        }
        imagedestroy($im);
    }
}
?>