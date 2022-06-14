<?php

/*
 * This file is part of the YesWiki Extension newimagelib.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace YesWiki\Newimagelib;

use YesWiki\Core\YesWikiHandler;
use YesWiki\Security\Controller\SecurityController;

class UpdateHandler__ extends YesWikiHandler
{
    private const ATTACH_PATH = 'tools/attach/libs/attach.lib.php';

    public function run()
    {
        $this->securityController = $this->getService(SecurityController::class);
        if ($this->securityController->isWikiHibernated()) {
            throw new \Exception(_t('WIKI_IN_HIBERNATION'));
        };
        if (!$this->wiki->UserIsAdmin()) {
            return null;
        }

        $output = '<strong>'._t('NEWIMAGELIB_UPDATE_HANDLER_TITLE').'</strong><br />';
        $messages = [];
        $state = 'success';
        extract($this->updateEnumField($messages, $state));
        if (!empty($messages)) {
            $output .= '<div class="alert alert-'.$state.'">';
            if ($state !== 'success') {
                $output .= _t('NEWIMAGELIB_UPDATE_HANDLER_HEADER');
            }
            $output .= '<ul>';
            foreach ($messages as $message) {
                $output .= '<li>'.$message.'</li>';
            }
            $output .= '</ul>';
            $output .= '</div>';
        }

        // set output
        $this->output = str_replace(
            '<!-- end handler /update -->',
            $output.'<!-- end handler /update -->',
            $this->output
        );
        return null;
    }

    private function updateEnumField(array $messages, string $state): array
    {
        $filePath = self::ATTACH_PATH;
        if (!file_exists($filePath)) {
            $messages[] = _t('NEWIMAGELIB_UPDATE_HANDLER_FILE_NOT_FOUND', ['file'=>$filePath]);
            $state = 'danger';
        } else {
            $content = file_get_contents($filePath);
            $replacement =
                "if (\$this->wiki->services->has(\\YesWiki\\Newimagelib\\Service\\NewimagelibService::class)) {\n".
                "    return \$this->wiki->services->get(\\YesWiki\\Newimagelib\\Service\\NewimagelibService::class)->redimensionner_image(\$image_src, \$image_dest, \$largeur, \$hauteur, \$mode);\n".
                "}";
            $patternBefore = "/(".preg_quote('public function redimensionner_image($image_src, $image_dest, $largeur, $hauteur, $mode = "fit")', '/')."\s*\{)(\s*)(if)/";
            $patternReplace = $patternBefore;
            $patternReplacement = "$1$2$replacement$2$3";
            $patternAfter = "/".preg_quote('public function redimensionner_image($image_src, $image_dest, $largeur, $hauteur, $mode = "fit")', '/')."\s*\{\s*".preg_quote($replacement, '/')."\s*(if)/";
            if (preg_match($patternAfter, $content, $matches)) {
                $messages[] = _t('NEWIMAGELIB_UPDATE_HANDLER_FILE_ALREADY_UP_TO_DATE', ['file'=>basename($filePath)]);
            } elseif (!preg_match($patternBefore, $content, $matches)) {
                $messages[] = _t('NEWIMAGELIB_UPDATE_HANDLER_FILE_NOT_WAITED', ['file'=>basename($filePath)]);
                $state = 'danger';
            } else {
                $newContent = preg_replace($patternReplace, $patternReplacement, $content);
                if (is_null($newContent) || $newContent === $content) {
                    $messages[] = _t('NEWIMAGELIB_UPDATE_HANDLER_FILE_NOT_MODIFIED', ['file'=>basename($filePath)]);
                    $state = 'danger';
                } else {
                    file_put_contents($filePath, $newContent);
                    $content = file_get_contents($filePath);
                    if (!preg_match($patternAfter, $content, $matches)) {
                        $messages[] = _t('NEWIMAGELIB_UPDATE_HANDLER_FILE_WRONG_MODIFICATION', ['file'=>basename($filePath)]);
                        $state = 'danger';
                    } else {
                        $messages[] = _t('NEWIMAGELIB_UPDATE_HANDLER_FILE_RIGHT_MODIFICATION', ['file'=>basename($filePath)]);
                    }
                }
            }
        }
        return compact(['messages','state']);
    }
}
