<?php

/*
 * This file is part of the YesWiki Extension newimagelib.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Newimagelib\Service;

use Zebra_Image;

class NewimagelibService
{
    public function __construct()
    {
    }

    /**
     * resize a file using Zebra lib
     * @param string $image_src
     * @param string $image_dest
     * @param $largeur
     * @param $hauteur
     * @param string $mode
     */
    public function redimensionner_image(string $image_src, string  $image_dest, $largeur, $hauteur, $mode = "fit")
    {
        if (empty($image_src) || empty($image_dest)) {
            return false;
        }
        $imgTrans = new Zebra_Image();
        $imgTrans->auto_handle_exif_orientation = true;
        $imgTrans->preserve_aspect_ratio = true;
        $imgTrans->enlarge_smaller_images = true;
        $imgTrans->preserve_time = true;
        $imgTrans->handle_exif_orientation_tag = true;
        $imgTrans->source_path = $image_src;
        $imgTrans->target_path = $image_dest;
        
        if ($mode == "crop") {
            $wantedRatio = $largeur/$hauteur;
            // get image info except for webp
            if (
                    !(
                        version_compare(PHP_VERSION, '7.0.0') >= 0 &&
                        version_compare(PHP_VERSION, '7.1.0') < 0 &&
                        (
                            $imgTrans->source_type = strtolower(substr($imgTrans->source_path, strrpos($imgTrans->source_path, '.') + 1))
                        ) === 'webp'
                    ) &&
                    !list($sourceImageWidth, $sourceImageHeight, $sourceImageType) = @getimagesize($imgTrans->source_path)
                ) {
                return false;
            }
            $imageRatio = $sourceImageWidth/$sourceImageHeight;

            if ($imageRatio != $wantedRatio) {
                if ($imageRatio > $wantedRatio) {
                    // width too large, keep height
                    $newWidth = round($sourceImageHeight * $wantedRatio);
                    $newHeight = $sourceImageHeight;
                } else {
                    // height too large, keep width
                    $newHeight = round($sourceImageWidth / $wantedRatio);
                    $newWidth = $sourceImageWidth;
                }
                // crop
                $ext = pathinfo($image_src)['extension'];
                do {
                    $tempFile = tmpfile();
                    $tempFileName = stream_get_meta_data($tempFile)['uri'].".$ext";
                    unlink(stream_get_meta_data($tempFile)['uri']);
                } while (file_exists($tempFileName));
                $imgTrans->target_path = $tempFileName;
                if ($imgTrans->resize(intval($newWidth), intval($newHeight), ZEBRA_IMAGE_CROP_CENTER, '#FFFFFF')) {
                    $imgTrans->source_path = $tempFileName;
                }
                $imgTrans->target_path = $image_dest;
            }
        }
        $result = $imgTrans->resize(intval($largeur), intval($hauteur), ZEBRA_IMAGE_NOT_BOXED, '#FFFFFF');
        
        if ($mode == "crop" && !empty($tempFileName) && file_exists($tempFileName)) {
            unlink($tempFileName);
        }
        if (!$result) {
            // in case of error, show error code
            return $imgTrans->error;
        // if there were no errors
        } else {
            return $imgTrans->target_path;
        }
    }
}
