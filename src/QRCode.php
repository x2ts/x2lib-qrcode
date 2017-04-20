<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/4/20
 * Time: PM1:10
 */

namespace x2lib\qrcode;

use PHPQRCode\QRcode as PhpQRcode;
use x2ts\Component;
use x2ts\Toolkit;

class QRCode extends Component {
    protected static $_conf = [
        'size'   => 10,
        'margin' => 4,
        'level'  => 1,
    ];

    private $useCache = false;

    public function cache() {
        $this->useCache = true;
        return $this;
    }

    public function png(string $url, $iconFile = null) {
        if (!empty($iconFile)) {
            ob_start();
        }
        PhpQRcode::png(
            $url,
            false,
            $this->conf['level'],
            $this->conf['size'],
            $this->conf['margin']
        );
        if (!empty($iconFile)) {
            $qrCodePng = ob_get_contents();
            ob_end_clean();
            $iconData = @file_get_contents($iconFile);
            if ($iconData === false) {
                throw new QRException('read icon file failed', QRException::ICON_UNREADABLE);
            }
            $icon = @imagecreatefromstring($iconData);
            if ($icon === false) {
                throw new QRException('$iconFile is not valid image', QRException::INVALID_ICON);
            }
            $iconSize = getimagesizefromstring($iconData);
            Toolkit::trace($iconSize);

            $qrCode = imagecreatefromstring($qrCodePng);
            $qrSize = getimagesizefromstring($qrCodePng);
            Toolkit::trace($qrSize);

            $iconDotWidth = floor(($qrSize[0] / $this->conf['size'] - 2 * $this->conf['margin']) *
                $this->iconFactor());
            $iconOffset = floor(($qrSize[0] / $this->conf['size'] - $iconDotWidth) / 2) * $this->conf['size'];
            $iconWidth = $iconDotWidth * $this->conf['size'];
            $qr = imagecreatetruecolor($qrSize[0], $qrSize[1]);
            imagecopy($qr, $qrCode, 0, 0, 0, 0, $qrSize[0], $qrSize[1]);
            imagecopyresampled(
                $qr,
                $icon,
                $iconOffset,
                $iconOffset,
                0,
                0,
                $iconWidth,
                $iconWidth,
                $iconSize[0],
                $iconSize[1]
            );
            imagepng($qr);
            imagedestroy($icon);
            imagedestroy($qrCode);
            imagedestroy($qr);
        }
    }

    private function iconFactor(): float {
        return [0.2, 0.27, 0.3, 0.35][$this->conf['level']];
    }
}
