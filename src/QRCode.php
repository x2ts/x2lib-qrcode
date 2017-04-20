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
use x2ts\ComponentFactory as X;
use x2ts\Toolkit;

/**
 * Class QRCode
 *
 * @package x2lib\qrcode
 */
class QRCode extends Component {
    protected static $_conf = [
        'size'     => 10,
        'margin'   => 4,
        'level'    => 1,
        'cacheId'  => 'cache',
        'duration' => 900,
    ];

    private $useCache = false;

    public function cache() {
        $this->useCache = true;
        return $this;
    }

    /**
     * @return bool|\x2ts\cache\ICache
     * @throws \x2ts\ComponentNotFoundException
     * @throws \InvalidArgumentException
     */
    private function getCache() {
        return X::getComponent($this->conf['cacheId']);
    }

    private function cacheKey(string $url, $iconFile) {
        return md5("$url:$iconFile:" . serialize($this->conf));
    }

    public function png(string $url, $iconFile = null) {
        $key = $this->cacheKey($url, $iconFile);
        if ($this->useCache) {
            $etag = X::router()->action->header('If-None-Match', '');
            if ($etag === "W/$key") {
                X::router()->action->setHeader('ETag', "W/$key", true, 304);
                return;
            }

            X::router()->action->setHeader('ETag', "W/$key", true);
            if ($png = $this->getCache()->get("qr:$key")) {
                X::router()->action->setHeader('Content-Type', 'image/png', true)->out($png);
                return;
            }
            ob_start();
        }
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
        if ($this->useCache) {
            $png = ob_get_contents();
            ob_clean();
            $this->getCache()->set("qr:$key", $png, $this->conf['duration']);
            X::router()->action->setHeader('Content-Type', 'image/png', true)->out($png);
        }
    }

    private function iconFactor(): float {
        return [0.2, 0.27, 0.3, 0.35][$this->conf['level']];
    }
}
