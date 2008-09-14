<?php
/**
 * rep2expack - カスタムフォント設定用関数群
 */

// {{{ fontconfig_detect_agent()

/**
 * フォント設定用にユーザエージェントを判定する
 *
 * @return string
 */
function fontconfig_detect_agent($ua = null)
{
    if ($ua === null) {
        $ua = $_SERVER['HTTP_USER_AGENT'];
    }
    if (preg_match('/\bWindows\b/', $ua)) {
        return 'windows';
    }
    if (preg_match('/\bMac(intoth)?\b/', $ua)) {
        if (preg_match('/\b(Safari|AppleWebKit)\/([\d]+)/', $ua, $matches)) {
            $version = (int)$matches[2];
            if ($version >= 500) {
                return 'safari3';
            } else if ($version >= 400) {
                return 'safari2';
            } else {
                return 'safari1';
            }
        } elseif (preg_match('/\b(Mac ?OS ?X)\b/', $ua)) {
            return 'macosx';
        } else {
            return 'macos9';
        }
    }
    return 'other';
}

// }}}
// {{{ fontconfig_apply_custom()

/**
 * フォント設定を読み込む
 *
 * @return void
 */
function fontconfig_apply_custom()
{
    global $STYLE, $_conf, $skin_en, $skin_uniq;

    if ($_conf['expack.skin.enabled']) {
        if (isset($_conf['expack.am.fontfamily'])) {
            $_conf['expack.am.fontfamily.orig'] = $_conf['expack.am.fontfamily'];
        } else {
            $_conf['expack.am.fontfamily.orig'] = '';
        }

        if (file_exists($_conf['expack.skin.fontconfig_path'])) {
            $fontconfig_data = file_get_contents($_conf['expack.skin.fontconfig_path']);
            $current_fontconfig = unserialize($fontconfig_data);
        }

        if (!is_array($current_fontconfig)) {
            $current_fontconfig = array('enabled' => false, 'custom' => array());
        }

        $type = fontconfig_detect_agent();

        if ($current_fontconfig['enabled'] && is_array($current_fontconfig['custom'][$type])) {
            $skin_uniq = P2_VERSION_ID . sprintf('.%u', crc32($fontconfig_data));

            foreach ($current_fontconfig['custom'][$type] as $key => $value) {
                if (strstr($key, 'fontfamily') && $value == '-') {
                    if ($key == 'fontfamily_aa') {
                        $_conf['expack.am.fontfamily'] = '';
                    } else {
                        $STYLE["{$key}.orig"] = (isset($STYLE[$key])) ? $STYLE[$key] : '';
                        $STYLE[$key] = '';
                    }
                } elseif ($value) {
                    if ($key == 'fontfamily_aa') {
                        $_conf['expack.am.fontfamily'] = $value;
                    } else {
                        $STYLE["{$key}.orig"] = (isset($STYLE[$key])) ? $STYLE[$key] : '';
                        $STYLE[$key] = $value;
                    }
                }
            }

            $skin_en = preg_replace('/&amp;_=[^&]*/', '', $skin_en) . '&amp;_=' . rawurlencode($skin_uniq);
        }
    }
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
