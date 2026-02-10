<?php

namespace AwardWallet\MainBundle\Globals;

use Sinergi\BrowserDetector\Browser;
use Sinergi\BrowserDetector\BrowserDetector;
use Sinergi\BrowserDetector\Os;
use Sinergi\BrowserDetector\OsDetector;

class UserAgentUtils
{
    public static function isMobileBrowser($useragent)
    {
        // visit http://detectmobilebrowsers.com/ for updates
        return
            isset($useragent)
            && (
                preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od|ad)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino|android|ipad|playbook|silk|Windows NT [0-9.]+; ARM|Trident\/[0-9.]+; Touch|AdsBot-Google-Mobile-Apps/i', $useragent)

                || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))
            );
    }

    public static function getBrowser(?string $userAgent): array
    {
        if (empty($userAgent)) {
            return [
                'soft' => '',
                'browser' => 'unknown',
                'platform' => 'unknown',
                'version' => 'unknown',
                'isMobile' => false,
                'isDesktop' => true,
            ];
        }

        $soft = [];

        $os = new Os($userAgent);
        OsDetector::detect($os, $os->getUserAgent());
        $browser = new Browser($userAgent);
        BrowserDetector::detect($browser, $browser->getUserAgent());

        $isMobile = $os->isMobile();
        $isDesktop = !$isMobile;

        if (preg_match('/Mobile App \((android|ios|browser) ([\d.]*)/is', $userAgent, $match)) {
            if ($match[1] === 'android') {
                $os->setName(Os::ANDROID);
                $browser->setName('Mobile App');
            }

            if ($match[1] === 'ios') {
                $os->setName(Os::IOS);
                $browser->setName('Mobile App');
            }

            if ($match[1] === 'browser') {
                $os->setName('Mobile Browser');
            }

            $browser->setVersion($match[2]);
        }

        if (
            false !== stripos($userAgent, 'Mobile Safari UIWebView')
            && false !== stripos($userAgent, 'iPhone')
            && false !== strpos($userAgent, ' Mobile/')
        ) {
            $os->setName(Os::IOS);
            $browser->setName('Mobile App');
        }

        if (preg_match('/(AwardWallet|CFNetwork|okhttp)\/([\d.]*)/is', $userAgent, $match)) {
            if ($match[1] === 'AwardWallet') {
                $os->setName(Os::IOS);
            }

            if ($match[1] === 'okhttp') {
                $os->setName(Os::ANDROID);
            }

            $browser->setName('Mobile App');
            $browser->setVersion($match[2]);
        }

        if ($os->getName() !== Os::UNKNOWN) {
            $soft[] = $os->getName();
        }

        if ($os->isMobile()) {
            $soft[] = "Mobile";
        }

        if ($browser->getName() !== Browser::UNKNOWN) {
            $soft[] = $browser->getName();
        }

        if ($browser->getVersion() !== Browser::VERSION_UNKNOWN) {
            $soft[] = $browser->getVersion();
        }

        $soft = implode(' ', $soft);

        if (false !== stripos($soft, 'Mobile')) {
            $isMobile = true;
            $isDesktop = false;
        }

        return [
            'soft' => $soft,
            'browser' => $browser->getName(),
            'platform' => $os->getName(),
            'version' => $browser->getVersion(),
            'isMobile' => $isMobile,
            'isDesktop' => $isDesktop,
        ];
    }

    public static function filterUserAgent($agent)
    {
        // exclude some google apps, like:
        // Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.120 Safari/537.36 AppEngine-Google; (+http://code.google.com/appengine; appid: s~lovejiani043)
        return trim(preg_replace('#AppEngine\-Google;\s*\([^\)]+\)#ims', '', $agent));
    }

    public static function isBrowserWebpCompatible(string $userAgent, ?string $httpAccept = null): bool
    {
        if (!empty($httpAccept) && false !== strpos($httpAccept, 'webp')) {
            return true;
        }

        if (empty($userAgent)) {
            return false;
        }

        if (preg_match('#Firefox/(?<version>[0-9]{2,})#i', $userAgent, $matches)) {
            if (66 >= (int) $matches['version']) {
                return false;
            }
        }

        if (preg_match('#(?:iPad|iPhone)(.*)Version/(?<version>[0-9]{2,})#i', $userAgent, $matches)) {
            if (14 > (int) $matches['version']) {
                return false;
            }

            return true;
        }

        if (preg_match('#Version/(?<version>[0-9]{2,})(?:.*)Safari#i', $userAgent, $matches)) {
            if (16 > (int) $matches['version']) {
                return false;
            }
        }

        return true;
    }
}
