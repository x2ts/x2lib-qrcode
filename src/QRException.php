<?php
/**
 * Created by IntelliJ IDEA.
 * User: rek
 * Date: 2017/4/20
 * Time: PM1:12
 */

namespace x2lib\qrcode\QRCode;


class QRException extends \Exception {
    const ICON_UNREADABLE = 1;
    const INVALID_ICON = 2;
}